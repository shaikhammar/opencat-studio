<?php

use App\Jobs\ImportTmxJob;
use App\Models\Project;
use App\Models\TranslationMemory;
use App\Models\User;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('tm show page renders for project owner', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $this->get(route('tm.show', $project))->assertOk();
});

test('tm show page renders with project tm data', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    TranslationMemory::factory()->forProject($project)->create(['entry_count' => 3]);

    $this->get(route('tm.show', $project))->assertOk();
});

test('non owner cannot view project tm', function () {
    $owner = User::factory()->create();
    $other = actingAsUser();
    $project = Project::factory()->create(['user_id' => $owner->id, 'team_id' => $owner->team_id]);

    $this->get(route('tm.show', $project))->assertForbidden();
});

test('tm import dispatches job and redirects', function () {
    Queue::fake();
    Storage::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $file = UploadedFile::fake()->create('file.tmx', 10, 'text/xml');

    $response = $this->post(route('tm.import', $project), ['file' => $file]);

    $response->assertRedirect();
    Queue::assertPushed(ImportTmxJob::class);
});

test('tm import requires an xml file', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $file = UploadedFile::fake()->create('file.pdf', 100, 'application/pdf');

    $response = $this->post(route('tm.import', $project), ['file' => $file]);

    $response->assertSessionHasErrors('file');
});

test('tm entry can be deleted', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $tm = TranslationMemory::factory()->forProject($project)->create(['entry_count' => 1]);

    $entryId = DB::table('tm_units')->insertGetId([
        'tm_id' => $tm->id,
        'source_lang' => 'en',
        'target_lang' => 'fr',
        'source_text' => 'Hello',
        'target_text' => 'Bonjour',
        'source_segment' => 'Hello',
        'target_segment' => 'Bonjour',
        'source_text_normalized' => 'hello',
    ]);

    $response = $this->delete(route('tm.entries.destroy', [$project, $entryId]));

    $response->assertRedirect();
    expect(DB::table('tm_units')->where('id', $entryId)->exists())->toBeFalse();
});

test('global tm page renders', function () {
    $user = actingAsUser();
    TranslationMemory::factory()->global()->create([
        'team_id' => $user->team_id,
    ]);

    $this->get(route('tm.global'))->assertOk();
});

test('tm import redirects with error when queue is unavailable', function () {
    Storage::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $this->mock(Dispatcher::class)
        ->shouldReceive('dispatch')
        ->andThrow(new RuntimeException('Redis connection refused'));

    $file = UploadedFile::fake()->create('file.tmx', 10, 'text/xml');

    $this->post(route('tm.import', $project), ['file' => $file])
        ->assertRedirect()
        ->assertSessionHasErrors('queue');
});

test('global tm import redirects with error when queue is unavailable', function () {
    Storage::fake();

    $user = actingAsUser();
    TranslationMemory::factory()->global()->create(['team_id' => $user->team_id]);

    $this->mock(Dispatcher::class)
        ->shouldReceive('dispatch')
        ->andThrow(new RuntimeException('Redis connection refused'));

    $file = UploadedFile::fake()->create('file.tmx', 10, 'text/xml');

    $this->post(route('tm.global.import'), ['file' => $file])
        ->assertRedirect()
        ->assertSessionHasErrors('queue');
});

test('tm export downloads a tmx file', function () {
    Storage::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $tm = TranslationMemory::factory()->forProject($project)->create();

    DB::table('tm_units')->insert([
        'tm_id' => $tm->id,
        'source_lang' => 'en',
        'target_lang' => 'fr',
        'source_text' => 'Hello world',
        'target_text' => 'Bonjour le monde',
        'source_segment' => 'Hello world',
        'target_segment' => 'Bonjour le monde',
        'source_text_normalized' => 'hello world',
        'created_at' => now(),
    ]);

    $response = $this->get(route('tm.export', $project));

    $response->assertOk();
    $response->assertDownload('tm_export.tmx');
    expect($response->streamedContent())->toContain('<tmx')->toContain('Hello world')->toContain('Bonjour le monde');
});

test('tm export returns empty tmx when no entries', function () {
    Storage::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    TranslationMemory::factory()->forProject($project)->create();

    $response = $this->get(route('tm.export', $project));

    $response->assertOk();
    $response->assertDownload('tm_export.tmx');
});
