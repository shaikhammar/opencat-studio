<?php

namespace App\Services;

use App\Jobs\ProcessUploadedFile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileProcessingService
{
    public function accept(UploadedFile $upload, Project $project, User $user, array $options = []): ProjectFile
    {
        $fileId = (string) Str::orderedUuid();
        $ext = $upload->getClientOriginalExtension();
        $storagePath = "uploads/{$project->id}/{$fileId}/source.{$ext}";

        Storage::put($storagePath, file_get_contents($upload->getRealPath()));

        $file = ProjectFile::create([
            'id' => $fileId,
            'project_id' => $project->id,
            'user_id' => $user->id,
            'original_name' => $upload->getClientOriginalName(),
            'storage_path' => $storagePath,
            'file_format' => strtolower($ext),
            'mime_type' => $upload->getMimeType(),
            'file_size_bytes' => $upload->getSize(),
            'status' => 'pending',
        ]);

        try {
            dispatch(new ProcessUploadedFile($file, $options));
        } catch (\Throwable $e) {
            $file->update(['status' => 'error', 'error_message' => 'Queue service unavailable.']);
            throw $e;
        }

        return $file;
    }

    public function getStatus(ProjectFile $file): array
    {
        return [
            'id' => $file->id,
            'status' => $file->status,
            'wordCount' => $file->word_count,
            'segmentCount' => $file->segment_count,
            'translatedCount' => $file->translated_count,
            'errorMessage' => $file->error_message,
            'progress' => $file->translationProgress(),
        ];
    }
}
