<?php

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\Services\ExportService;

test('export store calls export service and returns json', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->processed()->create(['project_id' => $project->id, 'user_id' => $user->id]);

    $this->mock(ExportService::class, function ($mock) {
        $mock->shouldReceive('export')->once()->andReturn('exports/test/file.docx');
    });

    $response = $this->post(route('export.store', [$project, $file]));

    $response->assertOk()
        ->assertJsonPath('exportPath', 'exports/test/file.docx')
        ->assertJsonPath('status', 'ready');
});

test('non owner cannot trigger export', function () {
    $owner = User::factory()->create();
    actingAsUser();
    $project = Project::factory()->create(['user_id' => $owner->id, 'team_id' => $owner->team_id]);
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'user_id' => $owner->id]);

    $this->post(route('export.store', [$project, $file]))->assertForbidden();
});

test('export download returns 404 when no export exists', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'export_path' => null]);

    $this->get(route('export.download', [$project, $file]))->assertNotFound();
});

test('guests cannot access export routes', function () {
    $this->post('/projects/abc/files/def/export')->assertRedirectToRoute('login');
});
