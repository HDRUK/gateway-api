<?php

namespace App\Context;

use Illuminate\Http\Request;
use App\Http\Resources\DatasetResource;
use App\Models\Dataset;

/**
 * Resolves the active partner and maps entity types to their Resource classes.
 *
 * The partner is determined (in priority order) from:
 *   1. The x-partner-context request header
 *   2. The DEFAULT_PARTNER_CONTEXT environment variable
 *   3. The fallback hard-coded default: HDRUK
 *
 * To add a new partner integration:
 *   1. Create a Resource subclass (e.g. app/Http/Resources/PartnerXDatasetResource.php)
 *   2. Add the partner key and its resource map to config/partners.php
 *   3. Set DEFAULT_PARTNER_CONTEXT=PARTNER_X in the partner environment
 *      (or send x-partner-context: PARTNER_X per-request)
 *
 * Example config/partners.php entry:
 *   'PARTNER_X' => [
 *       Dataset::class => \App\Http\Resources\PartnerXDatasetResource::class,
 *   ],
 */
class PartnerContext
{
    private string $partner;

    public function __construct(Request $request)
    {
        $this->partner = $request->header(
            'x-partner-context',
            config('partners.default', 'HDRUK')
        );
    }

    public function getPartner(): string
    {
        return $this->partner;
    }

    /**
     * Resolve the Resource class to use for a given model class.
     *
     * Falls back to the HDRUK default map if the partner has no specific
     * override for the requested model.
     *
     * @param  class-string  $modelClass  e.g. Dataset::class
     * @return class-string               e.g. DatasetResource::class
     */
    public function resourceFor(string $modelClass): string
    {
        $partnerMap = config('partners.resources.' . $this->partner, []);

        if (isset($partnerMap[$modelClass])) {
            return $partnerMap[$modelClass];
        }

        return $this->defaultResourceFor($modelClass);
    }

    /**
     * Resolve the index (listing) Resource class for a given model class.
     * Partners that need a custom listing shape override this entry in config.
     *
     * @param  class-string  $modelClass
     * @return class-string
     */
    public function indexResourceFor(string $modelClass): string
    {
        $partnerMap = config('partners.index_resources.' . $this->partner, []);

        if (isset($partnerMap[$modelClass])) {
            return $partnerMap[$modelClass];
        }

        return $this->defaultIndexResourceFor($modelClass);
    }

    private function defaultResourceFor(string $modelClass): string
    {
        return config('partners.resources.HDRUK.' . $modelClass, $modelClass);
    }

    private function defaultIndexResourceFor(string $modelClass): string
    {
        return config('partners.index_resources.HDRUK.' . $modelClass, $modelClass);
    }
}
