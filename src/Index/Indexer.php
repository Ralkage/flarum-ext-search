<?php

namespace FlarumExt\Search\Index;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use FlarumExt\Search\Search\MeilisearchClient;

class Indexer
{
    public function __construct(protected MeilisearchClient $client)
    {
    }

    public function indexDiscussion(Discussion $discussion): void
    {
        $this->client->upsert('discussions', [$this->discussionToDocument($discussion)], false);
    }

    public function indexManyDiscussions(iterable $discussions): void
    {
        $docs = [];

        foreach ($discussions as $discussion) {
            $docs[] = $this->discussionToDocument($discussion);
        }

        $this->client->upsert('discussions', $docs);
    }

    public function deleteDiscussion(int $id): void
    {
        $this->client->delete('discussions', $id);
    }

    public function indexPost(Post $post): void
    {
        if (! $this->isCommentPost($post)) {
            return;
        }

        $this->client->upsert('posts', [$this->postToDocument($post)], false);
    }

    public function indexManyPosts(iterable $posts): void
    {
        $docs = [];

        foreach ($posts as $post) {
            if (! $this->isCommentPost($post)) {
                continue;
            }

            $docs[] = $this->postToDocument($post);
        }

        $this->client->upsert('posts', $docs);
    }

    public function deletePost(int $id): void
    {
        $this->client->delete('posts', $id);
    }

    protected function discussionToDocument(Discussion $d): array
    {
        $first = $d->firstPost ?? $d->posts()->orderBy('number')->first();
        $last = $d->lastPost ?? $d->posts()->orderByDesc('number')->first();

        return [
            'id' => (int) $d->id,
            'title' => (string) $d->title,
            'firstPostContent' => (string) ($first?->content ?? ''),
            'lastPostContent' => (string) ($last?->content ?? ''),
            'tagIds' => $d->tags->pluck('id')->map('intval')->all(),
            'userId' => (int) $d->user_id,
            'isPrivate' => (bool) $d->is_private,
            'createdAt' => $d->created_at?->timestamp,
            'lastPostedAt' => $d->last_posted_at?->timestamp,
            'commentCount' => (int) $d->comment_count,
        ];
    }

    protected function postToDocument(Post $post): array
    {
        $discussion = $post->discussion;

        return [
            'id' => (int) $post->id,
            'discussionId' => (int) $post->discussion_id,
            'content' => (string) ($post->content ?? ''),
            'userId' => (int) $post->user_id,
            'isHidden' => $post->hidden_at !== null,
            'tagIds' => $discussion?->tags->pluck('id')->map('intval')->all() ?? [],
            'createdAt' => $post->created_at?->timestamp,
        ];
    }

    protected function isCommentPost(Post $post): bool
    {
        return $post->type === 'comment';
    }
}
