<?php

namespace App\Services;

use App\Models\ProjectFile;
use App\Models\Segment;
use App\Support\Filters\FilterDocument;
use App\Support\Filters\FilterSegmentPair;
use Illuminate\Support\Facades\DB;

class EditorService
{
    public function getSegments(ProjectFile $file, ?string $status, int $page, int $limit): array
    {
        $query = $file->segments();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->forPage($page, $limit)->get()->toArray();
    }

    public function updateSegment(
        Segment $segment,
        ?string $targetText,
        array $targetTags,
        string $status,
    ): Segment {
        $wasTranslated = $segment->isTranslated();

        $segment->update([
            'target_text' => $targetText,
            'target_tags' => $targetTags,
            'status' => $status,
        ]);

        $isTranslated = $segment->isTranslated();

        if ($isTranslated !== $wasTranslated) {
            $delta = $isTranslated ? 1 : -1;
            DB::table('project_files')
                ->where('id', $segment->file_id)
                ->increment('translated_count', $delta);
            DB::table('projects')
                ->where('id', $segment->project_id)
                ->update(['last_activity_at' => now()]);
        }

        return $segment->fresh();
    }

    /**
     * Reconstruct a FilterDocument from stored segments — used by ExportService and QA jobs.
     */
    public function hydrateDocument(ProjectFile $file): FilterDocument
    {
        $segments = $file->segments()->get();
        $document = new FilterDocument;

        foreach ($segments as $seg) {
            $pair = new FilterSegmentPair(
                $seg->source_text,
                $seg->target_text ?? '',
                $seg->source_tags ?? [],
                $seg->target_tags ?? [],
            );
            $document->addSegmentPair($pair);
        }

        return $document;
    }
}
