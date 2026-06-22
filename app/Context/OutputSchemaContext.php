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
 * Not needed in queued jobs (output translation is a synchronous read concern).
 * No middleware or container binding required — instantiated via auto-resolution.
 */
class OutputSchemaContext
{
    public function __construct(private PartnerContext $partnerContext) {}

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
