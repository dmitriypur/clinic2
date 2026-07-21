# Task 5 report: Integration verification

## Status

Complete. No feature-code changes were required.

## Verification

```text
php artisan test tests/Feature/SiteSearch
PASS: 24 tests, 82 assertions

php artisan test tests/Unit/SetCityMiddlewareTest.php tests/Unit/SetCityMiddlewareUtmRedirectTest.php tests/Unit/PageCitySeoVariablesTest.php tests/Unit/CityDetectionControllerTest.php
PASS: 17 tests, 75 assertions

php artisan view:cache
PASS

npm run build
PASS (Vite 5.4.19; existing unresolved runtime font-path and stale Browserslist-data warnings)

php artisan migrate --pretend
PASS: pending site_search_queries migration is syntactically generated, not applied

php artisan schedule:list
PASS: daily model:prune --model="App\\Models\\SiteSearchQuery" is registered

php artisan test
20 failed, 180 passed, 748 assertions — matches the recorded 20 known unrelated failures
in Feature\\ExampleTest and Feature\\MultiCityTest due to the missing SQLite cities table.
The 180 passes equal the 156-pass baseline plus 24 new SiteSearch tests.

git diff --check
PASS
```

`php artisan model:prune --model='App\\Models\\SiteSearchQuery' --pretend` was not run: the local configured database does not yet contain `site_search_queries`, and the task requires avoiding that unsafe query when the table is absent.

## Code inspection

- Page token clauses remain grouped beneath `active()`; page, block, doctor, and service queries preserve their existing city scopes.
- `%`, `_`, and `!` are escaped for all `LIKE` clauses; SQLite payload search is limited to scalar JSON text values.
- Result URLs use the current-city page/doctor helpers or `city_url('/services')`; full and live forms use `city_route`.
- Titles and snippets are converted to plain text and rendered through escaped Blade/Vue interpolation.
- Ranking uses fixed score tiers followed by lowercase title and result key tie-breakers.
- Analytics stores only normalized query, nullable city ID, and count; it skips blank, phone-like, and ASCII/Unicode e-mail input, and storage errors are isolated from search responses.
- Live JSON retains `id`, `title`, and `handle` while adding the documented mixed-result fields.
