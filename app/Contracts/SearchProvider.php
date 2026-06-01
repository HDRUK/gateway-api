<?php

namespace App\Contracts;

interface SearchProvider
{
    public function getFullName(): string;
    public function getShortName(): string;
    public function getProviderLogo(): string|null;
    public function getProviderBlurb(): string|null;
    public function getSearchURI(string $type): string;
    public function getSupportedTypes(): array;

    /**
     * @return array{hits: array, total: int, aggregations: array, ids: array}
     */
    public function search(string $query, string $type, array $params = []): array;
}
