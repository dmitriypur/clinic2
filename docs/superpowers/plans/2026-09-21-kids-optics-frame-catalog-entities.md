# Kids Optics Frame Catalog Entities Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace temporary block repeater cards with a normalized, administrator-managed frame catalog and database-backed filtering/load-more.

**Architecture:** Mutable attributes use dedicated relational dictionaries; fixed gender values use a PHP enum stored as a JSON array. The block SSR-renders the first catalog page and Vue requests further or filtered pages from a validated read-only endpoint that renders the existing card partial.

**Tech Stack:** Laravel 10, Eloquent, Filament 3, Curator, Blade, Vue 2.7, Vite, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-21-kids-optics-frame-catalog-entities-design.md`

## Global Constraints

- Work only on `feature/kids-optics-frame-entities`.
- Do not add frame sizes.
- Do not add city bindings, detail pages, slugs, SEO fields, stock or prices.
- Do not duplicate card markup between Blade and JavaScript.
- Do not commit or merge without explicit user approval.
- Preserve the existing block type and visual layout.

## Review Focus

- A frame with two genders must match either single-gender filter and the unfiltered state.
- Selecting both gender filters must behave like no gender restriction.
- Multiple selected ages must match a frame belonging to any selected age.
- Disabled frames and disabled dictionary values must not leak into the public catalog or filters.
- An invalid or oversized API request must be rejected or bounded without an expensive query.

---

### Task 1: Domain schema and Eloquent model

**Files:**
- Create: `app/Enums/FrameGender.php`
- Create: `app/Models/Frame.php`
- Create: `app/Models/FrameBrand.php`
- Create: `app/Models/FrameColor.php`
- Create: `app/Models/FrameAgeGroup.php`
- Create: `database/migrations/2026_09_21_120000_create_frame_catalog_tables.php`
- Test: `tests/Feature/FrameCatalogModelTest.php`

**Interfaces:**
- Produces: `Frame::publicCatalog()`, `Frame::brand()`, `Frame::colors()`, `Frame::ageGroups()`, and ordered active dictionary scopes.
- Produces: `FrameGender::options(): array<string,string>` and `Frame::genderLabel(): string`.

- [ ] **Step 1: Write failing model tests**

Create tests that assert the tables and foreign keys exist, one brand/many colors/many ages persist, genders are normalized to allowed enum values, public catalog excludes inactive frames and sorts by `sort_order,id`, and deleting an in-use dictionary record is blocked by foreign keys.

- [ ] **Step 2: Verify RED**

Run: `php artisan test --filter=FrameCatalogModelTest`

Expected: FAIL because the models and tables do not exist.

- [ ] **Step 3: Implement schema and models**

Create `frames`, `frame_brands`, `frame_colors`, `frame_age_groups`, `frame_color`, and `frame_age_group`. Define typed relationships, casts and query scopes. Seed the four approved age groups in the migration and migrate legacy block payload cards without retaining runtime dependency on payload.

- [ ] **Step 4: Verify GREEN**

Run: `php artisan test --filter=FrameCatalogModelTest`

Expected: PASS.

### Task 2: Filament catalog administration

**Files:**
- Create: `app/Filament/Resources/FrameResource.php`
- Create: `app/Filament/Resources/FrameResource/Pages/ListFrames.php`
- Create: `app/Filament/Resources/FrameResource/Pages/CreateFrame.php`
- Create: `app/Filament/Resources/FrameResource/Pages/EditFrame.php`
- Create: equivalent resource and page files for `FrameBrand`, `FrameColor`, and `FrameAgeGroup`
- Test: `tests/Feature/FrameCatalogAdminTest.php`

**Interfaces:**
- Consumes: Eloquent models and relationships from Task 1.
- Produces: Filament resource forms and tables under navigation group `Каталог оправ`.

- [ ] **Step 1: Write failing resource tests**

Assert all resources resolve, the frame form contains brand, model, description, Curator image, multiple colors, multiple ages, multiple gender choices, sorting and independent flags, and no size or legacy repeater fields.

- [ ] **Step 2: Verify RED**

Run: `php artisan test --filter=FrameCatalogAdminTest`

Expected: FAIL because resources do not exist.

- [ ] **Step 3: Implement Filament resources**

Use relationship-backed preloaded selects, `CuratorUrlPicker`, validated HEX input, active toggles and deterministic sorting. Hide destructive actions for dictionaries so values are disabled instead of deleted.

- [ ] **Step 4: Verify GREEN**

Run: `php artisan test --filter=FrameCatalogAdminTest`

Expected: PASS.

### Task 3: Server-rendered catalog and API query

**Files:**
- Create: `app/Http/Controllers/Api/FrameCatalogController.php`
- Create: `app/Http/Requests/Api/FrameCatalogRequest.php`
- Create: `app/Services/FrameCatalogService.php`
- Modify: `routes/api.php`
- Modify: `app/Blocks/Definitions/KidsOpticsFrameCatalogDefinition.php`
- Modify: `app/Models/CuratorMedia.php`
- Modify: `resources/views/components/block/kids-optics-frame-catalog.blade.php`
- Modify: `resources/views/components/block/partials/kids-optics-frame-card.blade.php`
- Test: `tests/Feature/FrameCatalogApiTest.php`
- Test: `tests/Feature/Blocks/KidsOpticsFrameCatalogTest.php`

**Interfaces:**
- Consumes: `Frame::publicCatalog()` and model relations from Task 1.
- Produces: `GET /api/frame-catalog` accepting `offset`, `limit`, `ages[]`, `genders[]` and returning `{html,total,hasMore}`.
- Produces: `FrameCatalogService::page(array $ages, array $genders, int $offset, int $limit): array` and one shared presenter for SSR/API card data.

- [ ] **Step 1: Write failing API and block tests**

Cover initial six-item SSR, no repeater in the block form, eager-loaded sorted active data, age OR filtering, age+gender AND filtering, both-gender semantics, bounded limits, empty results and Curator usage detection through `frames.curator_media_id`.

- [ ] **Step 2: Verify RED**

Run: `php artisan test --filter='FrameCatalog(Api|Block)Test|KidsOpticsFrameCatalogTest'`

Expected: FAIL because the service and endpoint do not exist and the block still reads payload cards.

- [ ] **Step 3: Implement query, presenter and endpoint**

Move image/card projection into `FrameCatalogService`, reuse the existing Blade partial for both responses, limit each request to 12 items, and leave only title/button settings in the block definition.

- [ ] **Step 4: Verify GREEN**

Run: `php artisan test --filter='FrameCatalog(Api|Block)Test|KidsOpticsFrameCatalogTest'`

Expected: PASS.

### Task 4: Vue multi-filter and incremental loading

**Files:**
- Modify: `resources/js/components/FrameCatalog/index.js`
- Modify: `resources/views/components/block/kids-optics-frame-catalog.blade.php`
- Create: `tests/js/frameCatalog.test.mjs`
- Modify: `package.json`

**Interfaces:**
- Consumes: SSR state and API contract from Task 3.
- Produces: togglable multi-age and multi-gender filtering, responsive 4/6 page sizes and incremental HTML insertion.

- [ ] **Step 1: Write failing JavaScript tests**

Extract and test pure selection/request helpers for toggle removal, multi-age query parameters, both-gender normalization, responsive limits and stale-request protection.

- [ ] **Step 2: Verify RED**

Run: `node --test tests/js/frameCatalog.test.mjs`

Expected: FAIL because helpers and new behavior do not exist.

- [ ] **Step 3: Implement Vue behavior**

Replace client-side filtering of preloaded hidden markup with API requests, use `AbortController` or request sequencing to ignore stale responses, expose loading/error states, and preserve SSR cards until the first user action.

- [ ] **Step 4: Verify GREEN**

Run: `node --test tests/js/frameCatalog.test.mjs`

Expected: PASS.

### Task 5: Verification and local data migration

**Files:**
- Modify only files required by verified defects.

**Interfaces:**
- Consumes: Tasks 1–4.
- Produces: migrated local schema/data and a reviewable branch.

- [ ] **Step 1: Run focused backend tests**

Run: `php artisan test --filter='FrameCatalog|KidsOpticsFrameCatalog'`

Expected: PASS.

- [ ] **Step 2: Run frontend tests and production build**

Run: `npm run test:frontend && npm run build`

Expected: PASS with no compile errors.

- [ ] **Step 3: Run migrations locally**

Run: `php artisan migrate`

Expected: migration succeeds and the existing `/detskaia-optika` block reads migrated catalog records.

- [ ] **Step 4: Inspect final diff**

Run: `git diff --check && git status --short`

Expected: no whitespace errors, secrets, generated assets or unrelated changes.

