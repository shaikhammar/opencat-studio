# Test Coverage Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a complete Pest feature-test suite covering all controllers, jobs, and API endpoints in OpenCAT Studio, raising coverage from ~20% to ~90%.

**Architecture:** Domain-by-domain test build-out — each domain ships factories + controller + job + API tests together. All catframework dependencies mocked via `FrameworkBridge`. Tests run against a dedicated `opencat_studio_test` Postgres database with `RefreshDatabase`.

**Tech Stack:** PHP 8.4, Pest v4, Laravel 13, PostgreSQL 16, Mockery, `Queue::fake()`, `Storage::fake()`

---

## File Map

**Modified:**
- `phpunit.xml` — switch to Postgres test DB, keep `CACHE_STORE=array`
- `tests/Pest.php` — enable `RefreshDatabase`, add `actingAsUser()` + `actingAsViewer()` helpers

**Created (factories):**
- `database/factories/ProjectFactory.php`
- `database/factories/ProjectFileFactory.php`
- `database/factories/SegmentFactory.php`
- `database/factories/TranslationMemoryFactory.php`
- `database/factories/GlossaryFactory.php`
- `database/factories/MtConfigFactory.php`
- `database/factories/SettingFactory.php`

**Created (tests):**
- `tests/Feature/Projects/ProjectControllerTest.php`
- `tests/Feature/Projects/FileControllerTest.php`
- `tests/Feature/Jobs/ProcessUploadedFileTest.php`
- `tests/Feature/Api/SegmentControllerTest.php`
- `tests/Feature/TmControllerTest.php`
- `tests/Feature/Api/TmLookupControllerTest.php`
- `tests/Feature/Jobs/TmJobsTest.php`
- `tests/Feature/GlossaryControllerTest.php`
- `tests/Feature/Api/MtControllerTest.php`
- `tests/Feature/Jobs/PopulateMtSuggestionsTest.php`
- `tests/Feature/Api/QaRunControllerTest.php`
- `tests/Feature/Jobs/RunQaOnFileTest.php`
- `tests/Feature/ExportControllerTest.php`

---

## Task 1: Test Infrastructure

**Files:**
- Modify: `phpunit.xml`
- Modify: `tests/Pest.php`

- [ ] **Step 1: Create the Postgres test database**

Run in terminal (one-time, manual):
```bash
createdb opencat_studio_test
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan migrate --env=testing
```

Expected: migrations run against `opencat_studio_test` with no errors.

- [ ] **Step 2: Update `phpunit.xml` to use Postgres test DB**

Replace the `<php>` block in `phpunit.xml` with:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_MAINTENANCE_DRIVER" value="file"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="BROADCAST_CONNECTION" value="null"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="DB_CONNECTION" value="pgsql"/>
        <env name="DB_DATABASE" value="opencat_studio_test"/>
        <env name="DB_URL" value=""/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
        <env name="NIGHTWATCH_ENABLED" value="false"/>
    </php>
</phpunit>
```

- [ ] **Step 3: Enable `RefreshDatabase` and add helpers to `tests/Pest.php`**

Replace `tests/Pest.php` with:

```php
<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function actingAsUser(): User
{
    $user = User::factory()->create();
    test()->actingAs($user);

    return $user;
}

function actingAsViewer(Project $project): User
{
    $viewer = User::factory()->create();
    test()->actingAs($viewer);

    return $viewer;
}

function something()
{
    // ..
}
```

- [ ] **Step 4: Run the existing tests to confirm baseline passes**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact
```

Expected: all previously-passing tests still pass (auth + dashboard + settings + teams).

- [ ] **Step 5: Commit**

```bash
git add phpunit.xml tests/Pest.php
git commit -m "test: switch to Postgres test DB and enable RefreshDatabase"
```

---

## Task 2: Model Factories

**Files:**
- Create: `database/factories/ProjectFactory.php`
- Create: `database/factories/ProjectFileFactory.php`
- Create: `database/factories/SegmentFactory.php`
- Create: `database/factories/TranslationMemoryFactory.php`
- Create: `database/factories/GlossaryFactory.php`
- Create: `database/factories/MtConfigFactory.php`
- Create: `database/factories/SettingFactory.php`

- [ ] **Step 1: Create `database/factories/ProjectFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => null,
            'source_lang' => 'en',
            'target_lang' => 'es',
            'status' => 'active',
            'use_global_tm' => false,
            'mt_prefill' => false,
            'tm_min_match_pct' => 75,
            'char_limit_per_segment' => null,
            'char_limit_warning_pct' => 80,
        ];
    }
}
```

- [ ] **Step 2: Create `database/factories/ProjectFileFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\ProjectFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectFile>
 */
class ProjectFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'original_name' => 'test.docx',
            'storage_path' => 'uploads/test/source.docx',
            'file_format' => 'docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size_bytes' => 1024,
            'status' => 'pending',
            'word_count' => null,
            'segment_count' => null,
            'translated_count' => 0,
            'error_message' => null,
        ];
    }

    public function ready(): static
    {
        return $this->state([
            'status' => 'ready',
            'segment_count' => 10,
            'word_count' => 100,
            'translated_count' => 0,
            'processed_at' => now(),
        ]);
    }

    public function error(): static
    {
        return $this->state([
            'status' => 'error',
            'error_message' => 'Processing failed',
        ]);
    }
}
```

- [ ] **Step 3: Create `database/factories/SegmentFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\Segment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Segment>
 */
class SegmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'segment_number' => 1,
            'source_text' => fake()->sentence(),
            'target_text' => null,
            'source_tags' => [],
            'target_tags' => [],
            'status' => 'untranslated',
            'word_count' => 5,
            'char_count' => 30,
            'locked' => false,
            'bookmarked' => false,
        ];
    }

    public function translated(): static
    {
        return $this->state(fn () => [
            'target_text' => fake()->sentence(),
            'status' => 'translated',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'target_text' => fake()->sentence(),
            'status' => 'draft',
        ]);
    }
}
```

- [ ] **Step 4: Create `database/factories/TranslationMemoryFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\TranslationMemory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TranslationMemory>
 */
class TranslationMemoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Test TM',
            'source_lang' => 'en',
            'target_lang' => 'es',
            'entry_count' => 0,
            'is_global' => false,
        ];
    }
}
```

- [ ] **Step 5: Create `database/factories/GlossaryFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\Glossary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Glossary>
 */
class GlossaryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Test Glossary',
            'source_lang' => 'en',
            'target_lang' => 'es',
            'term_count' => 0,
            'sqlite_path' => 'glossaries/test/test.db',
            'is_global' => false,
        ];
    }
}
```

- [ ] **Step 6: Create `database/factories/MtConfigFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\MtConfig;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends Factory<MtConfig>
 */
class MtConfigFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider' => 'deepl',
            'api_key_enc' => Crypt::encryptString('test-api-key'),
            'is_active' => true,
            'usage_monthly_chars' => 0,
        ];
    }
}
```

- [ ] **Step 7: Create `database/factories/SettingFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => 'default_mt_provider',
            'value' => 'deepl',
        ];
    }
}
```

- [ ] **Step 8: Verify factories are syntactically valid**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact
```

Expected: existing tests still pass, no factory errors.

- [ ] **Step 9: Commit**

```bash
git add database/factories/
git commit -m "test: add model factories for Project, ProjectFile, Segment, TM, Glossary, MtConfig, Setting"
```

---

## Task 3: Project Controller Tests

**Files:**
- Create: `tests/Feature/Projects/ProjectControllerTest.php`

- [ ] **Step 1: Create `tests/Feature/Projects/ProjectControllerTest.php`**

```php
<?php

use App\Jobs\ProcessUploadedFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('guests are redirected from create page', function () {
    $this->get(route('projects.create'))->assertRedirect(route('login'));
});

test('create page renders for authenticated user', function () {
    actingAsUser();

    $this->get(route('projects.create'))->assertOk();
});

test('owner can store a project', function () {
    Queue::fake();
    $user = actingAsUser();

    $this->post(route('projects.store'), [
        'name' => 'My Translation Project',
        'source_lang' => 'en',
        'target_lang' => 'es',
        'use_global_tm' => false,
        'create_project_tm' => false,
        'create_project_glossary' => false,
        'mt_prefill' => false,
        'tm_min_match_pct' => 75,
        'char_limit_warning_pct' => 80,
    ])->assertRedirect();

    expect(Project::count())->toBe(1);
    expect(Project::first()->name)->toBe('My Translation Project');
    expect(Project::first()->user_id)->toBe($user->id);
});

test('store with files dispatches ProcessUploadedFile for each file', function () {
    Queue::fake();
    \Illuminate\Support\Facades\Storage::fake();
    $user = actingAsUser();

    $file = \Illuminate\Http\UploadedFile::fake()->create('doc.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $this->post(route('projects.store'), [
        'name' => 'Project With File',
        'source_lang' => 'en',
        'target_lang' => 'de',
        'use_global_tm' => false,
        'create_project_tm' => false,
        'create_project_glossary' => false,
        'mt_prefill' => false,
        'tm_min_match_pct' => 75,
        'char_limit_warning_pct' => 80,
        'files' => [$file],
    ])->assertRedirect();

    Queue::assertDispatched(ProcessUploadedFile::class);
});

test('project show renders for owner', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $this->get(route('projects.show', $project))->assertOk();
});

test('project show returns 403 for non-owner', function () {
    actingAsUser();
    $owner = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id, 'team_id' => $owner->team_id]);

    $this->get(route('projects.show', $project))->assertForbidden();
});

test('owner can update a project', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $this->patch(route('projects.update', $project), [
        'name' => 'Renamed Project',
        'source_lang' => 'en',
        'target_lang' => 'fr',
        'tm_min_match_pct' => 75,
        'char_limit_warning_pct' => 80,
    ])->assertRedirect(route('projects.show', $project));

    expect($project->fresh()->name)->toBe('Renamed Project');
});

test('non-owner cannot update a project', function () {
    actingAsUser();
    $owner = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id, 'team_id' => $owner->team_id]);

    $this->patch(route('projects.update', $project), ['name' => 'Hacked'])->assertForbidden();
});

test('owner can archive a project', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $this->delete(route('projects.destroy', $project))
        ->assertRedirect(route('dashboard'));

    expect($project->fresh()->status)->toBe('archived');
});

test('non-owner cannot archive a project', function () {
    actingAsUser();
    $owner = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id, 'team_id' => $owner->team_id]);

    $this->delete(route('projects.destroy', $project))->assertForbidden();
});
```

- [ ] **Step 2: Run the tests**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact --filter=ProjectControllerTest
```

Expected: all 9 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Projects/ProjectControllerTest.php
git commit -m "test: ProjectController — CRUD routes and policy enforcement"
```

---

## Task 4: File Controller Tests

**Files:**
- Create: `tests/Feature/Projects/FileControllerTest.php`

- [ ] **Step 1: Create `tests/Feature/Projects/FileControllerTest.php`**

```php
<?php

use App\Jobs\ProcessUploadedFile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('owner can upload a file and job is dispatched', function () {
    Queue::fake();
    Storage::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $upload = UploadedFile::fake()->create('document.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $this->postJson(route('files.store', $project), ['file' => $upload])
        ->assertOk()
        ->assertJsonStructure(['fileId', 'status']);

    Queue::assertDispatched(ProcessUploadedFile::class);
    expect(ProjectFile::count())->toBe(1);
    expect(ProjectFile::first()->status)->toBe('pending');
});

test('non-owner cannot upload a file', function () {
    Storage::fake();
    actingAsUser();
    $owner = \App\Models\User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id, 'team_id' => $owner->team_id]);
    $upload = UploadedFile::fake()->create('doc.docx', 10);

    $this->postJson(route('files.store', $project), ['file' => $upload])->assertForbidden();
});

test('file status endpoint returns current status', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);

    $this->getJson(route('files.status', [$project, $file]))
        ->assertOk()
        ->assertJsonFragment(['status' => 'ready'])
        ->assertJsonStructure(['id', 'status', 'wordCount', 'segmentCount', 'translatedCount', 'progress']);
});

test('owner can delete a file and its segments', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    Segment::factory()->count(3)->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $this->delete(route('files.destroy', [$project, $file]))
        ->assertRedirect(route('projects.show', $project));

    expect(ProjectFile::find($file->id))->toBeNull();
    expect(Segment::where('file_id', $file->id)->count())->toBe(0);
});
```

- [ ] **Step 2: Run the tests**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact --filter=FileControllerTest
```

Expected: all 4 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Projects/FileControllerTest.php
git commit -m "test: FileController — upload, status, delete"
```

---

## Task 5: ProcessUploadedFile Job Tests

**Files:**
- Create: `tests/Feature/Jobs/ProcessUploadedFileTest.php`

- [ ] **Step 1: Create `tests/Feature/Jobs/ProcessUploadedFileTest.php`**

```php
<?php

use App\Jobs\PopulateMtSuggestions;
use App\Jobs\ProcessUploadedFile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Support\FrameworkBridge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

function makeBridgeMock(string $sourceText = 'Hello world'): array
{
    $pair = Mockery::mock();
    $pair->shouldReceive('getSourceText')->andReturn($sourceText);
    $pair->shouldReceive('getSourceTags')->andReturn([]);

    $document = Mockery::mock();
    $document->shouldReceive('getSegmentPairs')->andReturn([$pair]);

    $extractResult = (object) ['document' => $document, 'skeleton' => 'skeleton-bytes'];

    $filter = Mockery::mock();
    $filter->shouldReceive('extract')->andReturn($extractResult);

    $engine = Mockery::mock();
    $engine->shouldReceive('segment');

    $bridge = Mockery::mock(FrameworkBridge::class);
    $bridge->shouldReceive('makeFileFilter')->with('docx')->andReturn($filter);
    $bridge->shouldReceive('makeSegmentationEngine')->andReturn($engine);

    return [$bridge, $document];
}

function makeFileRecord(): array
{
    Storage::fake();
    $user = \App\Models\User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'storage_path' => 'uploads/test/source.docx',
        'file_format' => 'docx',
        'status' => 'pending',
    ]);
    Storage::put('uploads/test/source.docx', 'fake-file-content');

    return [$file, $project];
}

test('happy path: inserts segments and sets file status to ready', function () {
    Queue::fake();
    [$bridge] = makeBridgeMock('Hello world');
    [$file] = makeFileRecord();

    (new ProcessUploadedFile($file))->handle($bridge);

    $file->refresh();
    expect($file->status)->toBe('ready');
    expect($file->segment_count)->toBe(1);
    expect($file->word_count)->toBe(2);
    expect(DB::table('segments')->count())->toBe(1);
    expect(DB::table('segments')->first()->source_text)->toBe('Hello world');
});

test('error path: sets file status to error and rethrows exception', function () {
    Storage::fake();
    $user = \App\Models\User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'storage_path' => 'uploads/test/source.docx',
        'file_format' => 'docx',
    ]);
    Storage::put('uploads/test/source.docx', 'fake-content');

    $bridge = Mockery::mock(FrameworkBridge::class);
    $bridge->shouldReceive('makeFileFilter')->andThrow(new \RuntimeException('Unsupported format: docx'));

    expect(fn () => (new ProcessUploadedFile($file))->handle($bridge))
        ->toThrow(\RuntimeException::class, 'Unsupported format: docx');

    expect($file->fresh()->status)->toBe('error');
    expect($file->fresh()->error_message)->toBe('Unsupported format: docx');
});

test('mtPrefill option dispatches PopulateMtSuggestions after processing', function () {
    Queue::fake();
    [$bridge] = makeBridgeMock();
    [$file] = makeFileRecord();

    (new ProcessUploadedFile($file, ['mtPrefill' => true]))->handle($bridge);

    Queue::assertDispatched(PopulateMtSuggestions::class, fn ($job) => $job->file->id === $file->id);
});

test('skeleton is stored to the filesystem', function () {
    Queue::fake();
    [$bridge] = makeBridgeMock();
    [$file] = makeFileRecord();

    (new ProcessUploadedFile($file))->handle($bridge);

    $file->refresh();
    expect($file->skeleton_path)->not->toBeNull();
    expect(Storage::exists($file->skeleton_path))->toBeTrue();
});
```

- [ ] **Step 2: Run the tests**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact --filter=ProcessUploadedFileTest
```

Expected: all 4 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Jobs/ProcessUploadedFileTest.php
git commit -m "test: ProcessUploadedFile job — happy path, error path, mtPrefill, skeleton storage"
```

---

## Task 6: Segment API Tests

**Files:**
- Create: `tests/Feature/Api/SegmentControllerTest.php`

- [ ] **Step 1: Create `tests/Feature/Api/SegmentControllerTest.php`**

```php
<?php

use App\Jobs\WriteTmEntryJob;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;
use App\Models\TranslationMemory;
use Illuminate\Support\Facades\Queue;

test('index returns paginated segments for file owner', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    Segment::factory()->count(3)->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $this->getJson("/api/projects/{$project->id}/files/{$file->id}/segments")
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data', 'meta' => ['page', 'limit', 'total', 'hasMore']]);
});

test('index filters segments by status', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    Segment::factory()->count(2)->create(['file_id' => $file->id, 'project_id' => $project->id, 'status' => 'untranslated']);
    Segment::factory()->translated()->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $this->getJson("/api/projects/{$project->id}/files/{$file->id}/segments?status=untranslated")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('show returns a single segment', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $this->getJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $segment->id]);
});

test('update saves target text and status', function () {
    Queue::fake();
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $this->patchJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}", [
        'target_text' => 'Hola mundo',
        'target_tags' => [],
        'status' => 'draft',
    ])->assertOk();

    expect($segment->fresh()->target_text)->toBe('Hola mundo');
    expect($segment->fresh()->status)->toBe('draft');
});

test('update dispatches WriteTmEntryJob when status becomes translated and TM exists', function () {
    Queue::fake();
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    TranslationMemory::factory()->create(['project_id' => $project->id, 'team_id' => $user->team_id, 'is_global' => false]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $this->patchJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}", [
        'target_text' => 'Hola',
        'target_tags' => [],
        'status' => 'translated',
    ])->assertOk();

    Queue::assertDispatched(WriteTmEntryJob::class);
});

test('update does not dispatch WriteTmEntryJob when no TM exists', function () {
    Queue::fake();
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $this->patchJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}", [
        'target_text' => 'Hola',
        'target_tags' => [],
        'status' => 'translated',
    ])->assertOk();

    Queue::assertNotDispatched(WriteTmEntryJob::class);
});

test('unauthenticated requests are rejected', function () {
    $owner = \App\Models\User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id, 'team_id' => $owner->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $owner->id]);

    $this->getJson("/api/projects/{$project->id}/files/{$file->id}/segments")
        ->assertUnauthorized();
});
```

- [ ] **Step 2: Run the tests**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact --filter=SegmentControllerTest
```

Expected: all 7 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Api/SegmentControllerTest.php
git commit -m "test: SegmentController API — pagination, filtering, update, WriteTmEntryJob dispatch"
```

---

## Task 7: TM Controller Tests

**Files:**
- Create: `tests/Feature/TmControllerTest.php`

- [ ] **Step 1: Create `tests/Feature/TmControllerTest.php`**

```php
<?php

use App\Jobs\ImportTmxJob;
use App\Models\Project;
use App\Models\TranslationMemory;
use App\Services\TmService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('TM show page renders for project owner', function () {
    $this->mock(TmService::class, fn ($m) => $m->shouldReceive('paginate')->andReturn([]));

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    TranslationMemory::factory()->create(['project_id' => $project->id, 'team_id' => $user->team_id]);

    $this->get(route('tm.show', $project))->assertOk();
});

test('TM import dispatches ImportTmxJob', function () {
    Queue::fake();
    Storage::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    TranslationMemory::factory()->create(['project_id' => $project->id, 'team_id' => $user->team_id]);
    $tmx = UploadedFile::fake()->create('memory.tmx', 5, 'text/xml');

    $this->post(route('tm.import', $project), ['file' => $tmx])
        ->assertRedirect();

    Queue::assertDispatched(ImportTmxJob::class);
});

test('non-owner cannot import TM', function () {
    Queue::fake();
    Storage::fake();
    actingAsUser();
    $owner = \App\Models\User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id, 'team_id' => $owner->team_id]);
    $tmx = UploadedFile::fake()->create('memory.tmx', 5, 'text/xml');

    $this->post(route('tm.import', $project), ['file' => $tmx])->assertForbidden();
});

test('TM export returns a download response', function () {
    $this->mock(TmService::class, function ($m) {
        Storage::fake();
        Storage::put('exports/tm_export.tmx', '<tmx/>');
        $m->shouldReceive('exportTmx')->andReturn('exports/tm_export.tmx');
    });

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    TranslationMemory::factory()->create(['project_id' => $project->id, 'team_id' => $user->team_id]);

    $this->get(route('tm.export', $project))->assertOk();
});

test('owner can delete a TM entry', function () {
    $this->mock(TmService::class, fn ($m) => $m->shouldReceive('deleteEntry')->once());

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    TranslationMemory::factory()->create(['project_id' => $project->id, 'team_id' => $user->team_id]);

    $this->delete(route('tm.entries.destroy', [$project, 99]))->assertRedirect();
});

test('global TM page renders', function () {
    $this->mock(TmService::class, fn ($m) => $m->shouldReceive('paginate')->andReturn([]));

    $user = actingAsUser();
    TranslationMemory::factory()->create(['team_id' => $user->team_id, 'is_global' => true]);

    $this->get(route('tm.global'))->assertOk();
});
```

- [ ] **Step 2: Run the tests**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact --filter=TmControllerTest
```

Expected: all 6 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/TmControllerTest.php
git commit -m "test: TmController — show, import, export, delete entry, global TM page"
```

---

## Task 8: TM Lookup API + TM Job Tests

**Files:**
- Create: `tests/Feature/Api/TmLookupControllerTest.php`
- Create: `tests/Feature/Jobs/TmJobsTest.php`

- [ ] **Step 1: Create `tests/Feature/Api/TmLookupControllerTest.php`**

```php
<?php

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;
use App\Models\TranslationMemory;
use App\Services\TmService;

test('lookup returns matches from TM service', function () {
    $matches = [['source' => 'Hello', 'target' => 'Hola', 'score' => 100]];
    $this->mock(TmService::class, fn ($m) => $m->shouldReceive('lookup')->andReturn($matches));

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    TranslationMemory::factory()->create(['project_id' => $project->id, 'team_id' => $user->team_id, 'is_global' => false]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $this->getJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}/tm")
        ->assertOk()
        ->assertJson($matches);
});

test('lookup returns empty array when project has no TM', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $this->getJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}/tm")
        ->assertOk()
        ->assertJson([]);
});

test('concordance returns search results', function () {
    $results = [['source' => 'Hello world', 'target' => 'Hola mundo']];
    $this->mock(TmService::class, fn ($m) => $m->shouldReceive('search')->andReturn($results));

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    TranslationMemory::factory()->create(['project_id' => $project->id, 'team_id' => $user->team_id, 'is_global' => false]);

    $this->getJson("/api/projects/{$project->id}/tm/search?q=Hello")
        ->assertOk()
        ->assertJson($results);
});

test('concordance returns empty when no TM', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $this->getJson("/api/projects/{$project->id}/tm/search?q=Hello")
        ->assertOk()
        ->assertJson([]);
});
```

- [ ] **Step 2: Create `tests/Feature/Jobs/TmJobsTest.php`**

```php
<?php

use App\Jobs\ImportTmxJob;
use App\Jobs\WriteTmEntryJob;
use App\Models\Project;
use App\Models\Segment;
use App\Models\TranslationMemory;
use App\Services\TmService;
use Illuminate\Support\Facades\Storage;

test('ImportTmxJob calls importTmx on TmService and deletes the temp file', function () {
    Storage::fake();
    Storage::put('tmp/tmx/memory.tmx', '<tmx/>');

    $user = \App\Models\User::factory()->create();
    $tm = TranslationMemory::factory()->create(['team_id' => $user->team_id]);

    $tmService = $this->mock(TmService::class);
    $tmService->shouldReceive('importTmx')->once()->with(Storage::path('tmp/tmx/memory.tmx'), $tm);

    (new ImportTmxJob('tmp/tmx/memory.tmx', $tm))->handle($tmService);

    expect(Storage::exists('tmp/tmx/memory.tmx'))->toBeFalse();
});

test('WriteTmEntryJob calls addEntry on TmService with correct arguments', function () {
    $user = \App\Models\User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'team_id' => $user->team_id,
        'source_lang' => 'en',
        'target_lang' => 'de',
    ]);
    $file = \App\Models\ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->translated()->create(['file_id' => $file->id, 'project_id' => $project->id]);
    $tm = TranslationMemory::factory()->create(['project_id' => $project->id, 'team_id' => $user->team_id]);

    $tmService = $this->mock(TmService::class);
    $tmService->shouldReceive('addEntry')
        ->once()
        ->with($segment->source_text, $segment->target_text, 'en', 'de', $tm);

    (new WriteTmEntryJob($segment, $tm))->handle($tmService);
});
```

- [ ] **Step 3: Run the tests**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact --filter="TmLookupControllerTest|TmJobsTest"
```

Expected: all 6 tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Api/TmLookupControllerTest.php tests/Feature/Jobs/TmJobsTest.php
git commit -m "test: TM lookup API and ImportTmxJob/WriteTmEntryJob"
```

---

## Task 9: Glossary Controller Tests

**Files:**
- Create: `tests/Feature/GlossaryControllerTest.php`

- [ ] **Step 1: Create `tests/Feature/GlossaryControllerTest.php`**

```php
<?php

use App\Models\Glossary;
use App\Models\Project;
use App\Services\GlossaryService;

test('glossary show page renders for project owner', function () {
    $this->mock(GlossaryService::class, fn ($m) => $m->shouldReceive('paginate')->andReturn([]));

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    Glossary::factory()->create(['project_id' => $project->id, 'team_id' => $user->team_id]);

    $this->get(route('glossary.show', $project))->assertOk();
});

test('owner can add a glossary term', function () {
    $this->mock(GlossaryService::class, function ($m) {
        $m->shouldReceive('createForProject')->andReturn(Glossary::factory()->make());
        $m->shouldReceive('addTerm')->once();
    });

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $this->post(route('glossary.terms.store', $project), [
        'source' => 'Hello',
        'target' => 'Hola',
        'domain' => 'general',
    ])->assertRedirect();
});

test('non-owner cannot add a glossary term', function () {
    actingAsUser();
    $owner = \App\Models\User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id, 'team_id' => $owner->team_id]);

    $this->post(route('glossary.terms.store', $project), [
        'source' => 'Hello',
        'target' => 'Hola',
    ])->assertForbidden();
});

test('owner can delete a glossary term', function () {
    $this->mock(GlossaryService::class, fn ($m) => $m->shouldReceive('deleteTerm')->once());

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    Glossary::factory()->create(['project_id' => $project->id, 'team_id' => $user->team_id]);

    $this->delete(route('glossary.terms.destroy', [$project, 42]))->assertRedirect();
});

test('global glossary page renders', function () {
    $this->mock(GlossaryService::class, fn ($m) => $m->shouldReceive('paginate')->andReturn([]));

    $user = actingAsUser();
    Glossary::factory()->create(['team_id' => $user->team_id, 'is_global' => true]);

    $this->get(route('glossary.global'))->assertOk();
});
```

- [ ] **Step 2: Run the tests**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact --filter=GlossaryControllerTest
```

Expected: all 5 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/GlossaryControllerTest.php
git commit -m "test: GlossaryController — show, add term, delete term, global page, policy"
```

---

## Task 10: MT Controller + PopulateMtSuggestions Job Tests

**Files:**
- Create: `tests/Feature/Api/MtControllerTest.php`
- Create: `tests/Feature/Jobs/PopulateMtSuggestionsTest.php`

- [ ] **Step 1: Create `tests/Feature/Api/MtControllerTest.php`**

```php
<?php

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;
use App\Services\MtService;

test('suggest returns MT translation when adapter is configured', function () {
    $this->mock(MtService::class, function ($m) {
        $adapter = Mockery::mock();
        $m->shouldReceive('resolveAdapter')->andReturn($adapter);
        $m->shouldReceive('translate')->andReturn([
            'suggestion' => 'Hola mundo',
            'provider' => 'deepl',
            'tagWarning' => false,
        ]);
    });

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $this->postJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}/mt")
        ->assertOk()
        ->assertJsonFragment(['suggestion' => 'Hola mundo', 'provider' => 'deepl']);
});

test('suggest returns 422 when no MT adapter is configured', function () {
    $this->mock(MtService::class, fn ($m) => $m->shouldReceive('resolveAdapter')->andReturn(null));

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $this->postJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}/mt")
        ->assertUnprocessable()
        ->assertJsonFragment(['error' => 'No MT provider configured.']);
});
```

- [ ] **Step 2: Create `tests/Feature/Jobs/PopulateMtSuggestionsTest.php`**

```php
<?php

use App\Jobs\PopulateMtSuggestions;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;
use App\Services\EditorService;
use App\Services\MtService;

test('job skips gracefully when no MT adapter is configured', function () {
    $user = \App\Models\User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);

    $mtService = $this->mock(MtService::class);
    $mtService->shouldReceive('resolveAdapter')->andReturn(null);
    $mtService->shouldNotReceive('translate');

    $editorService = $this->mock(EditorService::class);
    $editorService->shouldNotReceive('updateSegment');

    (new PopulateMtSuggestions($file))->handle($editorService, $mtService);
});

test('job translates all untranslated segments', function () {
    $user = \App\Models\User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    Segment::factory()->count(3)->create(['file_id' => $file->id, 'project_id' => $project->id, 'status' => 'untranslated']);
    Segment::factory()->translated()->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $adapter = Mockery::mock();

    $mtService = $this->mock(MtService::class);
    $mtService->shouldReceive('resolveAdapter')->andReturn($adapter);
    $mtService->shouldReceive('translate')->times(3)->andReturn(['suggestion' => 'Hola', 'provider' => 'deepl', 'tagWarning' => false]);

    $editorService = $this->mock(EditorService::class);
    $editorService->shouldReceive('updateSegment')->times(3);

    (new PopulateMtSuggestions($file))->handle($editorService, $mtService);
});
```

- [ ] **Step 3: Run the tests**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact --filter="MtControllerTest|PopulateMtSuggestionsTest"
```

Expected: all 4 tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Api/MtControllerTest.php tests/Feature/Jobs/PopulateMtSuggestionsTest.php
git commit -m "test: MT suggest API and PopulateMtSuggestions job"
```

---

## Task 11: QA Run Controller + RunQaOnFile Job Tests

**Files:**
- Create: `tests/Feature/Api/QaRunControllerTest.php`
- Create: `tests/Feature/Jobs/RunQaOnFileTest.php`

- [ ] **Step 1: Create `tests/Feature/Api/QaRunControllerTest.php`**

```php
<?php

use App\Jobs\RunQaOnFile;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

test('store dispatches RunQaOnFile and sets cache status to pending', function () {
    Queue::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);

    $this->postJson("/api/projects/{$project->id}/files/{$file->id}/qa")
        ->assertOk()
        ->assertJson(['status' => 'queued']);

    Queue::assertDispatched(RunQaOnFile::class, fn ($job) => $job->file->id === $file->id);
    expect(Cache::get("qa_status_{$file->id}"))->toBe('pending');
});

test('results returns pending status when QA has not run yet', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);

    $this->getJson("/api/projects/{$project->id}/files/{$file->id}/qa/results")
        ->assertOk()
        ->assertJson(['status' => 'pending', 'issues' => []]);
});

test('results returns issues when QA is ready', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);

    $issues = [['type' => 'tag_consistency', 'segment' => 1, 'message' => 'Missing tag']];
    Cache::put("qa_status_{$file->id}", 'ready', 3600);
    Cache::put("qa_results_{$file->id}", $issues, 3600);

    $this->getJson("/api/projects/{$project->id}/files/{$file->id}/qa/results")
        ->assertOk()
        ->assertJson(['status' => 'ready', 'issues' => $issues]);
});
```

- [ ] **Step 2: Create `tests/Feature/Jobs/RunQaOnFileTest.php`**

```php
<?php

use App\Jobs\RunQaOnFile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\EditorService;
use App\Support\FrameworkBridge;
use CatFramework\Qa\QualityRunner;
use Illuminate\Support\Facades\Cache;

test('job runs QA checks and caches results', function () {
    $user = \App\Models\User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id, 'qa_config' => null]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);

    $issues = [['type' => 'double_spaces', 'segment' => 1]];

    $document = Mockery::mock();
    $editorService = $this->mock(EditorService::class);
    $editorService->shouldReceive('hydrateDocument')->with($file)->andReturn($document);

    $runner = Mockery::mock(QualityRunner::class);
    $runner->shouldReceive('run')->with($document)->andReturn($issues);

    // Replace QualityRunner construction in the job via the container
    $this->app->bind(QualityRunner::class, fn () => $runner);

    (new RunQaOnFile($file))->handle($editorService, app(FrameworkBridge::class));

    expect(Cache::get("qa_status_{$file->id}"))->toBe('ready');
    expect(Cache::get("qa_results_{$file->id}"))->toBe($issues);
});
```

> **Note:** `RunQaOnFile` constructs `QualityRunner` with `new` directly. If the container binding approach above does not intercept it, refactor `RunQaOnFile::handle` to accept a `QualityRunner` parameter or use `app()`. In that case, update `RunQaOnFile.php`:
>
> ```php
> public function handle(EditorService $editorService, FrameworkBridge $bridge): void
> {
>     // ... existing setup ...
>     $runner = app(QualityRunner::class, ['config' => $qaConfig]);
>     $issues = $runner->run($document);
>     // ...
> }
> ```

- [ ] **Step 3: Run the tests**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact --filter="QaRunControllerTest|RunQaOnFileTest"
```

Expected: all 4 tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Api/QaRunControllerTest.php tests/Feature/Jobs/RunQaOnFileTest.php
git commit -m "test: QaRunController and RunQaOnFile job"
```

---

## Task 12: Export Controller Tests

**Files:**
- Create: `tests/Feature/ExportControllerTest.php`

- [ ] **Step 1: Create `tests/Feature/ExportControllerTest.php`**

```php
<?php

use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\ExportService;
use Illuminate\Support\Facades\Storage;

test('store triggers export and returns export path', function () {
    $this->mock(ExportService::class, fn ($m) => $m->shouldReceive('export')->andReturn('exports/test/target.docx'));

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $user->id]);

    $this->postJson(route('export.store', [$project, $file]))
        ->assertOk()
        ->assertJsonFragment(['status' => 'ready'])
        ->assertJsonStructure(['exportPath', 'status']);
});

test('download returns the exported file', function () {
    Storage::fake();
    Storage::put('exports/test/target.docx', 'fake-docx-content');

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'export_path' => 'exports/test/target.docx',
        'original_name' => 'document.docx',
    ]);

    $this->get(route('export.download', [$project, $file]))
        ->assertOk()
        ->assertDownload('translated_document.docx');
});

test('download returns 404 when export path is not set', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->ready()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'export_path' => null,
    ]);

    $this->get(route('export.download', [$project, $file]))->assertNotFound();
});

test('non-owner cannot trigger export', function () {
    actingAsUser();
    $owner = \App\Models\User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id, 'team_id' => $owner->team_id]);
    $file = ProjectFile::factory()->ready()->create(['project_id' => $project->id, 'user_id' => $owner->id]);

    $this->postJson(route('export.store', [$project, $file]))->assertForbidden();
});
```

- [ ] **Step 2: Run the tests**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact --filter=ExportControllerTest
```

Expected: all 4 tests pass.

- [ ] **Step 3: Run the full test suite**

```bash
"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact
```

Expected: all tests pass (including original auth + dashboard + settings + teams tests).

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/ExportControllerTest.php
git commit -m "test: ExportController — store, download, 404 on missing export, policy"
```

---

## Execution Summary

| Task | Tests Added | Key Coverage |
|------|------------|--------------|
| 1 | 0 | Infrastructure — Postgres test DB, RefreshDatabase, helpers |
| 2 | 0 | Factories — 7 models |
| 3 | 9 | ProjectController — all CRUD + policy |
| 4 | 4 | FileController — upload, status, delete |
| 5 | 4 | ProcessUploadedFile job — happy path, error, mtPrefill, skeleton |
| 6 | 7 | SegmentController API — pagination, filter, update, TM dispatch |
| 7 | 6 | TmController — show, import, export, delete, global |
| 8 | 6 | TmLookupController API + ImportTmxJob + WriteTmEntryJob |
| 9 | 5 | GlossaryController — show, add, delete, global, policy |
| 10 | 4 | MtController API + PopulateMtSuggestions job |
| 11 | 4 | QaRunController API + RunQaOnFile job |
| 12 | 4 | ExportController — store, download, 404, policy |

**Total new tests: 53** (up from 6 passing tests)
