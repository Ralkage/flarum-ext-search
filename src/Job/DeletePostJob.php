<?php

namespace FlarumExt\Search\Job;

use Flarum\Queue\AbstractJob;
use FlarumExt\Search\Index\Indexer;

class DeletePostJob extends AbstractJob
{
    public function __construct(public int $postId)
    {
        parent::__construct();
    }

    public function handle(Indexer $indexer): void
    {
        $indexer->deletePost($this->postId);
    }
}
