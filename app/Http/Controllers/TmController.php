<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TranslationMemory;
use App\Services\TmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TmController extends Controller
{
    public function __construct(
        private readonly TmService $tmService,
    ) {}

    public function show(Request $request, Project $project): Response
    {
        $tm = $project->projectTm;
        $entries = $tm ? $this->tmService->paginate($tm, 50) : collect();

        return Inertia::render('tm/index', [
            'project' => $project,
            'tm' => $tm,
            'entries' => $entries,
        ]);
    }

    public function import(Request $request, Project $project): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimetypes:text/xml,application/xml']);
        $path = $request->file('file')->store('tmp/tmx');
        $tm = $project->projectTm ?? $this->tmService->createForProject($project);
        dispatch(new \App\Jobs\ImportTmxJob($path, $tm));

        return back()->with('status', 'Import started.');
    }

    public function export(Project $project): BinaryFileResponse
    {
        $path = $this->tmService->exportTmx($project->projectTm);

        return response()->download(storage_path('app/' . $path), 'tm_export.tmx');
    }

    public function destroyEntry(Project $project, int $entry): RedirectResponse
    {
        $this->tmService->deleteEntry($entry, $project->projectTm);

        return back();
    }

    public function global(Request $request): Response
    {
        $user = $request->user();
        $tm = TranslationMemory::where('team_id', $user->team_id)->where('is_global', true)->first();

        return Inertia::render('tm/global', [
            'tm' => $tm,
            'entries' => $tm ? $this->tmService->paginate($tm, 50) : collect(),
        ]);
    }

    public function importGlobal(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimetypes:text/xml,application/xml']);
        $user = $request->user();
        $tm = TranslationMemory::where('team_id', $user->team_id)->where('is_global', true)->firstOrFail();
        $path = $request->file('file')->store('tmp/tmx');
        dispatch(new \App\Jobs\ImportTmxJob($path, $tm));

        return back()->with('status', 'Global TM import started.');
    }

    public function exportGlobal(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        $tm = TranslationMemory::where('team_id', $user->team_id)->where('is_global', true)->firstOrFail();
        $path = $this->tmService->exportTmx($tm);

        return response()->download(storage_path('app/' . $path), 'global_tm_export.tmx');
    }
}
