# Site Search Foundation Implementation Plan

**Goal:** Replace the duplicated page-only search with one tested, city-aware search over pages, doctors, and services, plus a safer live UI and anonymous query analytics. Meilisearch is intentionally deferred.

**Constraints:** Preserve existing Laravel global city scopes and city-aware URLs. Do not change the stack or `docs/APP_CONTEXT.md`. Use TDD. Existing unrelated baseline failures in `ExampleTest` and `MultiCityTest` are out of scope.

## Task 1: Search contract and page/block source — complete

**Files:**
- Create `app/Http/Requests/SiteSearchRequest.php`
- Create `app/Search/SiteSearchResult.php`
- Create `app/Services/SiteSearchService.php`
- Modify `app/Http/Controllers/SearchController.php`
- Create `tests/Feature/SiteSearch/PageSearchTest.php`

**Requirements:**
- Write failing tests first and run them before production changes.
- Validate both endpoint inputs through `SiteSearchRequest`: full search reads `q`, live search reads `query`, normalized whitespace, maximum 100 characters. Queries shorter than two effective characters return no results rather than running SQL.
- Implement `SiteSearchResult` with public result data: unique `key`, source `id`, `type`, Russian `typeLabel`, `title`, `url`, nullable `snippet`, and internal numeric `score`. Provide array serialization for controller/UI use.
- Implement `SiteSearchService::search(string $term, int $perPage = 30, int $page = 1): LengthAwarePaginator` and `suggest(string $term, int $limit = 5): Collection`.
- First source is active `Page` records and related `Block` title/body/payload. Preserve all model global scopes and group SQL conditions so inactive pages never leak.
- Tokenize Unicode text, remove duplicate tokens, require every effective token to occur somewhere in the same page entity, and escape `%`, `_`, and the SQL escape character in `LIKE` clauses.
- Ignore tokens representing the current city name/cases and phone-like tokens. If nothing effective remains, return an empty result set.
- Rank exact title, title prefix, phrase in title, all tokens in title, mixed title/supporting text, phrase in supporting text, then all tokens in supporting text. Use deterministic title/key tie breaking.
- Return each page once even if multiple blocks match. Resolve city SEO variables before display. Page URLs must stay city-aware.
- Build an approximately 180-character plain-text snippet from page body first and then matching block content; decode/strip HTML and traverse only scalar string values from block payload. Blade will escape it.
- Keep `SearchController` thin and make full/live endpoints call the same service. Preserve `q` in paginator links. Live output must be the prefix of the same ranking and include backward-compatible `id`, `title`, `handle` plus `key`, `type`, `type_label`.
- Cover inactive body/block exclusion, literal wildcard searching, non-contiguous multiword AND matching, missing-token exclusion, city scopes/URLs, deduplication, ranking, safe snippet, pagination query preservation, and full/live ranking consistency.
- Run `php artisan test --filter=PageSearchTest` and commit the task.

## Task 2: Direct doctor and service sources

**Files:**
- Modify `app/Services/SiteSearchService.php`
- Modify `app/Search/SiteSearchResult.php` only if the source contract needs a compatible extension
- Create `tests/Feature/SiteSearch/EntitySearchTest.php`

**Requirements:**
- Write failing tests first and run them before production changes.
- Add direct `Doctor` candidates using `publiclyVisible()` and the existing city global scope. Search full name, surname, name, speciality, job title, excerpt, and bio. Resolve city SEO variables. Link to the existing doctor `url` accessor. Prefer speciality/job/excerpt for the snippet.
- Add direct active `Service` candidates using the existing city global scope. Search own title and parent title. Parent services link to their UUID anchor on the city-aware `/services` page; child services link to their parent's UUID anchor. Include parent context in child snippets.
- Apply the same all-token rules and ranking tiers to every source. Exact/prefix/phrase matches in the primary title rank above supporting text. Add a small direct-entity bonus so exact doctor/service matches beat incidental page-body mentions without overriding a stronger tier.
- Merge all sources into one ranked list, deduplicate by `page:<id>`, `doctor:<id>`, `service:<id>`, retain same-title records from different types, and paginate in memory with correct total/current page/path/query.
- Cover doctor surname/full-name/speciality matching when absent from blocks, noindex exclusion, city scope and profile URL; parent/child service matching, inactive exclusion and child anchor; entity-vs-page ranking; unique keys and pagination totals.
- Run `php artisan test --filter=EntitySearchTest` plus `--filter=PageSearchTest` and commit the task.

## Task 3: Full-results and live-search UI

**Files:**
- Modify `resources/views/search/results.blade.php`
- Modify `resources/views/components/search.blade.php`
- Modify `resources/views/components/search-new.blade.php`
- Modify `resources/js/components/SearchLive/SearchLive.vue`
- Add focused frontend tests if the repository has an established harness; otherwise verify by build and backend response tests

**Requirements:**
- Render the mixed full result list with Russian type badges, linked title, and escaped snippet. Provide clear Russian validation, empty-query, and no-result states. Preserve the search query in the form and pagination.
- Render mixed live suggestions with type badges and use `result.key` as the Vue key. Do not show snippets in live results.
- Set `maxlength=100` and `autocomplete="off"` on search inputs.
- Keep the existing debounce and add Axios 1.10-compatible `AbortController` cancellation so stale responses cannot replace newer ones. Add loading/no-result states and predictable focus/blur behavior.
- Navigation must use the server-provided `handle`/URL and submitting must retain the current city-aware search route behavior.
- Run the focused backend search tests and `npm run build`, then commit the task.

## Task 4: Anonymous full-search analytics and retention

**Files:**
- Create migration for `site_search_queries`
- Create `app/Models/SiteSearchQuery.php`
- Create a small recorder service if it keeps the controller/search service focused
- Modify `app/Http/Controllers/SearchController.php`
- Modify `app/Console/Kernel.php`
- Create `tests/Feature/SiteSearch/SearchAnalyticsTest.php`

**Requirements:**
- Write failing tests first and run them before production changes.
- Store normalized query (varchar 100), nullable current `city_id`, `results_count`, and timestamps. Add indexes for `created_at` and `[city_id, created_at]`.
- Record only a submitted full `/search` request, never live suggestions. Store no IP, user, cookie/session identifier, or full URL.
- Skip queries containing an email address or a phone-like run of at least seven digits.
- Analytics must be best effort: report exceptions and never break search results.
- Make the model `MassPrunable` and schedule Laravel `model:prune` daily. Delete records older than 90 days.
- Cover full-only recording, normalized query/city/total, PII skipping, harmless storage failure, and pruning boundary.
- Run `php artisan test --filter=SearchAnalyticsTest` and all `SiteSearch` tests, then commit the task.

## Task 5: Integration verification

**Files:** No feature expansion. Only minimal fixes required by verification/review are allowed.

**Requirements:**
- Review the complete branch against this plan and repository instructions.
- Run all `tests/Feature/SiteSearch` tests, related city/SEO tests that are independently runnable, `npm run build`, a safe `model:prune --pretend` check if supported, and `git diff --check`.
- Run the full PHP test suite and compare failures with the recorded baseline (156 passed, 20 unrelated failures from `ExampleTest`/`MultiCityTest` due missing `cities` setup). Do not fix unrelated failures as part of this feature.
- Perform a final code-quality review for query grouping, wildcard escaping, scopes, city-aware URLs, XSS-safe snippets, deterministic ranking, analytics privacy, and backward-compatible live JSON.
- Commit only if verification requires fixes.
