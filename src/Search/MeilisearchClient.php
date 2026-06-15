<?php

namespace FlarumExt\Search\Search;

use Flarum\Settings\SettingsRepositoryInterface;
use Meilisearch\Client;

class MeilisearchClient
{
    protected ?Client $client = null;

    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function client(): Client
    {
        if ($this->client === null) {
            $this->client = new Client(
                $this->settings->get('flarum-ext-search.host') ?: 'http://127.0.0.1:7700',
                $this->settings->get('flarum-ext-search.api_key') ?: null,
            );
        }

        return $this->client;
    }

    public function search(string $index, string $query, array $opts = []): array
    {
        $result = $this->client()->index($this->indexName($index))->search($query, $opts);

        return $result->getHits();
    }

    public function upsert(string $index, array $documents, bool $wait = true): void
    {
        if (empty($documents)) {
            return;
        }

        $task = $this->client()->index($this->indexName($index))->addDocuments($documents, 'id');

        if ($wait && isset($task['taskUid'])) {
            $this->client()->waitForTask($task['taskUid'], 300000, 200);
        }
    }

    public function delete(string $index, int|string $id): void
    {
        $this->client()->index($this->indexName($index))->deleteDocument($id);
    }

    public function ensureIndexes(): void
    {
        $discussions = $this->client()->index($this->indexName('discussions'));
        $discussions->updateSearchableAttributes(['title', 'firstPostContent', 'lastPostContent']);
        $discussions->updateFilterableAttributes(['tagIds', 'userId', 'isPrivate', 'createdAt']);
        $discussions->updateSortableAttributes(['createdAt', 'lastPostedAt', 'commentCount']);

        $posts = $this->client()->index($this->indexName('posts'));
        $posts->updateSearchableAttributes(['content']);
        $posts->updateFilterableAttributes(['discussionId', 'userId', 'isHidden', 'tagIds', 'createdAt']);
        $posts->updateSortableAttributes(['createdAt']);
    }

    public function indexName(string $logical): string
    {
        $prefix = $this->settings->get('flarum-ext-search.index_prefix') ?: 'flarum';

        return $prefix . '_' . $logical;
    }
}
