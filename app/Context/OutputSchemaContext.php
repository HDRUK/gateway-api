<?php

namespace App\Context;

/**
 * Resolves the output schema model and version for read operations.
 *
 * HTTP clients (HDRUK portal, CRUK, PRUK, DCAT frontends) can set these headers
 * globally instead of appending ?schema_model=&schema_version= to every URL.
 *
 * Resolution order in controllers:
 *   1. ?schema_model / ?schema_version query params       — highest priority
 *   2. x-schema-model / x-schema-version request headers — this class
 *   3. Partner default from config/partners.php           — derived from PartnerContext
 *   4. null (no translation — return stored GWDM)
 *
 * Dataset reads (V2 DatasetController@showActive) skip step 3 via
 * requestedSchemaModel()/requestedSchemaVersion(): datasets are returned as
 * native GWDM unless a translation is explicitly asked for. Form hydration
 * still uses the partner default via schemaModel()/schemaVersion().
 *
 * Not needed in queued jobs (output translation is a synchronous read concern).
 * No middleware or container binding required — instantiated via auto-resolution.
 */
class OutputSchemaContext
{
    public function __construct(private readonly PartnerContext $partnerContext)
    {
    }

    public function schemaModel(): ?string
    {
        return request()->header('x-schema-model')
            ?: $this->partnerContext->defaultSchemaModel()
            ?: null;
    }

    public function schemaVersion(): ?string
    {
        return request()->header('x-schema-version')
            ?: $this->partnerContext->defaultSchemaVersion()
            ?: null;
    }

    /**
     * The explicitly requested output schema model (x-schema-model header only),
     * with no partner default. Dataset reads use this so that GWDM is returned
     * natively unless the caller asks for a translation.
     */
    public function requestedSchemaModel(): ?string
    {
        return request()->header('x-schema-model') ?: null;
    }

    /**
     * The explicitly requested output schema version (x-schema-version header only).
     */
    public function requestedSchemaVersion(): ?string
    {
        return request()->header('x-schema-version') ?: null;
    }
}
