<?php

namespace App\Jobs;

use App\Models\Segment;
use App\Models\TranslationMemory;
use App\Services\TmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class WriteTmEntryJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public string $queue = 'critical';

    public function __construct(
        public readonly Segment $segment,
        public readonly TranslationMemory $tm,
    ) {}

    public function handle(TmService $tmService): void
    {
        $project = $this->segment->project;

        $tmService->addEntry(
            $this->segment->source_text,
            $this->segment->target_text,
            $project->source_lang,
            $project->target_lang,
            $this->tm,
        );
    }
}
