<?php

namespace App\Jobs;

use App\Models\ProjectFile;
use App\Support\FrameworkBridge;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessUploadedFile implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 2;

    public function __construct(
        public readonly ProjectFile $file,
        public readonly array $options = [],
    ) {}

    public function failed(\Throwable $e): void
    {
        $this->file->update([
            'status' => 'error',
            'error_message' => $e->getMessage(),
        ]);
    }

    public function handle(FrameworkBridge $bridge): void
    {
        Log::info('ProcessUploadedFile started', ['file_id' => $this->file->id, 'format' => $this->file->file_format]);

        $this->file->update(['status' => 'processing']);

        try {
            $sourceContents = Storage::get($this->file->storage_path);
            $filter = $bridge->makeFileFilter($this->file->file_format);
            $segEngine = $bridge->makeSegmentationEngine();

            $result = $filter->extract($sourceContents);
            $document = $result->document;
            $skeletonBytes = $result->skeleton;

            $segEngine->segment($document);

            $skeletonPath = "skeletons/{$this->file->project_id}/{$this->file->id}/skeleton.bin";
            Storage::put($skeletonPath, $skeletonBytes);

            $segments = [];
            $wordCount = 0;
            foreach ($document->getSegmentPairs() as $i => $pair) {
                $wordCount += str_word_count($pair->getSourceText());
                $segments[] = [
                    'id' => (string) Str::orderedUuid(),
                    'file_id' => $this->file->id,
                    'project_id' => $this->file->project_id,
                    'segment_number' => $i + 1,
                    'source_text' => $pair->getSourceText(),
                    'source_tags' => json_encode($pair->getSourceTags()),
                    'target_tags' => json_encode([]),
                    'status' => 'untranslated',
                    'word_count' => str_word_count($pair->getSourceText()),
                    'char_count' => mb_strlen($pair->getSourceText()),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('segments')->insert($segments);

            $this->file->update([
                'skeleton_path' => $skeletonPath,
                'word_count' => $wordCount,
                'segment_count' => count($segments),
                'status' => 'ready',
                'processed_at' => now(),
            ]);

            Log::info('ProcessUploadedFile completed', [
                'file_id' => $this->file->id,
                'segment_count' => count($segments),
                'word_count' => $wordCount,
            ]);

            if ($this->options['mtPrefill'] ?? false) {
                dispatch(new PopulateMtSuggestions($this->file));
            }
        } catch (\Throwable $e) {
            Log::error('ProcessUploadedFile failed', ['file_id' => $this->file->id, 'error' => $e->getMessage()]);

            $this->file->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }
}
