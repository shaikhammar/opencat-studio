<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\TranslationMemory;
use App\Services\FileProcessingService;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $this->fileProcessingService->accept($file, $project, $request->user(), [
                    'mtPrefill' => $project->mt_prefill,
                ]);
            }
        }

        return redirect()->route('projects.show', $project);
    }

    public function show(Request $request, Project $project): Response
    {
        $files = $project->files()->orderBy('created_at')->get();

        $totalSegments  = $files->sum('segment_count');
        $translated     = $files->sum('translated_count');
        $hasProcessing  = $files->contains(fn ($f) => in_array($f->status, ['pending', 'processing']));

        $nextStep = match (true) {
            $hasProcessing                                          => 'processing',
            $files->isEmpty()                                      => 'no_files',
            $totalSegments > 0 && $translated === $totalSegments   => 'complete',
            $totalSegments > 0 && $translated === 0                => 'translate',
            default                                                => 'translating',
        };

        return Inertia::render('projects/show', [
            'project'   => $project,
            'files'     => $files,
            'tm'        => $project->projectTm,
            'glossary'  => $project->projectGlossary,
            'nextStep'  => $nextStep,
            'isPolling' => $hasProcessing,
        ]);
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
