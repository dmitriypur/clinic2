# Task 5 report: Integration verification

## Status

Complete, including the consolidated final-review fixes.

## Final-review fix evidence

- RED: five focused regressions failed for the expected reasons: MySQL payload SQL had no explicit CI collation; children of inactive/foreign parents remained searchable; category queries increased with page result count; a non-matching page body displaced a matching block snippet; encoded markup survived plain-text conversion.
- GREEN: all five focused regressions pass (5 tests, 19 assertions).
- MySQL 5.7 proof: a read-only `SELECT` on local MySQL `5.7.39` searched `JSON_OBJECT('copy', 'ЛАЗЕРНАЯ КОРРЕКЦИЯ')` with the lowercase bound pattern `%лазерная%`. Plain `JSON_SEARCH` returned `NULL`; `CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci` returned `"$.copy"`. The automated driver/SQL assertion locks the same expression while SQLite retains its `json_tree` path.
- Active parent services remain searchable. A child now requires an active parent selected through a separate `Service` subquery, so the parent receives the same global city scopes without relying on a self-relation alias. Tests exclude children of inactive and foreign-city parents.
- Pages eager-load `category` together with `blocks`; the regression asserts one category query for both one and three matching categorized pages.
- Page snippets use body only when it matches all tokens, otherwise the matching block, then a fallback. HTML entities are decoded before tags are removed.

## Verification

```text
php artisan test tests/Feature/SiteSearch
PASS: 29 tests, 101 assertions

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
20 failed, 185 passed, 767 assertions — matches the recorded 20 known unrelated failures
in Feature\\ExampleTest and Feature\\MultiCityTest due to the missing SQLite cities table.
The five additional passing tests are the final-review regressions.

git diff --check
PASS
```

`php artisan model:prune --model='App\\Models\\SiteSearchQuery' --pretend` was not run: the local configured database does not yet contain `site_search_queries`, and the task requires avoiding that unsafe query when the table is absent.

## Code inspection

- Page token clauses remain grouped beneath `active()`; page, block, doctor, and service queries preserve their existing city scopes.
- `%`, `_`, and `!` are escaped for all `LIKE` clauses; SQLite payload search is limited to scalar JSON text values and MySQL JSON search applies `utf8mb4_unicode_ci` to its bound pattern.
- Active child services require a visible active parent under the same global city scopes; top-level parent services remain eligible.
- Page `blocks` and `category` are eager-loaded, preventing category URL N+1 queries.
- Result URLs use the current-city page/doctor helpers or `city_url('/services')`; full and live forms use `city_route`.
- Titles and snippets decode entities before stripping tags and are rendered through escaped Blade/Vue interpolation; snippets prefer a matching source.
- Ranking uses fixed score tiers followed by lowercase title and result key tie-breakers.
- Analytics stores only normalized query, nullable city ID, and count; it skips blank, phone-like, and ASCII/Unicode e-mail input, and storage errors are isolated from search responses.
- Live JSON retains `id`, `title`, and `handle` while adding the documented mixed-result fields.
