<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;
use App\Services\MtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MtController extends Controller
{
    public function __construct(
        private readonly MtService $mtService,
    ) {}

    public function suggest(Request $request, Project $project, ProjectFile $file, Segment $segment): JsonResponse
    {
        $adapter = $this->mtService->resolveAdapter($request->user(), $project);
        if (! $adapter) {
            return response()->json(['error' => 'No MT provider configured.'], 422);
        }

        $result = $this->mtService->translate($segment, $adapter);

        return response()->json($result);
    }
}
