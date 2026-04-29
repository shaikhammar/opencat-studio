<?php

namespace App\Support\Filters;

class PoFilter
{
    public function extract(string $contents): FilterResult
    {
        $document = new FilterDocument;
        $entries = $this->parse($contents);
        $skeletonEntries = [];

        foreach ($entries as $i => $entry) {
            if ($entry['msgid'] === '') {
                continue;
            }
            $document->addSegmentPair(new FilterSegmentPair($entry['msgid']));
            $skeletonEntries[] = [
                'comments' => $entry['comments'],
                'msgid' => $entry['msgid'],
                'msgstr' => $entry['msgstr'],
                'index' => $i,
            ];
        }

        return new FilterResult($document, json_encode($skeletonEntries, JSON_UNESCAPED_UNICODE));
    }

    public function rebuild(FilterDocument $document, string $skeleton): string
    {
        $entries = json_decode($skeleton, true);
        $pairs = $document->getSegmentPairs();
        $pairIdx = 0;
        $lines = [];

        foreach ($entries as $entry) {
            foreach ($entry['comments'] as $comment) {
                $lines[] = $comment;
            }
            $lines[] = 'msgid '.$this->quoteString($entry['msgid']);

            $pair = $pairs[$pairIdx] ?? null;
            $target = ($pair && $pair->getTargetText() !== '') ? $pair->getTargetText() : '';
            $lines[] = 'msgstr '.$this->quoteString($target);
            $lines[] = '';
            $pairIdx++;
        }

        return implode("\n", $lines);
    }

    private function parse(string $contents): array
    {
        $entries = [];
        $current = ['comments' => [], 'msgid' => '', 'msgstr' => ''];
        $inMsgid = false;
        $inMsgstr = false;

        foreach (explode("\n", $contents) as $line) {
            $line = rtrim($line);

            if (str_starts_with($line, '#')) {
                if ($current['msgid'] !== '' || $current['msgstr'] !== '') {
                    $entries[] = $current;
                    $current = ['comments' => [], 'msgid' => '', 'msgstr' => ''];
                }
                $current['comments'][] = $line;
                $inMsgid = $inMsgstr = false;
            } elseif (str_starts_with($line, 'msgid ')) {
                $current['msgid'] = $this->unquoteString(substr($line, 6));
                $inMsgid = true;
                $inMsgstr = false;
            } elseif (str_starts_with($line, 'msgstr ')) {
                $current['msgstr'] = $this->unquoteString(substr($line, 7));
                $inMsgstr = true;
                $inMsgid = false;
            } elseif (str_starts_with($line, '"') && ($inMsgid || $inMsgstr)) {
                $part = $this->unquoteString($line);
                if ($inMsgid) {
                    $current['msgid'] .= $part;
                } else {
                    $current['msgstr'] .= $part;
                }
            } elseif (trim($line) === '') {
                if ($current['msgid'] !== '' || ! empty($current['comments'])) {
                    $entries[] = $current;
                    $current = ['comments' => [], 'msgid' => '', 'msgstr' => ''];
                }
                $inMsgid = $inMsgstr = false;
            }
        }

        if ($current['msgid'] !== '' || ! empty($current['comments'])) {
            $entries[] = $current;
        }

        return $entries;
    }

    private function unquoteString(string $s): string
    {
        $s = trim($s);
        if (str_starts_with($s, '"') && str_ends_with($s, '"')) {
            $s = substr($s, 1, -1);
        }

        return stripcslashes($s);
    }

    private function quoteString(string $s): string
    {
        return '"'.addcslashes($s, "\\\"\n\r\t").'"';
    }
}
