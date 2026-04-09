<?php

namespace App\SearchProviders;

use Auditor;

use Http;
use App\Models\Filter;
use App\Models\Dataset;
use App\Contracts\SearchProvider;

class HDRUK implements SearchProvider
{
    private function getDefaultSearchType(): string
    {
        return 'datasets';
    }

    public function getFullName(): string
    {
        return 'Health Data Research UK';
    }

    public function getShortName(): string
    {
        return 'HDRUK';
    }

    public function getProviderLogo(): string|null
    {
        return null;
    }

    public function getProviderBlurb(): string|null
    {
        return null;
    }

    public function getSearchURI(): string {
        return config('gateway.search_service_url') . "/search/{$this->getDefaultSearchType()}";
    }

    public function search(string $query): array
    {
        // try {
            $aggs = Filter::where('type', 'dataset')->where('enabled', 1)->get()->toArray();
            $input['aggs'] = $aggs;

            $response = Http::post($this->getSearchURI(), $input);

            if (!$response->successful()) {
                return response()->json([
                    'message' => 'No response from ' . $this->getSearchURI,
                ], 404);
            }

            $response = $response->json();

            if (
                !isset($response['hits']) || !is_array($response['hits']) ||
                !isset($response['hits']['hits']) || !is_array($response['hits']['hits']) ||
                !isset($response['hits']['total']['value'])
            ) {
                return response()->json([
                    'message' => 'Hits not being properly returned by the search service',
                ], 404);
            }

            $datasetsArray = $response['hits']['hits'];
            $totalResults = $response['hits']['total']['value'];
            $matchedIds = [];

            foreach (array_values($datasetsArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            foreach ($matchedIds as $i => $matchedId) {
                $model = Dataset::with('team')->where('id', $matchedId)->first();

                if (!$model) {
                    continue;
                }

                $latestVersion = $model->latestVersion();
                if (is_null($latestVersion)) {
                    continue;
                }

                $model = $model->toArray();
                $datasetsArray[$i]['_source']['created_at'] = $model['created_at'];
                $datasetsArray[$i]['_source']['updated_at'] = $model['updated_at'];
            }

            $newArr = [];

            foreach ($datasetsArray as $arr) {
                $newArr[] = $arr['_source'];
            }

            return $newArr;

        // } catch (\Throwable $e) {
        //     Auditor::log([
        //         'action_type' => 'EXCEPTION',
        //         'action_name' => class_basename($this) . '@' . __FUNCTION__,
        //         'description' => $e->getMessage(),
        //     ]);
        //     \Log::info($e->getMessage());
        // }
    }
}