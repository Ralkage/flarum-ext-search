<?php

namespace FlarumExt\Search\Api\Controller;

use Flarum\Http\RequestUtil;
use FlarumExt\Search\Search\MeilisearchClient;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class HealthController implements RequestHandlerInterface
{
    public function __construct(protected MeilisearchClient $client)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        try {
            $health = $this->client->client()->health();

            $discussions = $this->client->client()->index($this->client->indexName('discussions'))->stats();
            $posts = $this->client->client()->index($this->client->indexName('posts'))->stats();

            return new JsonResponse([
                'ok' => ($health['status'] ?? null) === 'available',
                'discussions' => $discussions['numberOfDocuments'] ?? 0,
                'posts' => $posts['numberOfDocuments'] ?? 0,
            ]);
        } catch (Throwable $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
