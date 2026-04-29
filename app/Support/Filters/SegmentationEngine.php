<?php

namespace App\Support\Filters;

/**
 * No-op segmentation engine. Filters already produce paragraph-level units;
 * sentence splitting is deferred until the catframework/segmentation package ships.
 */
class SegmentationEngine
{
    public function segment(FilterDocument $document): void {}
}
