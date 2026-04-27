<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunQaOnFile;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class QaRunController extends Controller
{
    public function store(Project $project, ProjectFile $file): JsonResponse
    {
        Cache::put("qa_status_{$file->id}", 'pending', 3600);
        dispatch(new RunQaOnFile($file));

        return response()->json(['status' => 'queued']);
    }

    public function results(Project $project, ProjectFile $file): JsonResponse
    {
        $status = Cache::get("qa_status_{$file->id}", 'pending');
        $issues = Cache::get("qa_results_{$file->id}", []);

        return response()->json(['issues' => $issues, 'status' => $status]);
    }
}
