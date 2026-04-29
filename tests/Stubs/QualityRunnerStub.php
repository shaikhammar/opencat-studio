<?php

namespace CatFramework\Qa;

/** Test stub for the unimplemented catframework/qa package. */
if (! class_exists(QualityRunner::class)) {
    class QualityRunner
    {
        public function __construct(array $config = []) {}

        public function run(mixed $document): array
        {
            return [];
        }
    }
}
