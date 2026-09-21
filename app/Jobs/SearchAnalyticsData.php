<?php

namespace App\Jobs;

final readonly class SearchAnalyticsData
{
    public function __construct(
        public string $uuid,
        public string $entityType,
        public ?string $searchTerm,
        public array $filters,
        public string $dataSource,
        public array $entityIds,
        public int $entitiesReturned,
    ) {}
}
