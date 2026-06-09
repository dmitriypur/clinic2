# Article Reading Time Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Calculate and display article reading time from the page's `POST_TEXT` blocks.

**Architecture:** Add a computed `reading_time_minutes` attribute to `Page`. It reads the loaded `blocks` relation or queries it, extracts text from `POST_TEXT` block HTML, counts Unicode words, and rounds up at 200 words per minute with a minimum of one minute.

**Tech Stack:** Laravel 10, Eloquent attributes, PHPUnit, Blade.

---

### Task 1: Reading time attribute

**Files:**
- Create: `tests/Unit/PageReadingTimeTest.php`
- Modify: `app/Models/Page.php`

- [ ] **Step 1: Write failing model tests**

Cover multiple `POST_TEXT` blocks, HTML/entity cleanup, exclusion of other
block types, rounding at 200 words, and the one-minute minimum.

- [ ] **Step 2: Verify the tests fail**

Run: `php artisan test --filter=PageReadingTimeTest`

Expected: failure because `reading_time_minutes` does not exist.

- [ ] **Step 3: Implement the computed attribute**

Add `readingTimeMinutes(): Attribute` to `Page`, using Unicode word matching and
`ceil($wordCount / 200)`.

- [ ] **Step 4: Verify the tests pass**

Run: `php artisan test --filter=PageReadingTimeTest`

Expected: all focused tests pass.

### Task 2: Author block output

**Files:**
- Modify: `resources/views/components/block/author.blade.php`

- [ ] **Step 1: Replace the hard-coded value**

Read `$block->page->reading_time_minutes` and format `минута`, `минуты`, or
`минут` using Laravel's Russian pluralization helper.

- [ ] **Step 2: Verify syntax and focused tests**

Run: `php artisan test --filter=PageReadingTimeTest`

Expected: all focused tests pass.
