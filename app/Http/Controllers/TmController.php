<?php

namespace App\Http\Controllers;

use App\Jobs\ImportTmxJob;
use App\Models\Project;
use App\Models\TranslationMemory;
use App\Services\TmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        try {
            dispatch(new ImportTmxJob($path, $tm));
        } catch (\Throwable) {
            return back()->withErrors(['queue' => 'Queue service is unavailable. Import could not be started.']);
        }

        return back()->with('status', 'Import started.');
    }

    public function export(Project $project): StreamedResponse
    {
        $path = $this->tmService->exportTmx($project->projectTm);

        return Storage::download($path, 'tm_export.tmx');
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

        try {
            dispatch(new ImportTmxJob($path, $tm));
        } catch (\Throwable) {
            return back()->withErrors(['queue' => 'Queue service is unavailable. Import could not be started.']);
        }

        return back()->with('status', 'Global TM import started.');
    }

    public function exportGlobal(Request $request): StreamedResponse
    {
        $user = $request->user();
        $tm = TranslationMemory::where('team_id', $user->team_id)->where('is_global', true)->firstOrFail();
        $path = $this->tmService->exportTmx($tm);

        return Storage::download($path, 'global_tm_export.tmx');
    }
}
