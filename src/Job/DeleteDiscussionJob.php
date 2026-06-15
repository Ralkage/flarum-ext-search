<?php

namespace FlarumExt\Search\Job;

use Flarum\Queue\AbstractJob;
use FlarumExt\Search\Index\Indexer;

class DeleteDiscussionJob extends AbstractJob
{
    public function __construct(public int $discussionId)
    {
        parent::__construct();
    }

    public function handle(Indexer $indexer): void
    {
        $indexer->deleteDiscussion($this->discussionId);
    }
}
