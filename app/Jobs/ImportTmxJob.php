<?php

namespace App\Jobs;

use App\Models\TranslationMemory;
use App\Services\TmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

class ImportTmxJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $timeout = 300;

    public function __construct(
        public readonly string $storagePath,
        public readonly TranslationMemory $tm,
    ) {}

    public function handle(TmService $tmService): void
    {
        $absolutePath = Storage::path($this->storagePath);
        $tmService->importTmx($absolutePath, $this->tm);
        Storage::delete($this->storagePath);
    }
}
