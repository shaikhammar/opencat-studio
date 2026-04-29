<?php

namespace App\Support\Filters;

use DOMDocument;
use DOMElement;
use RuntimeException;
use ZipArchive;

class PptxFilter
{
    private const DRAW_NS = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    public function extract(string $contents): FilterResult
    {
        $tmp = $this->writeTempFile($contents, 'pptx');

        try {
            $zip = new ZipArchive;
            if ($zip->open($tmp) !== true) {
                throw new RuntimeException('Cannot open PPTX archive.');
            }

            $slideFiles = $this->getSlideFiles($zip);
            $overrides = [];
            $document = new FilterDocument;
            $seq = 0;

            foreach ($slideFiles as $slidePath) {
                $slideXml = $zip->getFromName($slidePath);
                if ($slideXml === false) {
                    continue;
                }

                $dom = new DOMDocument('1.0', 'UTF-8');
                libxml_use_internal_errors(true);
                $dom->loadXML($slideXml);
                libxml_clear_errors();

                foreach ($dom->getElementsByTagNameNS(self::DRAW_NS, 'p') as $para) {
                    if (! $para instanceof DOMElement) {
                        continue;
                    }
                    $text = '';
                    foreach ($para->getElementsByTagNameNS(self::DRAW_NS, 'r') as $run) {
                        foreach ($run->getElementsByTagNameNS(self::DRAW_NS, 't') as $t) {
                            $text .= $t->textContent;
                        }
                    }
                    if (trim($text) === '') {
                        continue;
                    }

                    $seq++;
                    $token = "{{SEG:{$seq}}}";

                    // Replace all runs with a single token run.
                    $toRemove = [];
                    foreach ($para->childNodes as $child) {
                        if ($child instanceof DOMElement && $child->localName === 'r') {
                            $toRemove[] = $child;
                        }
                    }
                    foreach ($toRemove as $run) {
                        $para->removeChild($run);
                    }
                    $run = $dom->createElementNS(self::DRAW_NS, 'a:r');
                    $t = $dom->createElementNS(self::DRAW_NS, 'a:t', $token);
                    $run->appendChild($t);
                    $para->appendChild($run);

                    $document->addSegmentPair(new FilterSegmentPair(trim($text)));
                }

                $overrides[$slidePath] = $dom->saveXML();
            }

            $zip->close();
            $skeletonZip = $this->rebuildZip($contents, $overrides, 'pptx');

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

        $tmp = $this->writeTempFile($zipBytes, 'pptx');

        try {
            $zip = new ZipArchive;
            $zip->open($tmp);

            $slideFiles = $this->getSlideFiles($zip);
            $allXml = [];
            foreach ($slideFiles as $path) {
                $allXml[$path] = $zip->getFromName($path);
            }
            $zip->close();

            $i = 1;
            foreach ($document->getSegmentPairs() as $pair) {
                $token = "{{SEG:{$i}}}";
                $target = $pair->getTargetText() !== '' ? $pair->getTargetText() : $pair->getSourceText();
                foreach ($allXml as $path => $xml) {
                    $allXml[$path] = str_replace($token, htmlspecialchars($target, ENT_XML1 | ENT_QUOTES), $xml);
                }
                $i++;
            }

            return $this->rebuildZip($zipBytes, $allXml, 'pptx');
        } finally {
            @unlink($tmp);
        }
    }

    private function getSlideFiles(ZipArchive $zip): array
    {
        $slides = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^ppt/slides/slide\d+\.xml$#', $name)) {
                $slides[] = $name;
            }
        }
        sort($slides);

        return $slides;
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
