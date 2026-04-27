<?php

namespace App\Services;

use App\Models\Glossary;
use App\Models\Project;
use App\Support\FrameworkBridge;
use Illuminate\Support\Facades\DB;

class GlossaryService
{
    public function __construct(
        private readonly FrameworkBridge $bridge,
    ) {}

    public function recognize(string $sourceText, string $sourceLang, Glossary $glossary): array
    {
        return $this->bridge->makeGlossaryProvider($glossary)->recognize($sourceText, $sourceLang);
    }

    public function importTbx(string $tbxPath, Glossary $glossary): int
    {
        $provider = $this->bridge->makeGlossaryProvider($glossary);
        $count = $provider->importTbx($tbxPath);

        DB::table('glossaries')->where('id', $glossary->id)->increment('term_count', $count);

        return $count;
    }

    public function exportTbx(Glossary $glossary): string
    {
        return $this->bridge->makeGlossaryProvider($glossary)->exportTbx();
    }

    public function addTerm(string $source, string $target, string $domain, Glossary $glossary): void
    {
        $this->bridge->makeGlossaryProvider($glossary)->addTerm($source, $target, $domain);
        DB::table('glossaries')->where('id', $glossary->id)->increment('term_count');
    }

    public function deleteTerm(int $termId, Glossary $glossary): void
    {
        $this->bridge->makeGlossaryProvider($glossary)->deleteTerm($termId);
        DB::table('glossaries')->where('id', $glossary->id)->decrement('term_count');
    }

    public function createForProject(Project $project): Glossary
    {
        $sqlitePath = "glossaries/{$project->team_id}/{$project->id}.db";

        return Glossary::create([
            'team_id' => $project->team_id,
            'project_id' => $project->id,
            'name' => $project->name . ' Glossary',
            'source_lang' => $project->source_lang,
            'target_lang' => $project->target_lang,
            'sqlite_path' => $sqlitePath,
            'is_global' => false,
        ]);
    }

    public function paginate(Glossary $glossary, int $perPage): array
    {
        return $this->bridge->makeGlossaryProvider($glossary)->paginate($perPage);
    }
}
