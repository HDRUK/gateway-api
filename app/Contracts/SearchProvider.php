<?php

namespace App\Contracts;

interface SearchProvider
{
    /**
     * Utility function to return full name of Search Provider.
     */
    public function getFullName(): string;
    /**
     * Utility function to return short name of Search Provider.
     */
    public function getShortName(): string;
    /**
     * Utility function to return associated logo for Search Provider.
     */
    public function getProviderLogo(): string|null;
    /**
     * Utility function to return associated information for Search Provider.
     */
    public function getProviderBlurb(): string|null;
    /**
     * Utility function to return search endpoint for Search Provider.
     */
    public function getSearchURI(string $type): string;
    /**
     * Utility function that denotes available search types for Search Provider.
     */
    public function getSupportedTypes(): array;
    /**
     * Determines if this is an internal (immediate) search, or whether it is off-loaded
     * to a queue for processing.
     */
    public function isDeferred(): bool;
    /**
     * Utility function to determine if this Search Provider offers indexing via
     * Typesense deployment.
     */
    public function isTypesenseEnabled(): bool;

    /**
     * Core interface function which provies the main search functionality for a Search Provider.
     *
     * @return array{source?: string, hits: array, total: int, aggregations: array, ids: array}
     */
    public function search(string $query, string $type, array $params = []): array;
}
