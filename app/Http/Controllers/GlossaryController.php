<?php

namespace App\Http\Controllers;

use App\Models\Glossary;
use App\Models\Project;
use App\Services\GlossaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GlossaryController extends Controller
{
    public function __construct(
        private readonly GlossaryService $glossaryService,
    ) {}

    public function show(Request $request, Project $project): Response
    {
        $glossary = $project->projectGlossary;

        return Inertia::render('glossary/index', [
            'project' => $project,
            'glossary' => $glossary,
            'terms' => $glossary ? $this->glossaryService->paginate($glossary, 100) : collect(),
        ]);
    }

    public function import(Request $request, Project $project): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimetypes:text/xml,application/xml']);
        $path = $request->file('file')->getRealPath();
        $glossary = $project->projectGlossary ?? $this->glossaryService->createForProject($project);
        $count = $this->glossaryService->importTbx($path, $glossary);

        return back()->with('status', "Imported {$count} terms.");
    }

    public function storeTerm(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'source' => 'required|string|max:500',
            'target' => 'required|string|max:500',
            'domain' => 'nullable|string|max:100',
        ]);

        $glossary = $project->projectGlossary ?? $this->glossaryService->createForProject($project);
        $this->glossaryService->addTerm($data['source'], $data['target'], $data['domain'] ?? '', $glossary);

        return back();
    }

    public function destroyTerm(Project $project, int $termId): RedirectResponse
    {
        $this->glossaryService->deleteTerm($termId, $project->projectGlossary);

        return back();
    }

    public function global(Request $request): Response
    {
        $user = $request->user();
        $glossary = Glossary::where('team_id', $user->team_id)->where('is_global', true)->first();

        return Inertia::render('glossary/global', [
            'glossary' => $glossary,
            'terms' => $glossary ? $this->glossaryService->paginate($glossary, 100) : collect(),
        ]);
    }
}
