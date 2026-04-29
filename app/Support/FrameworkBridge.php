<?php

namespace App\Support;

use App\Models\Glossary;
use App\Models\TranslationMemory;
use CatFramework\Filter\Docx\DocxFilter;
use CatFramework\Filter\Html\HtmlFilter;
use CatFramework\Filter\Plaintext\PlaintextFilter;
use CatFramework\Filter\Po\PoFilter;
use CatFramework\Filter\Pptx\PptxFilter;
use CatFramework\Filter\Xlsx\XlsxFilter;
use CatFramework\Filter\Xml\XmlFilter;
use CatFramework\Mt\AzureTranslatorAdapter;
use CatFramework\Mt\DeepLAdapter;
use CatFramework\Mt\GoogleTranslateAdapter;
use CatFramework\Segmentation\SrxSegmentationEngine;
use CatFramework\Terminology\SqliteTerminologyProvider;
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
            'docx' => new DocxFilter,
            'html', 'htm' => new HtmlFilter,
            'pptx' => new PptxFilter,
            'xlsx' => new XlsxFilter,
            'txt' => new PlaintextFilter,
            'po', 'pot' => new PoFilter,
            'xml' => new XmlFilter,
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    public function makeSegmentationEngine(): mixed
    {
        $srxFile = config('catframework.segmentation.srx_file');

        return new SrxSegmentationEngine($srxFile);
    }

    public function makeTmProvider(TranslationMemory $tm): mixed
    {
        $pdo = DB::connection()->getPdo();

        return new PostgresTmProvider($pdo, $tm->id);
    }

    public function makeGlossaryProvider(Glossary $glossary): mixed
    {
        $dbPath = storage_path('app/'.$glossary->sqlite_path);

        return new SqliteTerminologyProvider($dbPath);
    }

    public function makeMtAdapter(string $provider, string $apiKey): mixed
    {
        return match ($provider) {
            'deepl' => new DeepLAdapter($apiKey),
            'google' => new GoogleTranslateAdapter($apiKey),
            'azure' => new AzureTranslatorAdapter($apiKey),
            default => throw new \InvalidArgumentException("Unknown MT provider: {$provider}"),
        };
    }
}
