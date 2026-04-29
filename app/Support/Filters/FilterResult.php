<?php

namespace App\Support\Filters;

class FilterResult
{
    public function __construct(
        public readonly FilterDocument $document,
        public readonly string $skeleton,
    ) {}
}
