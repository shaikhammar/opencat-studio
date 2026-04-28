<?php

namespace App\Jobs;

use App\Models\ProjectFile;
use App\Services\EditorService;
use App\Support\FrameworkBridge;
use CatFramework\Qa\QualityRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunQaOnFile implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(
        public readonly ProjectFile $file,
    ) {}

    public function handle(EditorService $editorService, FrameworkBridge $bridge): void
    {
        Log::info('RunQaOnFile started', ['file_id' => $this->file->id]);

        $document = $editorService->hydrateDocument($this->file);
        $project = $this->file->project;

        $qaConfig = array_merge(
            config('catframework.qa.default_checks'),
            $project->qa_config ?? [],
        );

        $runner = new QualityRunner($qaConfig);
        $issues = $runner->run($document);

        Cache::put("qa_results_{$this->file->id}", $issues, 3600);
        Cache::put("qa_status_{$this->file->id}", 'ready', 3600);

        Log::info('RunQaOnFile completed', ['file_id' => $this->file->id, 'issue_count' => count($issues)]);
    }
}
