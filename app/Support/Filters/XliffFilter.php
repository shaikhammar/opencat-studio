<?php

namespace App\Support\Filters;

use DOMDocument;
use DOMElement;

class XliffFilter
{
    public function extract(string $contents): FilterResult
    {
        $document = new FilterDocument;
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadXML($contents);
        libxml_clear_errors();

        $units = $dom->getElementsByTagName('trans-unit');
        $seq = 0;

        foreach ($units as $unit) {
            if (! $unit instanceof DOMElement) {
                continue;
            }
            $sourceNodes = $unit->getElementsByTagName('source');
            if ($sourceNodes->length === 0) {
                continue;
            }
            $sourceText = trim($sourceNodes->item(0)->textContent);
            if ($sourceText === '') {
                continue;
            }

            $seq++;
            $token = "{{SEG:{$seq}}}";

            $targetNodes = $unit->getElementsByTagName('target');
            if ($targetNodes->length > 0) {
                $targetEl = $targetNodes->item(0);
                while ($targetEl->firstChild) {
                    $targetEl->removeChild($targetEl->firstChild);
                }
                $targetEl->appendChild($dom->createTextNode($token));
            } else {
                $targetEl = $dom->createElement('target', $token);
                $unit->appendChild($targetEl);
            }

            $document->addSegmentPair(new FilterSegmentPair($sourceText));
        }

        $skeleton = json_encode([
            'xml' => $dom->saveXML(),
        ], JSON_UNESCAPED_UNICODE);

        return new FilterResult($document, $skeleton);
    }

    public function rebuild(FilterDocument $document, string $skeleton): string
    {
        $data = json_decode($skeleton, true);
        $xml = $data['xml'];
        $i = 1;

        foreach ($document->getSegmentPairs() as $pair) {
            $token = "{{SEG:{$i}}}";
            $target = $pair->getTargetText() !== '' ? $pair->getTargetText() : $pair->getSourceText();
            $xml = str_replace($token, htmlspecialchars($target, ENT_XML1 | ENT_QUOTES), $xml);
            $i++;
        }

        return $xml;
    }
}
