<?php

use App\Jobs\WriteTmEntryJob;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;
use App\Models\TranslationMemory;
use Illuminate\Support\Facades\Queue;

test('segments can be listed for a file', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'segment_count' => 3]);
    Segment::factory()->count(3)->sequence(fn ($s) => ['segment_number' => $s->index + 1])
        ->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $response = $this->getJson("/api/projects/{$project->id}/files/{$file->id}/segments");

    $response->assertOk();
    $response->assertJsonStructure(['data', 'meta' => ['page', 'limit', 'total', 'hasMore']]);
    expect(count($response->json('data')))->toBe(3);
});

test('segments can be filtered by status', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'segment_count' => 4]);

    Segment::factory()->count(2)->sequence(fn ($s) => ['segment_number' => $s->index + 1])
        ->create(['file_id' => $file->id, 'project_id' => $project->id]);
    Segment::factory()->translated()->count(2)->sequence(fn ($s) => ['segment_number' => $s->index + 3])
        ->create(['file_id' => $file->id, 'project_id' => $project->id]);

    $response = $this->getJson("/api/projects/{$project->id}/files/{$file->id}/segments?status=translated");

    $response->assertOk();
    expect(count($response->json('data')))->toBe(2);
});

test('a single segment can be retrieved', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id, 'segment_number' => 1]);

    $this->getJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}")->assertOk();
});

test('segments can be updated', function () {
    Queue::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id, 'segment_number' => 1]);

    $response = $this->patchJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}", [
        'target_text' => 'Translated text',
        'status' => 'translated',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('segments', [
        'id' => $segment->id,
        'target_text' => 'Translated text',
        'status' => 'translated',
    ]);
});

test('translating a segment increments file translated count', function () {
    Queue::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'translated_count' => 0,
    ]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id, 'segment_number' => 1]);

    $this->patchJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}", [
        'target_text' => 'Translated',
        'status' => 'translated',
    ]);

    expect($file->fresh()->translated_count)->toBe(1);
});

test('saving a translation dispatches tm write when project tm exists', function () {
    Queue::fake();

    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    TranslationMemory::factory()->forProject($project)->create();
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id, 'segment_number' => 1]);

    $this->patchJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}", [
        'target_text' => 'Translated',
        'status' => 'translated',
    ]);

    Queue::assertPushed(WriteTmEntryJob::class);
});

test('guests cannot access segment api', function () {
    $this->getJson('/api/projects/abc/files/def/segments')->assertUnauthorized();
});
