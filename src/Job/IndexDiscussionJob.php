<?php

namespace FlarumExt\Search\Job;

use Flarum\Discussion\Discussion;
use Flarum\Queue\AbstractJob;
use FlarumExt\Search\Index\Indexer;

class IndexDiscussionJob extends AbstractJob
{
    public function __construct(public Discussion $discussion)
    {
        parent::__construct();
    }

    public function handle(Indexer $indexer): void
    {
        $indexer->indexDiscussion($this->discussion);
    }
}
