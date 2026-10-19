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
 * Every partner has a default (config/partners.php), so step 3 means reads are
 * translated unless a caller opts out. A caller asks for the stored GWDM by
 * requesting the GWDM model explicitly — ?schema_model=GWDM or
 * x-schema-model: GWDM — which DatasetService@prepareForShow reads as
 * "no translation". Removing step 3 instead would silently change the payload
 * for every partner that relies on its default.
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
}
