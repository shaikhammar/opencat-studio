<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;
use App\Services\TmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TmLookupController extends Controller
{
    public function __construct(
        private readonly TmService $tmService,
    ) {}

    public function lookup(Project $project, ProjectFile $file, Segment $segment): JsonResponse
    {
        $tm = $project->projectTm;
        if (! $tm) {
            return response()->json([]);
        }

        $matches = $this->tmService->lookup(
            $segment->source_text,
            $project->source_lang,
            $project->target_lang,
            $project->tm_min_match_pct,
            $tm,
        );

        return response()->json($matches);
    }

    public function concordance(Request $request, Project $project): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2|max:500']);
        $tm = $project->projectTm;
        if (! $tm) {
            return response()->json([]);
        }

        $results = $this->tmService->search(
            $request->query('q'),
            $tm,
            (int) $request->query('limit', 20),
        );

        return response()->json($results);
    }
}
