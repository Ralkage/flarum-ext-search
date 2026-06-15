<?php

namespace FlarumExt\Search\Api\Controller;

use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use FlarumExt\Search\Index\Indexer;
use FlarumExt\Search\Search\MeilisearchClient;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ReindexController implements RequestHandlerInterface
{
    public function __construct(
        protected Indexer $indexer,
        protected MeilisearchClient $client,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $this->client->ensureIndexes();

        $discussions = 0;
        Discussion::with(['firstPost', 'lastPost', 'tags'])->chunk(500, function ($batch) use (&$discussions) {
            $this->indexer->indexManyDiscussions($batch);
            $discussions += $batch->count();
        });

        $posts = 0;
        Post::query()->where('type', 'comment')->with(['discussion.tags'])->chunk(500, function ($batch) use (&$posts) {
            $this->indexer->indexManyPosts($batch);
            $posts += $batch->count();
        });

        return new JsonResponse([
            'discussions' => $discussions,
            'posts' => $posts,
        ]);
    }
}
