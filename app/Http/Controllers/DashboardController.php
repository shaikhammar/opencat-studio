<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $projects = $user->projects()
            ->with(['files:id,project_id,status,segment_count,translated_count'])
            ->withCount('files')
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(function ($project) {
                $totalSegments = $project->files->sum('segment_count');
                $translatedCount = $project->files->sum('translated_count');

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'source_lang' => $project->source_lang,
                    'target_lang' => $project->target_lang,
                    'status' => $project->status,
                    'last_activity_at' => $project->last_activity_at?->toISOString(),
                    'created_at' => $project->created_at->toISOString(),
                    'files_count' => $project->files_count,
                    'total_segments' => $totalSegments,
                    'translated_count' => $translatedCount,
                    'progress_pct' => $totalSegments > 0
                        ? (int) round($translatedCount / $totalSegments * 100)
                        : 0,
                    'has_processing_files' => $project->files
                        ->contains(fn ($f) => in_array($f->status, ['pending', 'processing'])),
                ];
            });

        return Inertia::render('dashboard', [
            'projects' => $projects,
        ]);
    }
}
