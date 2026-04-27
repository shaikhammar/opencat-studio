<?php

namespace App\Jobs;

use App\Models\ProjectFile;
use App\Services\EditorService;
use App\Support\FrameworkBridge;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class RunQaOnFile implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $timeout = 120;

    public function __construct(
        public readonly ProjectFile $file,
    ) {}

    public function handle(EditorService $editorService, FrameworkBridge $bridge): void
    {
        $document = $editorService->hydrateDocument($this->file);
        $project = $this->file->project;

        $qaConfig = array_merge(
            config('catframework.qa.default_checks'),
            $project->qa_config ?? [],
        );

        $runner = new \CatFramework\Qa\QualityRunner($qaConfig);
        $issues = $runner->run($document);

        Cache::put("qa_results_{$this->file->id}", $issues, 3600);
        Cache::put("qa_status_{$this->file->id}", 'ready', 3600);
    }
}
