<?php

namespace App\Jobs;

use App\Models\ProjectFile;
use App\Support\FrameworkBridge;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

class ProcessUploadedFile implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $timeout = 300;
    public int $tries = 2;

    public function __construct(
        public readonly ProjectFile $file,
        public readonly array $options = [],
    ) {}

    public function handle(FrameworkBridge $bridge): void
    {
        $this->file->update(['status' => 'processing']);

        try {
            $sourceContents = Storage::get($this->file->storage_path);
            $filter = $bridge->makeFileFilter($this->file->file_format);
            $segEngine = $bridge->makeSegmentationEngine();

            // extract → segment → persist segments → store skeleton
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
                    'id' => (string) \Illuminate\Support\Str::orderedUuid(),
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

            \Illuminate\Support\Facades\DB::table('segments')->insert($segments);

            $this->file->update([
                'skeleton_path' => $skeletonPath,
                'word_count' => $wordCount,
                'segment_count' => count($segments),
                'status' => 'ready',
                'processed_at' => now(),
            ]);

            if ($this->options['mtPrefill'] ?? false) {
                dispatch(new PopulateMtSuggestions($this->file));
            }
        } catch (\Throwable $e) {
            $this->file->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }
}
