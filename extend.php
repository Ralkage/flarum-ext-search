<?php

/*
 * This file is part of flarum-ext/search.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace FlarumExt\Search;

use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use FlarumExt\Search\Api\Controller\HealthController;
use FlarumExt\Search\Api\Controller\ReindexController;
use FlarumExt\Search\Console\ReindexCommand;
use FlarumExt\Search\Listener\UpdateIndex;
use FlarumExt\Search\Search\DiscussionFulltextGambit;

return [
    new Extend\Locales(__DIR__ . '/locale'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    (new Extend\SimpleFlarumSearch(DiscussionSearcher::class))
        ->setFullTextGambit(DiscussionFulltextGambit::class),

    (new Extend\Event())
        ->subscribe(UpdateIndex::class),

    (new Extend\Console())
        ->command(ReindexCommand::class),

    (new Extend\Routes('api'))
        ->post('/search/reindex', 'flarum-ext-search.reindex', ReindexController::class)
        ->get('/search/health', 'flarum-ext-search.health', HealthController::class),

    (new Extend\Settings())
        ->default('flarum-ext-search.host', 'http://127.0.0.1:7700')
        ->default('flarum-ext-search.index_prefix', 'flarum')
        ->default('flarum-ext-search.hit_limit', 500),
];
