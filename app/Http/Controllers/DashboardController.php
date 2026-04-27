<?php

namespace App\Http\Controllers;

use App\Models\Glossary;
use App\Models\TranslationMemory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $projects = $user->projects()
            ->withCount('files')
            ->with(['files' => fn ($q) => $q->select('id', 'project_id', 'status', 'word_count', 'segment_count', 'translated_count')])
            ->orderByDesc('last_activity_at')
            ->get();

        $globalTm = TranslationMemory::where('team_id', $user->team_id)
            ->where('is_global', true)
            ->first();

        $globalGlossary = Glossary::where('team_id', $user->team_id)
            ->where('is_global', true)
            ->first();

        return Inertia::render('dashboard', [
            'projects' => $projects,
            'globalTm' => $globalTm,
            'globalGlossary' => $globalGlossary,
        ]);
    }
}
