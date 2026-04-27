<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function __construct(
        private readonly ExportService $exportService,
    ) {}

    public function store(Project $project, ProjectFile $file): JsonResponse
    {
        $exportPath = $this->exportService->export($file);

        return response()->json(['exportPath' => $exportPath, 'status' => 'ready']);
    }

    public function download(Project $project, ProjectFile $file): BinaryFileResponse
    {
        abort_unless($file->export_path && file_exists(storage_path('app/' . $file->export_path)), 404);

        $filename = 'translated_' . $file->original_name;

        return response()->download(storage_path('app/' . $file->export_path), $filename);
    }
}
