<?php

namespace App\Support;

use App\Models\Glossary;
use App\Models\TranslationMemory;
use Illuminate\Support\Facades\DB;

/**
 * Singleton that constructs catframework/* objects from Laravel config + DB connections.
 * All services that need framework objects should inject this class, not the framework
 * classes directly — this keeps the Laravel/catframework boundary in one place.
 */
class FrameworkBridge
{
    public function makeFileFilter(string $format): mixed
    {
        return match (strtolower($format)) {
            'docx' => new \CatFramework\Filter\Docx\DocxFilter(),
            'html', 'htm' => new \CatFramework\Filter\Html\HtmlFilter(),
            'pptx' => new \CatFramework\Filter\Pptx\PptxFilter(),
            'xlsx' => new \CatFramework\Filter\Xlsx\XlsxFilter(),
            'txt' => new \CatFramework\Filter\Plaintext\PlaintextFilter(),
            'po', 'pot' => new \CatFramework\Filter\Po\PoFilter(),
            'xml' => new \CatFramework\Filter\Xml\XmlFilter(),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    public function makeSegmentationEngine(): mixed
    {
        $srxFile = config('catframework.segmentation.srx_file');

        return new \CatFramework\Segmentation\SrxSegmentationEngine($srxFile);
    }

    public function makeTmProvider(TranslationMemory $tm): mixed
    {
        $pdo = DB::connection()->getPdo();

        return new \CatFramework\TranslationMemory\PostgresTranslationMemoryProvider($pdo, $tm->id);
    }

    public function makeGlossaryProvider(Glossary $glossary): mixed
    {
        $dbPath = storage_path('app/' . $glossary->sqlite_path);

        return new \CatFramework\Terminology\SqliteTerminologyProvider($dbPath);
    }

    public function makeMtAdapter(string $provider, string $apiKey): mixed
    {
        return match ($provider) {
            'deepl' => new \CatFramework\Mt\DeepLAdapter($apiKey),
            'google' => new \CatFramework\Mt\GoogleTranslateAdapter($apiKey),
            'azure' => new \CatFramework\Mt\AzureTranslatorAdapter($apiKey),
            default => throw new \InvalidArgumentException("Unknown MT provider: {$provider}"),
        };
    }
}
