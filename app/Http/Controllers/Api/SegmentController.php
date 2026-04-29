<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSegmentRequest;
use App\Jobs\WriteTmEntryJob;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;
use App\Services\EditorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SegmentController extends Controller
{
    public function __construct(
        private readonly EditorService $editorService,
    ) {}

    public function index(Request $request, Project $project, ProjectFile $file): JsonResponse
    {
        $status = $request->query('status');
        $page = max(1, (int) $request->query('page', 1));
        $limit = min(200, max(1, (int) $request->query('limit', 100)));

        $segments = $this->editorService->getSegments($file, $status ?: null, $page, $limit);
        $total = $file->segment_count;

        return response()->json([
            'data' => $segments,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'hasMore' => ($page * $limit) < $total,
            ],
        ]);
    }

    public function show(Project $project, ProjectFile $file, Segment $segment): JsonResponse
    {
        return response()->json($segment);
    }

    public function update(UpdateSegmentRequest $request, Project $project, ProjectFile $file, Segment $segment): JsonResponse
    {
        $updated = $this->editorService->updateSegment(
            $segment,
            $request->input('target_text'),
            $request->input('target_tags', []),
            $request->input('status'),
        );

        if ($updated->isTranslated() && $project->projectTm) {
            try {
                dispatch(new WriteTmEntryJob($updated, $project->projectTm));
            } catch (\Throwable $e) {
                Log::warning('Failed to queue TM entry write', [
                    'segment_id' => $updated->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json($updated);
    }
}
