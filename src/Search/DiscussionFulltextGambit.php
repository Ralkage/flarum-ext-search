<?php

namespace FlarumExt\Search\Search;

use Flarum\Search\GambitInterface;
use Flarum\Search\SearchState;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Database\Query\Expression;

class DiscussionFulltextGambit implements GambitInterface
{
    public function __construct(
        protected MeilisearchClient $client,
        protected SettingsRepositoryInterface $settings,
    ) {
    }

    public function apply(SearchState $search, $bit)
    {
        $limit = (int) ($this->settings->get('flarum-ext-search.hit_limit') ?: 500);
        $query = (string) $bit;
        $actor = $search->getActor();
        $tagIds = $this->visibleTagIds($actor);

        $discussionOpts = ['limit' => $limit, 'attributesToRetrieve' => ['id']];
        if ($filter = $this->discussionFilter($actor, $tagIds)) {
            $discussionOpts['filter'] = $filter;
        }

        $postOpts = ['limit' => $limit, 'attributesToRetrieve' => ['id', 'discussionId']];
        if ($filter = $this->postFilter($actor, $tagIds)) {
            $postOpts['filter'] = $filter;
        }

        $discussionHits = $this->client->search('discussions', $query, $discussionOpts);
        $postHits = $this->client->search('posts', $query, $postOpts);

        $bestPostPerDiscussion = [];
        foreach ($postHits as $hit) {
            $discussionId = (int) $hit['discussionId'];
            if (! isset($bestPostPerDiscussion[$discussionId])) {
                $bestPostPerDiscussion[$discussionId] = (int) $hit['id'];
            }
        }

        $ordered = [];
        foreach ($discussionHits as $hit) {
            $ordered[(int) $hit['id']] = true;
        }
        foreach ($postHits as $hit) {
            $ordered[(int) $hit['discussionId']] = true;
        }

        $ids = array_keys($ordered);

        if (empty($ids)) {
            $search->getQuery()->whereRaw('1 = 0');

            return [];
        }

        $list = implode(',', $ids);

        $search->getQuery()
            ->addSelect(new Expression($this->mostRelevantPostExpression($bestPostPerDiscussion)))
            ->whereIn('discussions.id', $ids)
            ->orderByRaw("FIELD(discussions.id, $list)");

        return $ids;
    }

    protected function mostRelevantPostExpression(array $bestPostPerDiscussion): string
    {
        if (empty($bestPostPerDiscussion)) {
            return 'discussions.first_post_id as most_relevant_post_id';
        }

        $whens = [];
        foreach ($bestPostPerDiscussion as $discussionId => $postId) {
            $whens[] = "WHEN $discussionId THEN $postId";
        }

        return 'CASE discussions.id ' . implode(' ', $whens) . ' ELSE discussions.first_post_id END as most_relevant_post_id';
    }

    protected function discussionFilter(User $actor, ?array $tagIds): ?string
    {
        if ($actor->isAdmin()) {
            return null;
        }

        $clauses = ['isPrivate = false'];
        if ($tagIds !== null) {
            $clauses[] = $this->tagClause($tagIds);
        }

        return implode(' AND ', $clauses);
    }

    protected function postFilter(User $actor, ?array $tagIds): ?string
    {
        $clauses = [];

        if (! $actor->isAdmin()) {
            $clauses[] = $actor->id
                ? '(isHidden = false OR userId = ' . (int) $actor->id . ')'
                : 'isHidden = false';

            if ($tagIds !== null) {
                $clauses[] = $this->tagClause($tagIds);
            }
        }

        return $clauses ? implode(' AND ', $clauses) : null;
    }

    protected function tagClause(array $tagIds): string
    {
        if (empty($tagIds)) {
            return 'tagIds IS EMPTY';
        }

        return '(tagIds IN [' . implode(',', $tagIds) . '] OR tagIds IS EMPTY)';
    }

    protected function visibleTagIds(User $actor): ?array
    {
        if (! class_exists(Tag::class)) {
            return null;
        }

        return Tag::whereVisibleTo($actor)->pluck('id')->map('intval')->all();
    }
}
