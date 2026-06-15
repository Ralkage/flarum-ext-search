<?php

namespace FlarumExt\Search\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use FlarumExt\Search\Index\Indexer;
use FlarumExt\Search\Search\MeilisearchClient;
use Symfony\Component\Console\Input\InputOption;

class ReindexCommand extends AbstractCommand
{
    protected static $defaultName = 'search:reindex';
    protected $description = 'Rebuild the Meilisearch index from scratch.';

    public function __construct(
        protected Indexer $indexer,
        protected MeilisearchClient $client,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'Items per batch', 500);
    }

    protected function fire(): int
    {
        $this->info('Configuring index settings...');
        $this->client->ensureIndexes();

        $chunk = max(1, (int) $this->input->getOption('chunk'));

        $discussionCount = 0;
        Discussion::with(['firstPost', 'lastPost', 'tags'])
            ->chunk($chunk, function ($batch) use (&$discussionCount) {
                $this->indexer->indexManyDiscussions($batch);
                $discussionCount += $batch->count();
                $this->info("  discussions: $discussionCount");
            });

        $postCount = 0;
        Post::query()
            ->where('type', 'comment')
            ->with(['discussion.tags'])
            ->chunk($chunk, function ($batch) use (&$postCount) {
                $this->indexer->indexManyPosts($batch);
                $postCount += $batch->count();
                $this->info("  posts: $postCount");
            });

        $this->info("Done. Indexed $discussionCount discussions and $postCount posts.");

        return 0;
    }
}
