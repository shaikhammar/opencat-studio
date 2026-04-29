<?php

use App\Jobs\PopulateMtSuggestions;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;
use App\Services\EditorService;
use App\Services\MtService;

test('job skips when no mt adapter is configured', function () {
    $project = Project::factory()->create();
    $file = ProjectFile::factory()->create(['project_id' => $project->id]);

    $mtService = $this->mock(MtService::class, function ($mock) {
        $mock->shouldReceive('resolveAdapter')->once()->andReturn(null);
        $mock->shouldNotReceive('translate');
    });

    $editorService = $this->mock(EditorService::class, function ($mock) {
        $mock->shouldNotReceive('updateSegment');
    });

    (new PopulateMtSuggestions($file))->handle($editorService, $mtService);
});

test('job translates untranslated segments when adapter is available', function () {
    $project = Project::factory()->create();
    $file = ProjectFile::factory()->create(['project_id' => $project->id, 'segment_count' => 2]);
    Segment::factory()->count(2)->sequence(fn ($s) => ['segment_number' => $s->index + 1])
        ->create(['file_id' => $file->id, 'project_id' => $project->id, 'status' => 'untranslated']);

    $fakeAdapter = new class
    {
        public function translate(string $text): string
        {
            return 'Translated: '.$text;
        }
    };

    $mtService = $this->mock(MtService::class, function ($mock) use ($fakeAdapter) {
        $mock->shouldReceive('resolveAdapter')->once()->andReturn($fakeAdapter);
        $mock->shouldReceive('translate')->twice()->andReturn(['suggestion' => 'Translated text']);
    });

    $editorService = $this->mock(EditorService::class, function ($mock) {
        $mock->shouldReceive('updateSegment')->twice();
    });

    (new PopulateMtSuggestions($file))->handle($editorService, $mtService);
});
