<?php

namespace FlarumExt\Search\Job;

use Flarum\Post\Post;
use Flarum\Queue\AbstractJob;
use FlarumExt\Search\Index\Indexer;

class IndexPostJob extends AbstractJob
{
    public function __construct(public Post $post)
    {
        parent::__construct();
    }

    public function handle(Indexer $indexer): void
    {
        $indexer->indexPost($this->post);
    }
}
