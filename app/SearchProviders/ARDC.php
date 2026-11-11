<?php

namespace App\SearchProviders;

use Auditor;
use Http;
use App\Contracts\PartnerPortal;

class ARDC implements PartnerPortal
{
    private const PORTAL_SEARCH_URL = 'https://researchdata.edu.au/health/search?filter-type=all-fields';
    private const PORTAL_VIEW_URL   = 'https://researchdata.edu.au/health/view';

    public function isDeferred(): bool
    {
        return true;
    }

    public function getFullName(): string
    {
        return 'Australian Research Data Commons';
    }

    public function getShortName(): string
    {
        return 'ARDC';
    }

    public function getProviderLogo(): string
    {
        return 'https://demo.researchdata.ardc.edu.au/hd-portal/images/ardc-logo.svg';
    }

    public function getProviderBlurb(): string|null
    {
        return '<b>ABOUT THE ARDC</b>
            <p>At the Australian Research Data Commons (ARDC), we\'re accelerating Australian research and innovation by driving excellence in the creation, analysis and retention of high-quality data assets.</p>
            <p>We partner with the research community and industry to build leading-edge digital research infrastructure to provide Australian researchers with competitive advantage through data.</p>';
    }

    public function getSearchURI(string $type): string
    {
        return 'https://researchdata.edu.au/registry/services/registry/post_solr_search';
    }

    public function getSupportedTypes(): array
    {
        return ['datasets'];
    }

    public function isTypesenseEnabled(): bool
    {
        return false;
    }

    public function search(string $query, string $type, array $params = []): array
    {
        try {
            $response = Http::post($this->getSearchURI($type), [
                'filters' => [
                    'q'    => empty($query) ? false : $query,
                    'type' => 'health.dataset',
                    'start' => 0,
                    'rows' => 25,
                ],
            ]);

            if (!$response->successful()) {
                return ['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []];
            }

            $incoming = $response->json();

            if (!isset($incoming['result']['docs']) || !is_array($incoming['result']['docs'])) {
                return ['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []];
            }

            $hits = array_values($incoming['result']['docs']);

            return [
                'hits'         => $hits,
                'total'        => $incoming['result']['numFound'],
                'aggregations' => [],
                'ids'          => [],
            ];
        } catch (\Throwable $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::error($e->getMessage());
        }

        return ['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []];
    }

    public function toPartnerPortalResult(array $searchResult, string $query): PartnerPortalResult
    {
        return new PartnerPortalResult(
            name: $this->getFullName(),
            logo: $this->getProviderLogo(),
            searchUrl: self::PORTAL_SEARCH_URL . ($query === '' ? '' : '&q=' . rawurlencode($query)),
            total: (int) $searchResult['total'],
            hits: array_map($this->toPartnerHit(...), $searchResult['hits']),
        );
    }

    private function toPartnerHit(array $doc): PartnerHit
    {
        $id = (string) ($doc['id'] ?? '');

        return new PartnerHit(
            id: $id,
            title: ($doc['display_title'] ?? null) ?: ($doc['title'] ?? null) ?: '',
            description: ($doc['list_description'] ?? null) ?: ($doc['description'] ?? null) ?: '',
            organisation: ($doc['group'] ?? null) ?: null,
            url: $id === '' ? self::PORTAL_VIEW_URL : self::PORTAL_VIEW_URL . '/' . $id,
        );
    }
}
