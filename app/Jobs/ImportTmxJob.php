<?php

namespace App\Jobs;

use App\Models\TranslationMemory;
use App\Services\TmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportTmxJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(
        public readonly string $storagePath,
        public readonly TranslationMemory $tm,
    ) {}

    public function handle(TmService $tmService): void
    {
        Log::info('ImportTmxJob started', ['tm_id' => $this->tm->id, 'path' => $this->storagePath]);

        $absolutePath = Storage::path($this->storagePath);
        $tmService->importTmx($absolutePath, $this->tm);
        Storage::delete($this->storagePath);

        Log::info('ImportTmxJob completed', ['tm_id' => $this->tm->id]);
    }
}
