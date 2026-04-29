<?php

namespace App\Support\Filters;

use DOMDocument;
use DOMElement;
use RuntimeException;
use ZipArchive;

class XlsxFilter
{
    public function extract(string $contents): FilterResult
    {
        $tmp = $this->writeTempFile($contents, 'xlsx');

        try {
            $zip = new ZipArchive;
            if ($zip->open($tmp) !== true) {
                throw new RuntimeException('Cannot open XLSX archive.');
            }

            // XLSX stores unique strings in xl/sharedStrings.xml.
            $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
            $zip->close();

            if ($sharedXml === false) {
                // No shared strings — nothing to translate.
                return new FilterResult(new FilterDocument, json_encode(['zip' => base64_encode($contents)]));
            }

            $dom = new DOMDocument('1.0', 'UTF-8');
            libxml_use_internal_errors(true);
            $dom->loadXML($sharedXml);
            libxml_clear_errors();

            $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $document = new FilterDocument;
            $seq = 0;

            foreach ($dom->getElementsByTagNameNS($ns, 'si') as $si) {
                if (! $si instanceof DOMElement) {
                    continue;
                }
                // Collect all <t> text within the <si>
                $text = '';
                foreach ($si->getElementsByTagNameNS($ns, 't') as $t) {
                    $text .= $t->textContent;
                }
                if (trim($text) === '') {
                    continue;
                }

                $seq++;
                $token = "{{SEG:{$seq}}}";

                // Replace all child nodes with a single <t> holding the token.
                while ($si->firstChild) {
                    $si->removeChild($si->firstChild);
                }
                $tEl = $dom->createElementNS($ns, 't', $token);
                $si->appendChild($tEl);

                $document->addSegmentPair(new FilterSegmentPair(trim($text)));
            }

            $modifiedShared = $dom->saveXML();
            $skeletonZip = $this->rebuildZip($contents, ['xl/sharedStrings.xml' => $modifiedShared], 'xlsx');

            $skeleton = json_encode(['zip' => base64_encode($skeletonZip)]);
        } finally {
            @unlink($tmp);
        }

        return new FilterResult($document, $skeleton);
    }

    public function rebuild(FilterDocument $document, string $skeleton): string
    {
        $data = json_decode($skeleton, true);
        $zipBytes = base64_decode($data['zip']);

        $tmp = $this->writeTempFile($zipBytes, 'xlsx');

        try {
            $zip = new ZipArchive;
            $zip->open($tmp);
            $sharedXml = $zip->getFromName('xl/sharedStrings.xml') ?: '';
            $zip->close();

            $i = 1;
            foreach ($document->getSegmentPairs() as $pair) {
                $token = "{{SEG:{$i}}}";
                $target = $pair->getTargetText() !== '' ? $pair->getTargetText() : $pair->getSourceText();
                $sharedXml = str_replace($token, htmlspecialchars($target, ENT_XML1 | ENT_QUOTES), $sharedXml);
                $i++;
            }

            return $this->rebuildZip($zipBytes, ['xl/sharedStrings.xml' => $sharedXml], 'xlsx');
        } finally {
            @unlink($tmp);
        }
    }

    private function writeTempFile(string $bytes, string $ext): string
    {
        $tmp = sys_get_temp_dir().'/filter_'.uniqid().'.'.$ext;
        file_put_contents($tmp, $bytes);

        return $tmp;
    }

    private function rebuildZip(string $originalBytes, array $overrides, string $ext): string
    {
        $original = $this->writeTempFile($originalBytes, $ext);
        $output = $this->writeTempFile('', $ext);

        try {
            copy($original, $output);
            $zip = new ZipArchive;
            $zip->open($output);
            foreach ($overrides as $name => $content) {
                $zip->addFromString($name, $content);
            }
            $zip->close();

            return file_get_contents($output);
        } finally {
            @unlink($original);
            @unlink($output);
        }
    }
}
