<?php

namespace App\Support\Filters;

use DOMDocument;
use DOMElement;
use RuntimeException;
use ZipArchive;

class DocxFilter
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function extract(string $contents): FilterResult
    {
        $tmp = $this->writeTempZip($contents);

        try {
            $zip = new ZipArchive;
            if ($zip->open($tmp) !== true) {
                throw new RuntimeException('Cannot open DOCX archive.');
            }

            $docXml = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($docXml === false) {
                throw new RuntimeException('word/document.xml not found in DOCX.');
            }

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = true;
            libxml_use_internal_errors(true);
            $dom->loadXML($docXml);
            libxml_clear_errors();

            $document = new FilterDocument;
            $seq = 0;

            foreach ($dom->getElementsByTagNameNS(self::WORD_NS, 'p') as $para) {
                if (! $para instanceof DOMElement) {
                    continue;
                }
                $text = $this->extractParaText($para);
                if (trim($text) === '') {
                    continue;
                }

                $seq++;
                $token = "{{SEG:{$seq}}}";

                $this->clearRuns($para, $dom, $token);
                $document->addSegmentPair(new FilterSegmentPair(trim($text)));
            }

            $modifiedXml = $dom->saveXML();
            $skeletonZip = $this->rebuildZip($contents, ['word/document.xml' => $modifiedXml]);

            $skeleton = json_encode([
                'zip' => base64_encode($skeletonZip),
            ]);
        } finally {
            @unlink($tmp);
        }

        return new FilterResult($document, $skeleton);
    }

    public function rebuild(FilterDocument $document, string $skeleton): string
    {
        $data = json_decode($skeleton, true);
        $zipBytes = base64_decode($data['zip']);

        $tmp = $this->writeTempZip($zipBytes);

        try {
            $zip = new ZipArchive;
            $zip->open($tmp);
            $docXml = $zip->getFromName('word/document.xml');
            $zip->close();

            $i = 1;
            foreach ($document->getSegmentPairs() as $pair) {
                $token = "{{SEG:{$i}}}";
                $target = $pair->getTargetText() !== '' ? $pair->getTargetText() : $pair->getSourceText();
                $docXml = str_replace($token, htmlspecialchars($target, ENT_XML1 | ENT_QUOTES), $docXml);
                $i++;
            }

            return $this->rebuildZip($zipBytes, ['word/document.xml' => $docXml]);
        } finally {
            @unlink($tmp);
        }
    }

    private function extractParaText(DOMElement $para): string
    {
        $text = '';
        foreach ($para->getElementsByTagNameNS(self::WORD_NS, 't') as $t) {
            $text .= $t->textContent;
        }

        return $text;
    }

    private function clearRuns(DOMElement $para, DOMDocument $dom, string $token): void
    {
        // Remove all runs; keep paragraph properties (w:pPr) intact.
        $toRemove = [];
        foreach ($para->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'r') {
                $toRemove[] = $child;
            }
        }
        foreach ($toRemove as $run) {
            $para->removeChild($run);
        }

        // Insert single run with token text.
        $run = $dom->createElementNS(self::WORD_NS, 'w:r');
        $t = $dom->createElementNS(self::WORD_NS, 'w:t', $token);
        $t->setAttribute('xml:space', 'preserve');
        $run->appendChild($t);
        $para->appendChild($run);
    }

    private function writeTempZip(string $bytes): string
    {
        $tmp = sys_get_temp_dir().'/docx_'.uniqid().'.docx';
        file_put_contents($tmp, $bytes);

        return $tmp;
    }

    private function rebuildZip(string $originalBytes, array $overrides): string
    {
        $original = $this->writeTempZip($originalBytes);
        $output = $this->writeTempZip('');

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
