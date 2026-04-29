<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\TranslationMemory;
use App\Services\FileProcessingService;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly FileProcessingService $fileProcessingService,
    ) {}

    public function create(Request $request): Response
    {
        $user = $request->user();

        $globalTm = TranslationMemory::where('team_id', $user->team_id)
            ->where('is_global', true)
            ->first();

        return Inertia::render('projects/create', [
            'globalTmExists' => $globalTm !== null,
            'globalTmEntryCount' => $globalTm?->entry_count ?? 0,
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = $this->projectService->create($request->validated(), $request->user());

        $queueFailed = false;

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                try {
                    $this->fileProcessingService->accept($file, $project, $request->user(), [
                        'mtPrefill' => $project->mt_prefill,
                    ]);
                } catch (\Throwable) {
                    $queueFailed = true;
                    break;
                }
            }
        }

        $redirect = redirect()->route('projects.show', $project);

        return $queueFailed
            ? $redirect->withErrors(['queue' => 'Project created, but file processing could not be queued — queue service is unavailable.'])
            : $redirect;
    }

    public function show(Request $request, Project $project): Response
    {
        $files = $project->files()->orderBy('created_at')->get();
        $files = $this->markStaleFilesAsFailed($project, $files);

        $totalSegments = $files->sum('segment_count');
        $translated = $files->sum('translated_count');
        $hasProcessing = $files->contains(fn ($f) => in_array($f->status, ['pending', 'processing']));

        $nextStep = match (true) {
            $hasProcessing => 'processing',
            $files->isEmpty() => 'no_files',
            $totalSegments > 0 && $translated === $totalSegments => 'complete',
            $totalSegments > 0 && $translated === 0 => 'translate',
            default => 'translating',
        };

        return Inertia::render('projects/show', [
            'project' => $project,
            'files' => $files,
            'tm' => $project->projectTm,
            'glossary' => $project->projectGlossary,
            'nextStep' => $nextStep,
            'isPolling' => $hasProcessing,
        ]);
    }

    /**
     * Mark pending/processing files that have been stuck past their timeout as failed.
     * Pending files should start within 2 minutes; processing files must finish within 5.
     * Returns a refreshed collection if any files were updated, otherwise returns the original.
     *
     * @param  Collection<int, ProjectFile>  $files
     * @return Collection<int, ProjectFile>
     */
    private function markStaleFilesAsFailed(Project $project, Collection $files): Collection
    {
        $markedAny = false;

        foreach ($files as $file) {
            if ($file->status === 'pending' && $file->updated_at->lt(now()->subMinutes(2))) {
                $file->update([
                    'status' => 'error',
                    'error_message' => 'File processing did not start. Check that the queue worker is running.',
                ]);
                $markedAny = true;
            } elseif ($file->status === 'processing' && $file->updated_at->lt(now()->subMinutes(5))) {
                $file->update([
                    'status' => 'error',
                    'error_message' => 'File processing timed out. Please try uploading the file again.',
                ]);
                $markedAny = true;
            }
        }

        return $markedAny
            ? $project->files()->orderBy('created_at')->get()
            : $files;
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->route('projects.show', $project);
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->projectService->archive($project);

        return redirect()->route('dashboard');
    }
}
