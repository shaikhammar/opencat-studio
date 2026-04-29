<?php

use App\Jobs\RunQaOnFile;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

test('qa store dispatches job and returns queued status', function () {
    Queue::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);

    $response = $this->postJson("/api/projects/{$project->id}/files/{$file->id}/qa");

    $response->assertOk()->assertJson(['status' => 'queued']);
    Queue::assertPushed(RunQaOnFile::class);
});

test('qa results returns cached issues and status', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);

    Cache::put("qa_results_{$file->id}", [['type' => 'spelling', 'message' => 'Error']], 3600);
    Cache::put("qa_status_{$file->id}", 'ready', 3600);

    $response = $this->getJson("/api/projects/{$project->id}/files/{$file->id}/qa/results");

    $response->assertOk()
        ->assertJsonPath('status', 'ready')
        ->assertJsonCount(1, 'issues');
});

test('qa store returns 503 when queue is unavailable', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);

    $this->mock(Dispatcher::class)
        ->shouldReceive('dispatch')
        ->andThrow(new RuntimeException('Redis connection refused'));

    $this->postJson("/api/projects/{$project->id}/files/{$file->id}/qa")
        ->assertStatus(503)
        ->assertJsonPath('error', 'Queue service is unavailable. Please try again later.');
});

test('guests cannot access qa api', function () {
    $this->postJson('/api/projects/abc/files/def/qa')->assertUnauthorized();
});
