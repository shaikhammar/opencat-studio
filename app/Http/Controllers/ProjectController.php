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
        $project->load(['files', 'translationMemories', 'glossaries']);

        return Inertia::render('projects/show', [
            'project' => $project,
            'files' => $project->files,
            'tm' => $project->projectTm,
            'glossary' => $project->projectGlossary,
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
