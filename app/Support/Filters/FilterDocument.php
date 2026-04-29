<?php

namespace App\Support\Filters;

class FilterDocument
{
    /** @var FilterSegmentPair[] */
    private array $pairs = [];

    public function addSegmentPair(FilterSegmentPair $pair): void
    {
        $this->pairs[] = $pair;
    }

    /** @return FilterSegmentPair[] */
    public function getSegmentPairs(): array
    {
        return $this->pairs;
    }
}
