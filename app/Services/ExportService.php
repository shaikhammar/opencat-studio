<?php

namespace App\Services;

use App\Models\ProjectFile;
use App\Support\FrameworkBridge;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    public function __construct(
        private readonly FrameworkBridge $bridge,
        private readonly EditorService $editorService,
    ) {}

    public function export(ProjectFile $file): string
    {
        $document = $this->editorService->hydrateDocument($file);
        $skeletonBytes = Storage::get($file->skeleton_path);

        $filter = $this->bridge->makeFileFilter($file->file_format);
        $targetBytes = $filter->rebuild($document, $skeletonBytes);

        $ext = $file->file_format;
        $exportPath = "exports/{$file->project_id}/{$file->id}/target.{$ext}";
        Storage::put($exportPath, $targetBytes);

        $file->update(['export_path' => $exportPath, 'status' => 'exported']);

        return $exportPath;
    }
}
