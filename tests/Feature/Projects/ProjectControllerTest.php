<?php

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the project create page can be rendered', function () {
    $user = actingAsUser();

    $this->get(route('projects.create'))->assertOk();
});

test('users can create projects', function () {
    $user = actingAsUser();

    $response = $this->post(route('projects.store'), [
        'name' => 'My Project',
        'source_lang' => 'en',
        'target_lang' => 'fr',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('projects', [
        'user_id' => $user->id,
        'name' => 'My Project',
        'source_lang' => 'en',
        'target_lang' => 'fr',
    ]);
});

test('project creation can create a project tm and glossary', function () {
    $user = actingAsUser();

    $this->post(route('projects.store'), [
        'name' => 'My Project',
        'source_lang' => 'en',
        'target_lang' => 'de',
        'create_project_tm' => true,
        'create_project_glossary' => true,
    ]);

    $project = Project::where('user_id', $user->id)->first();

    $this->assertDatabaseHas('translation_memories', [
        'project_id' => $project->id,
        'is_global' => false,
    ]);

    $this->assertDatabaseHas('glossaries', [
        'project_id' => $project->id,
        'is_global' => false,
    ]);
});

test('the project show page can be rendered', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $this->get(route('projects.show', $project))->assertOk();
});

test('projects cannot be viewed by other users', function () {
    actingAsUser();
    $other = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id, 'team_id' => $other->team_id]);

    $this->get(route('projects.show', $project))->assertForbidden();
});

test('projects can be updated', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $response = $this->patch(route('projects.update', $project), [
        'name' => 'Updated Name',
        'source_lang' => 'en',
        'target_lang' => 'de',
    ]);

    $response->assertRedirect(route('projects.show', $project));

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Updated Name',
    ]);
});

test('projects cannot be updated by other users', function () {
    actingAsUser();
    $other = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id, 'team_id' => $other->team_id]);

    $this->patch(route('projects.update', $project), [
        'name' => 'Updated Name',
        'source_lang' => 'en',
        'target_lang' => 'de',
    ])->assertForbidden();
});

test('projects can be archived', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $this->delete(route('projects.destroy', $project))->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'status' => 'archived',
    ]);
});

test('projects cannot be archived by other users', function () {
    actingAsUser();
    $other = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id, 'team_id' => $other->team_id]);

    $this->delete(route('projects.destroy', $project))->assertForbidden();
});

test('project creation redirects with queue error when dispatch fails', function () {
    Storage::fake('local');

    $user = actingAsUser();

    $this->mock(Dispatcher::class)
        ->shouldReceive('dispatch')
        ->andThrow(new RuntimeException('Redis connection refused'));

    $file = UploadedFile::fake()->create('document.xlf', 10, 'text/xml');

    $this->post(route('projects.store'), [
        'name' => 'My Project',
        'source_lang' => 'en',
        'target_lang' => 'fr',
        'files' => [$file],
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('queue');

    $this->assertDatabaseHas('projects', [
        'user_id' => $user->id,
        'name' => 'My Project',
    ]);
});

test('a pending file stuck for over 2 minutes is marked as error on show', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'pending',
        'updated_at' => now()->subMinutes(3),
    ]);

    $this->get(route('projects.show', $project))->assertOk();

    $this->assertDatabaseHas('project_files', [
        'id' => $file->id,
        'status' => 'error',
        'error_message' => 'File processing did not start. Check that the queue worker is running.',
    ]);
});

test('a processing file stuck for over 5 minutes is marked as error on show', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'processing',
        'updated_at' => now()->subMinutes(6),
    ]);

    $this->get(route('projects.show', $project))->assertOk();

    $this->assertDatabaseHas('project_files', [
        'id' => $file->id,
        'status' => 'error',
        'error_message' => 'File processing timed out. Please try uploading the file again.',
    ]);
});

test('a recently pending file is not marked as error', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'pending',
        'updated_at' => now()->subSeconds(30),
    ]);

    $this->get(route('projects.show', $project))->assertOk();

    $this->assertDatabaseHas('project_files', [
        'id' => $file->id,
        'status' => 'pending',
    ]);
});

test('isPolling is false when all files have timed out', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    ProjectFile::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'pending',
        'updated_at' => now()->subMinutes(3),
    ]);

    $this->get(route('projects.show', $project))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('isPolling', false));
});

test('guests cannot access projects', function () {
    $this->get(route('projects.create'))->assertRedirect(route('login'));
});
