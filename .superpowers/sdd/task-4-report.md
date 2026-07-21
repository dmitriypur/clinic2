# Task 4 report: Anonymous full-search analytics and retention

## Status

Complete.

## TDD evidence

- RED: `php artisan test --filter=SearchAnalyticsTest` initially failed because the analytics table, model, recorder, and controller integration did not exist.
- GREEN: full-search-only recording, normalization, current city and total, PII exclusions, recorder-failure isolation, and the 90-day pruning boundary now pass.

## Changed files

- `database/migrations/2026_07_21_120000_create_site_search_queries_table.php`
- `app/Models/SiteSearchQuery.php`
- `app/Services/SiteSearchAnalyticsRecorder.php`
- `app/Http/Controllers/SearchController.php`
- `app/Console/Kernel.php`
- `tests/Feature/SiteSearch/SearchAnalyticsTest.php`
- `.superpowers/sdd/progress.md`
- `.superpowers/sdd/task-4-report.md`

## Implementation

- `site_search_queries` stores only the normalized, 100-character query, nullable city ID, result count, and timestamps, indexed by creation time and city/time.
- The full `/search` controller action records the paginator total after a successful search; live suggestions do not use the recorder.
- The recorder skips blank queries, e-mail addresses, and phone-like values with seven or more digits. It does not accept or persist request, user, cookie, session, IP, or URL data.
- Persistence failures are reported and leave the full search response intact.
- `SiteSearchQuery` uses `MassPrunable` for entries strictly older than 90 days; the focused `model:prune` schedule runs daily.

## Verification

```text
php artisan test --filter=SearchAnalyticsTest
PASS: 5 tests, 13 assertions

php artisan test tests/Feature/SiteSearch
PASS: 23 tests, 80 assertions

php artisan schedule:list
PASS: daily model:prune --model="App\\Models\\SiteSearchQuery" is registered

git diff --check
PASS
```

## Risks / follow-up

- `php artisan model:prune --model='App\\Models\\SiteSearchQuery' --pretend` was attempted but the local MySQL schema has not yet run the new pending migration, so it cannot query `site_search_queries`. The migration was verified by the SQLite feature tests; apply pending migrations before rerunning the production-connection prune check.
- PHPUnit still reports the repository's pre-existing deprecated XML-schema warning.
