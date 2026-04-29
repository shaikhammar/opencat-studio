<?php

use App\Jobs\RunQaOnFile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\EditorService;
use App\Support\Filters\FilterDocument;
use App\Support\FrameworkBridge;
use Illuminate\Support\Facades\Cache;

test('job stores qa results and sets status to ready', function () {
    $project = Project::factory()->create(['qa_config' => []]);
    $file = ProjectFile::factory()->create(['project_id' => $project->id]);

    $fakeDocument = new FilterDocument;

    $editorService = $this->mock(EditorService::class, function ($mock) use ($fakeDocument) {
        $mock->shouldReceive('hydrateDocument')->once()->andReturn($fakeDocument);
    });

    $bridge = $this->mock(FrameworkBridge::class);

    (new RunQaOnFile($file))->handle($editorService, $bridge);

    expect(Cache::get("qa_status_{$file->id}"))->toBe('ready');
    expect(Cache::get("qa_results_{$file->id}"))->toBeArray();
});
