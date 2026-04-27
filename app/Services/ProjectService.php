<?php

namespace App\Services;

use App\Models\Glossary;
use App\Models\Project;
use App\Models\TranslationMemory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function create(array $data, User $user): Project
    {
        return DB::transaction(function () use ($data, $user) {
            $project = $user->projects()->create(array_merge($data, [
                'team_id' => $user->team_id,
            ]));

            if ($data['create_project_tm'] ?? false) {
                TranslationMemory::create([
                    'team_id' => $user->team_id,
                    'project_id' => $project->id,
                    'name' => $project->name . ' TM',
                    'source_lang' => $project->source_lang,
                    'target_lang' => $project->target_lang,
                    'is_global' => false,
                ]);
            }

            if ($data['create_project_glossary'] ?? false) {
                $sqlitePath = "glossaries/{$user->team_id}/{$project->id}.db";
                Glossary::create([
                    'team_id' => $user->team_id,
                    'project_id' => $project->id,
                    'name' => $project->name . ' Glossary',
                    'source_lang' => $project->source_lang,
                    'target_lang' => $project->target_lang,
                    'sqlite_path' => $sqlitePath,
                    'is_global' => false,
                ]);
            }

            return $project;
        });
    }

    public function archive(Project $project): void
    {
        $project->update(['status' => 'archived']);
    }
}
