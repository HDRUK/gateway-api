<?php

namespace App\Contracts;

use App\SearchProviders\PartnerPortalResult;

interface PartnerPortal extends SearchProvider
{
    public function getProviderLogo(): string;

    /**
     * @param array{hits: array, total: int} $searchResult
     */
    public function toPartnerPortalResult(array $searchResult, string $query): PartnerPortalResult;
}
