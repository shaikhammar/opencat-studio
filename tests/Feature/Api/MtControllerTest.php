<?php

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;

test('mt suggest returns 422 when no adapter configured', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id, 'segment_number' => 1]);

    $response = $this->postJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}/mt");

    $response->assertUnprocessable()->assertJsonFragment(['error' => 'No MT provider configured.']);
});

test('guests cannot access mt suggest api', function () {
    $this->postJson('/api/projects/abc/files/def/segments/ghi/mt')->assertUnauthorized();
});
