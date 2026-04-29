<?php

namespace App\Jobs;

use App\Models\Segment;
use App\Models\TranslationMemory;
use App\Services\TmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class WriteTmEntryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Segment $segment,
        public readonly TranslationMemory $tm,
    ) {
        $this->onQueue('critical');
    }

    public function handle(TmService $tmService): void
    {
        Log::info('WriteTmEntryJob started', ['segment_id' => $this->segment->id, 'tm_id' => $this->tm->id]);

        $project = $this->segment->project;

        $tmService->addEntry(
            $this->segment->source_text,
            $this->segment->target_text,
            $project->source_lang,
            $project->target_lang,
            $this->tm,
        );

        Log::info('WriteTmEntryJob completed', ['segment_id' => $this->segment->id, 'tm_id' => $this->tm->id]);
    }
}
