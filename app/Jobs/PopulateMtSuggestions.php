<?php

namespace App\Jobs;

use App\Models\ProjectFile;
use App\Services\EditorService;
use App\Services\MtService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PopulateMtSuggestions implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public readonly ProjectFile $file,
    ) {}

    public function handle(EditorService $editorService, MtService $mtService): void
    {
        Log::info('PopulateMtSuggestions started', ['file_id' => $this->file->id]);

        $project = $this->file->project;
        $adapter = $mtService->resolveAdapter($project->user, $project);

        if (! $adapter) {
            Log::info('PopulateMtSuggestions skipped — no MT adapter configured', ['file_id' => $this->file->id]);

            return;
        }

        $segments = $this->file->segments()->where('status', 'untranslated')->get();

        Log::info('PopulateMtSuggestions translating segments', ['file_id' => $this->file->id, 'segment_count' => $segments->count()]);

        foreach ($segments->chunk(10) as $batch) {
            foreach ($batch as $segment) {
                $result = $mtService->translate($segment, $adapter);
                $editorService->updateSegment($segment, $result['suggestion'], [], 'draft');
            }
        }

        Log::info('PopulateMtSuggestions completed', ['file_id' => $this->file->id, 'segment_count' => $segments->count()]);
    }
}
