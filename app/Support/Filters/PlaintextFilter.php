<?php

namespace App\Support\Filters;

class PlaintextFilter
{
    public function extract(string $contents): FilterResult
    {
        $document = new FilterDocument;
        $lines = preg_split('/\r?\n/', $contents) ?: [];
        $segments = [];

        foreach ($lines as $line) {
            $text = trim($line);
            if ($text !== '') {
                $document->addSegmentPair(new FilterSegmentPair($text));
                $segments[] = $text;
            }
        }

        $skeleton = json_encode($segments, JSON_UNESCAPED_UNICODE);

        return new FilterResult($document, $skeleton);
    }

    public function rebuild(FilterDocument $document, string $skeleton): string
    {
        $lines = [];
        foreach ($document->getSegmentPairs() as $pair) {
            $text = $pair->getTargetText() !== '' ? $pair->getTargetText() : $pair->getSourceText();
            $lines[] = $text;
        }

        return implode("\n", $lines)."\n";
    }
}
