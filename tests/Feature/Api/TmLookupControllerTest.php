<?php

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;

test('lookup returns empty array when project has no tm', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    $segment = Segment::factory()->create(['file_id' => $file->id, 'project_id' => $project->id, 'segment_number' => 1]);

    $response = $this->getJson("/api/projects/{$project->id}/files/{$file->id}/segments/{$segment->id}/tm");

    $response->assertOk()->assertJson([]);
});

test('concordance returns empty array when project has no tm', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $response = $this->getJson("/api/projects/{$project->id}/tm/search?q=hello");

    $response->assertOk()->assertJson([]);
});

test('concordance requires a query of at least two characters', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $this->getJson("/api/projects/{$project->id}/tm/search?q=h")->assertUnprocessable();
    $this->getJson("/api/projects/{$project->id}/tm/search")->assertUnprocessable();
});

test('guests cannot access tm lookup api', function () {
    $this->getJson('/api/projects/abc/files/def/segments/ghi/tm')->assertUnauthorized();
});
