<?php

namespace App\Services\V3\Dashboard;

readonly class BigQueryQuery
{
    /**
     * @param array<string, int|string> $params
     */
    public function __construct(
        public string $sql,
        public array $params,
    ) {
    }
}
