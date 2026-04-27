<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileRequest;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\FileProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function __construct(
        private readonly FileProcessingService $fileProcessingService,
    ) {}

    public function store(StoreFileRequest $request, Project $project): JsonResponse
    {
        $file = $this->fileProcessingService->accept(
            $request->file('file'),
            $project,
            $request->user(),
            ['mtPrefill' => (bool) $request->input('mt_prefill', false)],
        );

        return response()->json(['fileId' => $file->id, 'status' => $file->status]);
    }

    public function status(Project $project, ProjectFile $file): JsonResponse
    {
        return response()->json($this->fileProcessingService->getStatus($file));
    }

    public function destroy(Project $project, ProjectFile $file): RedirectResponse
    {
        $file->segments()->delete();
        $file->delete();

        return redirect()->route('projects.show', $project);
    }
}
