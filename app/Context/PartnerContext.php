<?php

namespace App\Context;

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
    public function getPartner(): string
    {
        return request()->header(
            'x-partner-context',
            config('partners.default', 'HDRUK')
        );
    }

    /**
     * The partner contexts the active partner may read and render.
     *
     * @return string[]|null  null means unrestricted (no partner_context filter)
     */
    public function readableContexts(): ?array
    {
        return self::readableContextsFor($this->getPartner());
    }

    /**
     * The partner contexts the given partner may read and render.
     *
     * Non-HDRUK partners are always isolated to their own context. HDRUK (the
     * gateway operator) is governed by partners.allow_cross_context_read:
     *   true  / 'true'    → unrestricted (null)
     *   false / 'false'   → ['HDRUK']
     *   'HDRUK,CRUK,...'  → the listed contexts, HDRUK always included
     *
     * Static and config-driven so callers holding a mocked PartnerContext
     * (e.g. search providers) can resolve it from the partner string alone.
     *
     * @return string[]|null
     */
    public static function readableContextsFor(string $partner): ?array
    {
        if ($partner !== 'HDRUK') {
            return [$partner];
        }

        $setting = config('partners.allow_cross_context_read', true);

        if (is_string($setting) && in_array(strtolower(trim($setting)), ['true', 'false', ''], true)) {
            $setting = strtolower(trim($setting)) === 'true';
        }

        if ($setting === true) {
            return null;
        }

        if ($setting === false || $setting === null) {
            return ['HDRUK'];
        }

        $listed = is_array($setting) ? $setting : explode(',', (string) $setting);
        $listed = array_filter(array_map(fn ($c) => trim((string) $c), $listed), fn ($c) => $c !== '');

        return array_values(array_unique(array_merge(['HDRUK'], $listed)));
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
        $partnerMap = config('partners.resources.' . $this->getPartner(), []);

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
        $partnerMap = config('partners.index_resources.' . $this->getPartner(), []);

        if (isset($partnerMap[$modelClass])) {
            return $partnerMap[$modelClass];
        }

        return $this->defaultIndexResourceFor($modelClass);
    }

    public function defaultSchemaModel(): ?string
    {
        return config('partners.schema.' . $this->getPartner() . '.model') ?: null;
    }

    public function defaultSchemaVersion(): ?string
    {
        return config('partners.schema.' . $this->getPartner() . '.version') ?: null;
    }

    private function defaultResourceFor(string $modelClass): string
    {
        return config('partners.resources.HDRUK', [])[$modelClass] ?? $modelClass;
    }

    private function defaultIndexResourceFor(string $modelClass): string
    {
        return config('partners.index_resources.HDRUK', [])[$modelClass] ?? $modelClass;
    }
}
