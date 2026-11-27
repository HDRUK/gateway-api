<?php

namespace App\Services;

readonly class DatasetTitles
{
    public function __construct(
        public ?string $title,
        public ?string $shortTitle,
    ) {
    }
}
