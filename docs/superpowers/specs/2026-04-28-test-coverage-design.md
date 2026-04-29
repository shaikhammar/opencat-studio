# Test Coverage Design — OpenCAT Studio

**Date:** 2026-04-28
**Scope:** Phase 5 Track D gap-filling, Track A (test coverage)
**Status:** Approved

---

## Context

OpenCAT Studio currently has ~20% test coverage — auth and Fortify boilerplate only. No tests exist for the core CAT features: project management, file processing, translation editor, TM, glossary, MT, QA, or export. CLAUDE.md mandates that every change be programmatically tested.

This spec covers the full test build-out for all controllers, jobs, and API endpoints.

---

## Decisions

| Decision | Choice | Reason |
|----------|--------|--------|
| Test database | Dedicated Postgres (`opencat_studio_test`) with `RefreshDatabase` | Core TM fuzzy search uses `pg_trgm` — SQLite can't test it |
| Job testing strategy | Mock `FrameworkBridge` via Laravel container | Keeps the Laravel/catframework seam clean; tests DB writes and status transitions without real filters |
| Test organisation | Domain-by-domain | Each domain (projects, TM, etc.) ships factories + controller + job + API tests together as a reviewable chunk |

---

## Section 1 — Infrastructure

### Database

- Add to `phpunit.xml`:
  ```xml
  <env name="DB_CONNECTION" value="pgsql"/>
  <env name="DB_DATABASE" value="opencat_studio_test"/>
  ```
- Uncomment `RefreshDatabase` in `tests/Pest.php` — wraps each test in a rolled-back transaction
- One-time manual setup: `createdb opencat_studio_test && php artisan migrate --env=testing`

### Factories (all new)

| Factory | Key states |
|---------|-----------|
| `ProjectFactory` | `->withTm()` (also creates `TranslationMemory`) |
| `ProjectFileFactory` | `->pending()`, `->ready()`, `->error()` |
| `SegmentFactory` | `->untranslated()`, `->translated()`, `->draft()` |
| `TranslationMemoryFactory` | defaults only |
| `GlossaryFactory` | defaults only |
| `MtConfigFactory` | defaults only |
| `SettingFactory` | defaults only |

### Shared Pest helpers (added to `tests/Pest.php`)

```php
function actingAsUser(): User
// Creates a User — UserFactory::configure() auto-creates Team + Owner membership

function actingAsViewer(Project $project): User
// Creates a User and attaches them to $project->team as Viewer role
```

### Faking infrastructure

- `Queue::fake()` in controller tests that dispatch jobs — assert dispatched, don't run
- `Storage::fake()` in file upload and export tests
- `CACHE_STORE=array` in `phpunit.xml` — array driver resets between tests, no fake needed; QA result tests seed cache with `Cache::put()` directly

---

## Section 2 — Domain Coverage

### 2.1 Projects (`Feature/Projects/`)

**`ProjectControllerTest.php`**
- `GET /projects/create` renders create page
- `POST /projects` stores project, dispatches `ProcessUploadedFile` for each uploaded file, redirects to show
- `GET /projects/{project}` renders show page for owner; 403 for other team's user
- `PATCH /projects/{project}` updates project; 403 for Viewer role
- `DELETE /projects/{project}` archives project; 403 for Viewer role

**Policy coverage:** Owner/Admin pass; Viewer gets 403 on update/delete.

---

### 2.2 Files (`Feature/Projects/` + `Feature/Jobs/`)

**`FileControllerTest.php`**
- `POST /projects/{project}/files` stores file to Storage, creates `ProjectFile` with status=pending, dispatches `ProcessUploadedFile`
- `DELETE /projects/{project}/files/{file}` soft-deletes file; 403 for Viewer
- `GET /projects/{project}/files/{file}/status` returns JSON with current status

**`ProcessUploadedFileTest.php`** (mocks `FrameworkBridge`)
- Happy path: mocked filter + segmentation engine → segments inserted into DB, file status=ready, word/segment counts set
- `mtPrefill` option dispatches `PopulateMtSuggestions`
- Error path: filter throws → file status=error, error_message set, exception re-thrown

---

### 2.3 Editor / Segments (`Feature/Api/`)

**`SegmentControllerTest.php`**
- `GET /api/projects/{project}/files/{file}/segments` returns paginated segments (100/page default)
- `GET /api/projects/{project}/files/{file}/segments/{segment}` returns single segment
- `PATCH /api/projects/{project}/files/{file}/segments/{segment}` updates target_text + status, dispatches `WriteTmEntryJob` when status=translated

---

### 2.4 Translation Memory (`Feature/` + `Feature/Api/` + `Feature/Jobs/`)

**`TmControllerTest.php`**
- `GET /projects/{project}/tm` renders TM show page with entries
- `POST /projects/{project}/tm/import` validates TMX file, dispatches `ImportTmxJob`
- `GET /projects/{project}/tm/export` returns downloadable file
- `DELETE /projects/{project}/tm/entries/{entry}` removes entry; 403 for Viewer
- `GET /tm` renders global TM page

**`TmLookupControllerTest.php`**
- `GET /api/.../segments/{segment}/tm` returns fuzzy matches above threshold (seeds TM entries via direct DB insert — no TmEntry Eloquent model exists)
- `GET /api/projects/{project}/tm/search` returns concordance results

**`TmJobsTest.php`**
- `ImportTmxJob`: mocks `TmService::importTmx`, asserts Storage::delete called after import
- `WriteTmEntryJob`: mocks `TmService::addEntry`, asserts called with correct source/target/langs

---

### 2.5 Glossary (`Feature/`)

**`GlossaryControllerTest.php`**
- `GET /projects/{project}/glossary` renders glossary page
- `POST /projects/{project}/glossary/terms` adds term; 403 for Viewer
- `DELETE /projects/{project}/glossary/terms/{termId}` removes term
- `POST /projects/{project}/glossary/import` dispatches import
- `GET /glossary` renders global glossary page

---

### 2.6 MT (`Feature/Api/` + `Feature/Jobs/`)

**`MtControllerTest.php`**
- `POST /api/.../segments/{segment}/mt` returns MT suggestion when adapter configured
- Returns 422 when no MT adapter configured for project

**`PopulateMtSuggestionsTest.php`** (mocks `FrameworkBridge` + `MtService`)
- Skips gracefully when no adapter resolved (no DB writes, just logs)
- Calls `MtService::translate` + `EditorService::updateSegment` for each untranslated segment

---

### 2.7 QA & Export (`Feature/Api/` + `Feature/Jobs/` + `Feature/`)

**`QaRunControllerTest.php`**
- `POST /api/.../qa` dispatches `RunQaOnFile`, sets cache status=pending
- `GET /api/.../qa/results` returns cached results when status=ready; returns pending status when not ready

**`RunQaOnFileTest.php`** (mocks `FrameworkBridge`, `EditorService`)
- Mocked `QualityRunner` returns issues → cached under `qa_results_{file_id}` + status=ready

**`ExportControllerTest.php`**
- `POST /export` triggers export, stores file, sets `export_path` on `ProjectFile`
- `GET /export/download` streams the exported file

---

## Section 3 — Out of Scope

- Browser/JS/Playwright tests
- Arch tests (`arch()`)
- Smoke tests
- Settings controller tests (covered by existing Fortify tests pattern)

---

## Execution Order

1. Infrastructure (phpunit.xml, Pest.php, all factories, helpers)
2. Projects domain
3. Files + ProcessUploadedFile job
4. Editor/Segments
5. TM (controller + API + jobs)
6. Glossary
7. MT (controller + job)
8. QA + Export

Each step is independently committable.
