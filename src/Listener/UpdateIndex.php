<?php

namespace FlarumExt\Search\Listener;

use Flarum\Discussion\Event\Deleted as DiscussionDeleted;
use Flarum\Discussion\Event\Renamed;
use Flarum\Discussion\Event\Started;
use Flarum\Post\Event\Deleted as PostDeleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use FlarumExt\Search\Job\DeleteDiscussionJob;
use FlarumExt\Search\Job\DeletePostJob;
use FlarumExt\Search\Job\IndexDiscussionJob;
use FlarumExt\Search\Job\IndexPostJob;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue;

class UpdateIndex
{
    public function __construct(protected Queue $queue)
    {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Started::class, [$this, 'whenDiscussionStarted']);
        $events->listen(Renamed::class, [$this, 'whenDiscussionRenamed']);
        $events->listen(DiscussionDeleted::class, [$this, 'whenDiscussionDeleted']);

        $events->listen(Posted::class, [$this, 'whenPostSaved']);
        $events->listen(Revised::class, [$this, 'whenPostSaved']);
        $events->listen(Hidden::class, [$this, 'whenPostSaved']);
        $events->listen(Restored::class, [$this, 'whenPostSaved']);
        $events->listen(PostDeleted::class, [$this, 'whenPostDeleted']);
    }

    public function whenDiscussionStarted(Started $event): void
    {
        $this->queue->push(new IndexDiscussionJob($event->discussion));
    }

    public function whenDiscussionRenamed(Renamed $event): void
    {
        $this->queue->push(new IndexDiscussionJob($event->discussion));
    }

    public function whenDiscussionDeleted(DiscussionDeleted $event): void
    {
        $this->queue->push(new DeleteDiscussionJob((int) $event->discussion->id));
    }

    public function whenPostSaved($event): void
    {
        if (! $event->post) {
            return;
        }

        $this->queue->push(new IndexPostJob($event->post));

        if ($event->post->discussion) {
            $this->queue->push(new IndexDiscussionJob($event->post->discussion));
        }
    }

    public function whenPostDeleted(PostDeleted $event): void
    {
        if (! $event->post) {
            return;
        }

        $this->queue->push(new DeletePostJob((int) $event->post->id));

        if ($event->post->discussion) {
            $this->queue->push(new IndexDiscussionJob($event->post->discussion));
        }
    }
}
