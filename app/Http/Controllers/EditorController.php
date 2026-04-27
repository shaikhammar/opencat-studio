<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\EditorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EditorController extends Controller
{
    public function __construct(
        private readonly EditorService $editorService,
    ) {}

    public function show(Request $request, Project $project, ProjectFile $file): Response
    {
        $user = $request->user();
        $segments = $this->editorService->getSegments($file, null, 1, 100);

        return Inertia::render('editor/index', [
            'project' => $project,
            'file' => $file,
            'segments' => $segments,
            'userSettings' => [
                'autosaveMs' => (int) $user->getSetting('editor_autosave_ms', 500),
                'fontSize' => (int) $user->getSetting('editor_font_size', 14),
                'mtAutoRequest' => filter_var($user->getSetting('mt_auto_request', 'false'), FILTER_VALIDATE_BOOLEAN),
            ],
        ]);
    }
}
