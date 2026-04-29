<?php

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('files can be uploaded to a project', function () {
    Storage::fake('local');
    Queue::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $file = UploadedFile::fake()->create('document.xlf', 10, 'text/xml');

    $response = $this->post(route('files.store', $project), ['file' => $file]);

    $response->assertOk();
    $response->assertJsonStructure(['fileId', 'status']);

    $this->assertDatabaseHas('project_files', [
        'project_id' => $project->id,
        'user_id' => $user->id,
        'original_name' => 'document.xlf',
        'status' => 'pending',
    ]);
});

test('file status can be retrieved', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->processed()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);

    $response = $this->get(route('files.status', [$project, $file]));

    $response->assertOk();
    $response->assertJsonStructure(['id', 'status', 'wordCount', 'segmentCount', 'translatedCount', 'progress']);
});

test('files can be deleted from a project', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);

    $this->delete(route('files.destroy', [$project, $file]))
        ->assertRedirect(route('projects.show', $project));

    $this->assertDatabaseMissing('project_files', ['id' => $file->id]);
});

test('other users cannot delete project files', function () {
    $user = actingAsUser();
    $other = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id, 'team_id' => $other->team_id]);
    $file = ProjectFile::factory()->create([
        'project_id' => $project->id,
        'user_id' => $other->id,
    ]);

    $this->delete(route('files.destroy', [$project, $file]))->assertForbidden();
});
