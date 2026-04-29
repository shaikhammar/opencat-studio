<?php

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('files can be uploaded to a project', function () {
    Storage::fake('local');
    Queue::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $file = UploadedFile::fake()->create('document.xlf', 10, 'text/xml');

    $this->post(route('files.store', $project), ['files' => [$file]])
        ->assertRedirect(route('projects.show', $project));

    $this->assertDatabaseHas('project_files', [
        'project_id' => $project->id,
        'user_id' => $user->id,
        'original_name' => 'document.xlf',
        'status' => 'pending',
    ]);
});

test('multiple files can be uploaded at once', function () {
    Storage::fake('local');
    Queue::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $files = [
        UploadedFile::fake()->create('first.xlf', 5, 'text/xml'),
        UploadedFile::fake()->create('second.xlf', 5, 'text/xml'),
    ];

    $this->post(route('files.store', $project), ['files' => $files])
        ->assertRedirect(route('projects.show', $project));

    expect(ProjectFile::where('project_id', $project->id)->count())->toBe(2);
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

test('file upload redirects with queue error when dispatch fails', function () {
    Storage::fake('local');

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $this->mock(Dispatcher::class)
        ->shouldReceive('dispatch')
        ->andThrow(new RuntimeException('Redis connection refused'));

    $file = UploadedFile::fake()->create('document.xlf', 10, 'text/xml');

    $this->post(route('files.store', $project), ['files' => [$file]])
        ->assertRedirect()
        ->assertSessionHasErrors('queue');
});
