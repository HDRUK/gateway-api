<?php

namespace App\SearchProviders;

use Http;
use App\Contracts\SearchProvider;

class ARDC implements SearchProvider
{
    private function getDefaultSearchType(): string
    {
        return 'health.dataset';
    }

    public function getFullName(): string
    {
        return 'Australian Research Data Commons';
    }

    public function getShortName(): string
    {
        return 'ARDC';
    }

    public function getProviderLogo(): string|null
    {
        return 'https://demo.researchdata.ardc.edu.au/hd-portal/images/ardc-logo.svg';
    }

    public function getProviderBlurb(): string|null
    {
        return '<b>ABOUT THE ARDC</b>
            <p>At the Australian Research Data Commons (ARDC), we’re accelerating Australian research and innovation by driving excellence in the creation, analysis and retention of high-quality data assets.</p>
            <p>We partner with the research community and industry to build leading-edge digital research infrastructure to provide Australian researchers with competitive advantage through data.</p>';
    }

    public function getSearchURI(): string
    {
        return 'https://researchdata.edu.au/registry/services/registry/post_solr_search';
    }

    public function search(string $query): array
    {
        $response = Http::post($this->getSearchURI(), [
            'filters' => [
                'q' => (empty($query) ? false : $query),
                'type' => $this->getDefaultSearchType(),
            ],
        ]);

        $newArr = [];
        $incoming = $response->json();

        foreach ($incoming['result']['docs'] as $arr) {
            $newArr[] = $arr;
        }

        return $newArr;
    }
}