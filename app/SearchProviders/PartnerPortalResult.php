<?php

namespace App\SearchProviders;

readonly class PartnerPortalResult
{
    /**
     * @param list<PartnerHit> $hits
     */
    public function __construct(
        public string $name,
        public string $logo,
        public string $searchUrl,
        public int $total,
        public array $hits,
    ) {
    }

    /**
     * @return array{name: string, logo: string, search_url: string, total: int, hits: list<array<string, ?string>>}
     */
    public function toArray(): array
    {
        return [
            'name'       => $this->name,
            'logo'       => $this->logo,
            'search_url' => $this->searchUrl,
            'total'      => $this->total,
            'hits'       => array_map(fn (PartnerHit $hit) => $hit->toArray(), $this->hits),
        ];
    }
}
