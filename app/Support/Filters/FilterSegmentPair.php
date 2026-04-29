<?php

namespace App\Support\Filters;

class FilterSegmentPair
{
    public function __construct(
        private readonly string $sourceText,
        private readonly string $targetText = '',
        private readonly array $sourceTags = [],
        private readonly array $targetTags = [],
    ) {}

    public function getSourceText(): string
    {
        return $this->sourceText;
    }

    public function getTargetText(): string
    {
        return $this->targetText;
    }

    public function getSourceTags(): array
    {
        return $this->sourceTags;
    }

    public function getTargetTags(): array
    {
        return $this->targetTags;
    }
}
