# Task 2 report: Direct doctor and service sources

## Status

Complete.

## TDD evidence

- RED: `php artisan test --filter=EntitySearchTest` initially failed because doctors and services were not search candidates.
- GREEN: doctors now use `publiclyVisible()` and city scopes; services use their existing city scopes, active status, parent context, and city-aware anchors.
- Debugging fix: executing the existing `publiclyVisible()` scope under SQLite exposed its MySQL-only `JSON_UNQUOTE` call. The scope now uses SQLite-compatible JSON extraction while preserving the existing MySQL expression.

## Changed files

- `app/Models/Doctor.php`
- `app/Services/SiteSearchService.php`
- `tests/Feature/SiteSearch/EntitySearchTest.php`
- `.superpowers/sdd/progress.md`
- `.superpowers/sdd/task-2-report.md`

## Verification

```text
php artisan test --filter=EntitySearchTest
PASS: 5 tests, 22 assertions

php artisan test --filter=PageSearchTest
PASS: 12 tests, 35 assertions

git diff --check
PASS
```

## Risks / follow-up

- SQLite has no Unicode-aware case-insensitive `LIKE`; direct entity candidates are therefore filtered in PHP under SQLite after public and city scopes. MySQL retains token filtering in SQL.
- PHPUnit still reports the pre-existing deprecated XML-schema warning.
