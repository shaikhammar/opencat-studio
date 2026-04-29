<?php

namespace App\Support\Filters;

use DOMDocument;
use DOMElement;
use DOMText;

class XmlFilter
{
    private int $seq = 0;

    public function extract(string $contents): FilterResult
    {
        $this->seq = 0;

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        libxml_use_internal_errors(true);
        $dom->loadXML($contents);
        libxml_clear_errors();

        $document = new FilterDocument;
        $segMap = [];

        if ($dom->documentElement instanceof DOMElement) {
            $this->walk($dom->documentElement, $dom, $document, $segMap);
        }

        $skeleton = json_encode([
            'xml' => $dom->saveXML(),
            'seg_map' => $segMap,
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

    private function walk(DOMElement $element, DOMDocument $dom, FilterDocument $document, array &$segMap): void
    {
        if ($this->hasDirectText($element)) {
            $text = trim($element->textContent);
            if ($text === '') {
                return;
            }
            $this->seq++;
            $token = "{{SEG:{$this->seq}}}";
            $segMap[$this->seq] = $token;

            while ($element->firstChild) {
                $element->removeChild($element->firstChild);
            }
            $element->appendChild($dom->createTextNode($token));

            $document->addSegmentPair(new FilterSegmentPair($text));
        } else {
            foreach (iterator_to_array($element->childNodes) as $child) {
                if ($child instanceof DOMElement) {
                    $this->walk($child, $dom, $document, $segMap);
                }
            }
        }
    }

    private function hasDirectText(DOMElement $element): bool
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMText && trim($child->nodeValue) !== '') {
                return true;
            }
        }

        return false;
    }
}
