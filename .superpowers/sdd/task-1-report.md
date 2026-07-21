# Task 1 report: Search contract and page/block source

## Status

Complete.

## TDD evidence

- RED: `php artisan test --filter=PageSearchTest` initially failed because the legacy live endpoint did not expose `key`.
- RED: added coverage exposed literal `LIKE` wildcard matching, JSON payload candidate selection, phone token handling, and effective-term length handling.
- RED (review fix): `Киров лазерная коррекция` ranked a non-exact title above `лазерная коррекция`, because scoring and snippet selection used the raw term after city-token removal.
- RED (review fix): removing the broad non-null payload condition exposed that SQLite stores JSON Unicode escapes, so a raw payload `LIKE` did not search scalar JSON strings.
- GREEN (review fix): scoring and snippets use the phrase rebuilt from effective tokens; payload candidates use scalar JSON string matching (`json_tree` for SQLite, `JSON_SEARCH` for MySQL); a production-like page global scope is verified through `/live-search` with a foreign-city page.
- GREEN: `php artisan test --filter=PageSearchTest` passes with 12 tests and 35 assertions.

## Changed files

- `app/Http/Requests/SiteSearchRequest.php`
- `app/Search/SiteSearchResult.php`
- `app/Services/SiteSearchService.php`
- `app/Http/Controllers/SearchController.php`
- `tests/Feature/SiteSearch/PageSearchTest.php`
- `.superpowers/sdd/task-1-report.md`
- `docs/superpowers/plans/2026-07-21-site-search-foundation.md`
- `.superpowers/sdd/progress.md`

## Verification

```text
php artisan test --filter=PageSearchTest
PASS: 12 tests, 35 assertions

git diff --check
PASS
```

## Risks / follow-up

- The existing PHPunit configuration reports its pre-existing deprecated-schema warning.
- Task 3 will render the newly provided result `type_label` and `snippet` in the full and live UI.
