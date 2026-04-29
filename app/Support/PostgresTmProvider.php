<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;

/**
 * Direct Postgres-backed translation memory provider.
 * Implements the contract expected by TmService until catframework/translation-memory is ready.
 */
class PostgresTmProvider
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $tmId,
    ) {}

    /**
     * @return array<int, array{source_text: string, target_text: string, percent: int, origin: string, diff_tokens: array}>
     */
    public function lookup(string $sourceText, string $sourceLang, string $targetLang, int $threshold): array
    {
        if (trim($sourceText) === '') {
            return [];
        }

        $normalized = mb_strtolower(trim($sourceText));
        $minSimilarity = $threshold / 100;

        $stmt = $this->pdo->prepare('
            SELECT source_text, target_text,
                   LEAST(1.0, similarity(source_text_normalized, :norm)) AS score
            FROM tm_units
            WHERE tm_id = :tm_id
              AND source_lang = :source_lang
              AND target_lang = :target_lang
            ORDER BY score DESC
            LIMIT 10
        ');

        $stmt->execute([
            ':norm' => $normalized,
            ':tm_id' => $this->tmId,
            ':source_lang' => $sourceLang,
            ':target_lang' => $targetLang,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $matches = [];

        foreach ($rows as $row) {
            $score = (float) $row['score'];
            if ($score < $minSimilarity) {
                continue;
            }
            $percent = (int) round($score * 100);
            $matches[] = [
                'source_text' => $row['source_text'],
                'target_text' => $row['target_text'],
                'percent' => $percent,
                'origin' => $percent === 100 ? 'exact' : 'tm',
                'diff_tokens' => [],
            ];
        }

        return $matches;
    }

    public function store(string $source, string $target, string $sourceLang, string $targetLang): void
    {
        $normalized = mb_strtolower(trim($source));

        $stmt = $this->pdo->prepare('
            INSERT INTO tm_units (id, tm_id, source_lang, target_lang, source_text, target_text,
                                  source_segment, target_segment, source_text_normalized, created_at)
            VALUES (:id, :tm_id, :source_lang, :target_lang, :source, :target,
                    :source_segment, :target_segment, :normalized, NOW())
            ON CONFLICT (tm_id, source_lang, target_lang, source_text_normalized)
            DO UPDATE SET target_text = EXCLUDED.target_text,
                          target_segment = EXCLUDED.target_segment,
                          last_used_at = NOW()
        ');

        $stmt->execute([
            ':id' => (string) Str::orderedUuid(),
            ':tm_id' => $this->tmId,
            ':source_lang' => $sourceLang,
            ':target_lang' => $targetLang,
            ':source' => $source,
            ':target' => $target,
            ':source_segment' => $source,
            ':target_segment' => $target,
            ':normalized' => $normalized,
        ]);
    }

    public function importTmx(string $tmxStoragePath): int
    {
        $absolutePath = Storage::path($tmxStoragePath);

        if (! file_exists($absolutePath)) {
            throw new \RuntimeException("TMX file not found: {$absolutePath}");
        }

        $xml = simplexml_load_file($absolutePath);
        if ($xml === false) {
            throw new \RuntimeException('Invalid TMX file.');
        }

        $count = 0;

        foreach ($xml->body->tu ?? [] as $tu) {
            $srcTuv = null;
            $tgtTuv = null;
            $srcLang = null;
            $tgtLang = null;

            foreach ($tu->tuv as $tuv) {
                $lang = strtolower((string) ($tuv->attributes('xml', true)['lang'] ?? $tuv['lang'] ?? ''));
                if ($srcTuv === null) {
                    $srcTuv = (string) $tuv->seg;
                    $srcLang = $lang;
                } else {
                    $tgtTuv = (string) $tuv->seg;
                    $tgtLang = $lang;
                }
            }

            if ($srcTuv !== null && $tgtTuv !== null && $srcLang && $tgtLang) {
                $this->store($srcTuv, $tgtTuv, $srcLang, $tgtLang);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Export all TM entries as a TMX file and return the storage-relative path.
     */
    public function exportTmx(): string
    {
        $stmt = $this->pdo->prepare('
            SELECT source_lang, target_lang, source_text, target_text, created_at
            FROM tm_units
            WHERE tm_id = :tm_id
            ORDER BY created_at
        ');
        $stmt->execute([':tm_id' => $this->tmId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $creationDate = now()->toAtomString();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<tmx version="1.4">'."\n";
        $xml .= '  <header creationtool="OpenCAT Studio" creationtoolversion="1.0" datatype="PlainText"';
        $xml .= ' segtype="sentence" adminlang="en" srclang="*all*"'."\n";
        $xml .= '          creationdate="'.htmlspecialchars($creationDate).'"/>'."\n";
        $xml .= '  <body>'."\n";

        foreach ($rows as $row) {
            $xml .= '    <tu>'."\n";
            $xml .= '      <tuv xml:lang="'.htmlspecialchars($row['source_lang']).'">'."\n";
            $xml .= '        <seg>'.htmlspecialchars($row['source_text']).'</seg>'."\n";
            $xml .= '      </tuv>'."\n";
            $xml .= '      <tuv xml:lang="'.htmlspecialchars($row['target_lang']).'">'."\n";
            $xml .= '        <seg>'.htmlspecialchars($row['target_text']).'</seg>'."\n";
            $xml .= '      </tuv>'."\n";
            $xml .= '    </tu>'."\n";
        }

        $xml .= '  </body>'."\n";
        $xml .= '</tmx>'."\n";

        $path = 'tmp/tmx/export_'.Str::ulid().'.tmx';
        Storage::put($path, $xml);

        return $path;
    }

    /**
     * @return array<int, array{id: int, source_text: string, target_text: string, source_lang: string, target_lang: string, created_at: string}>
     */
    public function concordance(string $query, int $limit): array
    {
        $normalized = '%'.mb_strtolower(trim($query)).'%';

        $stmt = $this->pdo->prepare('
            SELECT id, source_text, target_text, source_lang, target_lang, created_at
            FROM tm_units
            WHERE tm_id = :tm_id
              AND (LOWER(source_text) LIKE :query OR LOWER(target_text) LIKE :query)
            ORDER BY created_at DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':tm_id', $this->tmId);
        $stmt->bindValue(':query', $normalized);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
