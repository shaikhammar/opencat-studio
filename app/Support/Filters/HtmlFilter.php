<?php

namespace App\Support\Filters;

use DOMDocument;
use DOMElement;

class HtmlFilter
{
    private const BLOCK_TAGS = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'td', 'th', 'caption', 'dt', 'dd', 'figcaption', 'blockquote', 'title'];

    private array $segments = [];

    private int $seq = 0;

    public function extract(string $contents): FilterResult
    {
        $this->segments = [];
        $this->seq = 0;

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$contents, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $document = new FilterDocument;

        foreach (self::BLOCK_TAGS as $tag) {
            foreach ($dom->getElementsByTagName($tag) as $el) {
                if (! $el instanceof DOMElement) {
                    continue;
                }
                $text = trim($el->textContent);
                if ($text === '') {
                    continue;
                }
                $this->seq++;
                $token = "{{SEG:{$this->seq}}}";
                $this->segments[$this->seq] = $text;

                while ($el->firstChild) {
                    $el->removeChild($el->firstChild);
                }
                $el->appendChild($dom->createTextNode($token));

                $document->addSegmentPair(new FilterSegmentPair($text));
            }
        }

        $skeleton = json_encode([
            'html' => $dom->saveHTML(),
            'segments' => $this->segments,
        ], JSON_UNESCAPED_UNICODE);

        return new FilterResult($document, $skeleton);
    }

    public function rebuild(FilterDocument $document, string $skeleton): string
    {
        $data = json_decode($skeleton, true);
        $html = $data['html'];
        $i = 1;

        foreach ($document->getSegmentPairs() as $pair) {
            $target = $pair->getTargetText() !== '' ? $pair->getTargetText() : $pair->getSourceText();
            $html = str_replace("{{SEG:{$i}}}", htmlspecialchars($target, ENT_HTML5), $html);
            $i++;
        }

        return $html;
    }
}
