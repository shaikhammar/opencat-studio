<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TranslationMemory;
use App\Support\FrameworkBridge;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TmService
{
    public function __construct(
        private readonly FrameworkBridge $bridge,
    ) {}

    public function lookup(string $sourceText, string $sourceLang, string $targetLang, int $threshold, TranslationMemory $tm): array
    {
        $provider = $this->bridge->makeTmProvider($tm);

        return $provider->lookup($sourceText, $sourceLang, $targetLang, $threshold);
    }

    public function addEntry(string $source, string $target, string $sourceLang, string $targetLang, TranslationMemory $tm): void
    {
        $provider = $this->bridge->makeTmProvider($tm);
        $provider->store($source, $target, $sourceLang, $targetLang);

        DB::table('translation_memories')
            ->where('id', $tm->id)
            ->increment('entry_count');
    }

    public function importTmx(string $tmxPath, TranslationMemory $tm): int
    {
        $provider = $this->bridge->makeTmProvider($tm);
        $count = $provider->importTmx($tmxPath);

        DB::table('translation_memories')
            ->where('id', $tm->id)
            ->increment('entry_count', $count);

        return $count;
    }

    public function exportTmx(TranslationMemory $tm): string
    {
        $provider = $this->bridge->makeTmProvider($tm);

        return $provider->exportTmx();
    }

    public function search(string $query, TranslationMemory $tm, int $limit): array
    {
        $provider = $this->bridge->makeTmProvider($tm);

        return $provider->concordance($query, $limit);
    }

    public function deleteEntry(int $entryId, TranslationMemory $tm): void
    {
        DB::table('tm_units')->where('id', $entryId)->where('tm_id', $tm->id)->delete();
        DB::table('translation_memories')->where('id', $tm->id)->decrement('entry_count');
    }

    public function createForProject(Project $project): TranslationMemory
    {
        return TranslationMemory::create([
            'team_id' => $project->team_id,
            'project_id' => $project->id,
            'name' => $project->name . ' TM',
            'source_lang' => $project->source_lang,
            'target_lang' => $project->target_lang,
            'is_global' => false,
        ]);
    }

    public function paginate(TranslationMemory $tm, int $perPage): array
    {
        return DB::table('tm_units')
            ->where('tm_id', $tm->id)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->toArray();
    }
}
