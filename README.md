# Better Search

Meilisearch-backed full-text search for [Flarum](https://flarum.org). Drop-in replacement for Flarum's default MySQL `LIKE`-based search, adding typo tolerance, prefix matching, instant fuzzy matches, per-field weighting (title > content), and proper relevance ranking.

## What you get

- **Typo tolerance**: "wellcom" finds "Welcome", "annoucement" finds "Announcement".
- **Searches inside replies**: a query that matches only the 14th post in a long thread now surfaces that discussion. Default Flarum search would miss it.
- **Highlighted post snippets in the search dropdown**: the matching post (not just the first) shows beneath each result.
- **Tag-aware visibility**: Meilisearch-side filtering against the actor's visible tags. Flarum's normal `whereVisibleTo` still gates final results, so nothing leaks.
- **Live updates**: every post create/edit/hide/delete reindexes via a queued job. With Flarum's default `sync` driver it runs inline (no setup); switch to Redis/database for real async.

## Requirements

- Flarum `^1.8`
- A reachable Meilisearch instance (`^1.0`)

## Install

```sh
composer require ralkage/flarum-ext-search
```

Enable in **Admin → Extensions → Better Search**.

Run a Meilisearch instance. Docker is the easiest path:

```sh
docker run -d --name meilisearch -p 7700:7700 \
  -v meilisearch_data:/meili_data \
  -e MEILI_ENV=development \
  --restart unless-stopped \
  getmeili/meilisearch:v1.11
```

For production, set `MEILI_MASTER_KEY=<long-random-string>` and pass it in the admin settings page.

Build the initial index:

```sh
php flarum search:reindex
```

Or click **Rebuild index now** on the settings page.

## Configuration

All settings live on the admin extension page:

| Setting | Default | Purpose |
| --- | --- | --- |
| Host URL | `http://127.0.0.1:7700` | Meilisearch HTTP endpoint. |
| API key | _(empty)_ | Master key or scoped key. Leave blank in development. |
| Index prefix | `flarum` | Distinguish staging/prod on a shared Meilisearch instance. |
| Max hits per query | `500` | Upper bound on results pulled per index per query. |

## How it works

Two Meilisearch indexes (`<prefix>_discussions`, `<prefix>_posts`) are kept in sync via event listeners. When a user searches, the gambit queries both indexes, merges the discussion IDs (title hits first, content hits second), and populates `most_relevant_post_id` so Flarum's existing dropdown can render the matching post snippet automatically.

## Async indexing

Live updates are dispatched as `Flarum\Queue\AbstractJob` instances. Out of the box they run on the `sync` driver (inline). To move them off the request path, point Flarum at a real queue (e.g. database/Redis) in `config.php`:

```php
'queue' => ['driver' => 'database'],
```

and run a worker: `php flarum queue:work`.

## Console

```sh
php flarum search:reindex [--chunk=500]
```

Rebuilds both indexes from MySQL. Safe to run at any time. It re-upserts; documents never leave the indexes unless explicitly deleted.

## License

MIT. See [LICENSE](LICENSE).
