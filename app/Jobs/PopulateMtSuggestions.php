<?php

namespace App\Jobs;

use App\Models\ProjectFile;
use App\Services\EditorService;
use App\Services\MtService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class PopulateMtSuggestions implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(
        public readonly ProjectFile $file,
    ) {}

    public function handle(EditorService $editorService, MtService $mtService): void
    {
        $project = $this->file->project;
        $adapter = $mtService->resolveAdapter($project->user, $project);
        if (! $adapter) {
            return;
        }

        $segments = $this->file->segments()->where('status', 'untranslated')->get();

        foreach ($segments->chunk(10) as $batch) {
            foreach ($batch as $segment) {
                $result = $mtService->translate($segment, $adapter);
                $editorService->updateSegment($segment, $result['suggestion'], [], 'draft');
            }
        }
    }
}
