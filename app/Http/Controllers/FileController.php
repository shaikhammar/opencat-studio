<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileRequest;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\FileProcessingService;
use Illuminate\Http\RedirectResponse;

class FileController extends Controller
{
    public function __construct(
        private readonly FileProcessingService $fileProcessingService,
    ) {}

    public function store(StoreFileRequest $request, Project $project): RedirectResponse
    {
        $queueFailed = false;

        foreach ($request->file('files') as $file) {
            try {
                $this->fileProcessingService->accept(
                    $file,
                    $project,
                    $request->user(),
                    ['mtPrefill' => (bool) $request->input('mt_prefill', false)],
                );
            } catch (\Throwable) {
                $queueFailed = true;
            }
        }

        $redirect = redirect()->route('projects.show', $project);

        return $queueFailed
            ? $redirect->withErrors(['queue' => 'Queue service is unavailable. Some files could not be queued for processing.'])
            : $redirect;
    }

    public function destroy(Project $project, ProjectFile $file): RedirectResponse
    {
        $file->segments()->delete();
        $file->delete();

        return redirect()->route('projects.show', $project);
    }
}
