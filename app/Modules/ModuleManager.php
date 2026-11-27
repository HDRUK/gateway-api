<?php

namespace App\Modules;

use App\Context\PartnerContext;
use App\Modules\Contracts\EnrichmentModule;
use App\Modules\Contracts\EnrichmentStore;
use App\Modules\Jobs\RunModuleEnrichment;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use Throwable;

/**
 * Runs enrichment modules for a subject.
 *
 * A module applies only when all of these hold:
 *   1. the ModuleManager flag is on (Pennant; unstored resolves false),
 *   2. the owning partner (the subject's `partner_context`, else the
 *      requester's) is listed for the module in config/modules.php,
 *   3. for reads only, the requesting partner is listed too, so the HDRUK
 *      Gateway never shows a partner's enrichment even where it can show
 *      the partner's dataset,
 *   4. the subject's type is listed for the module,
 *   5. the module's supports() accepts the subject.
 *
 * Pulls are gated on the owning partner alone, so a partner's dataset saved
 * by an import, command or Gateway admin request is still enriched.
 *
 * Reads (resolve) only return stored results. Pulls (run) happen in a queued
 * job via refresh(). Nothing a module throws reaches the caller.
 *
 * Bound with scoped() so each Octane request gets its own instance.
 */
class ModuleManager
{
    public const FLAG = 'ModuleManager';

    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly EnrichmentStore $store,
        private readonly PartnerContext $partners,
        private readonly Container $container,
    ) {
    }

    public function enabled(): bool
    {
        return Feature::active(self::FLAG);
    }

    public function for(Model $subject, array $params = [], string $origin = 'unknown'): ModuleScope
    {
        return new ModuleScope(
            $this,
            EnrichmentContext::forModel($subject, $this->partners->getPartner(), $params, $origin),
        );
    }

    public function registry(): ModuleRegistry
    {
        return $this->registry;
    }

    /**
     * Stored results for each module that applies, keyed by module name.
     * Modules that are off for this partner or subject type are left out;
     * ones whose supports() declines are included as Skipped.
     *
     * @param  string[]|null  $only
     * @return Collection<string, EnrichmentResult>
     */
    public function resolve(EnrichmentContext $context, ?array $only = null): Collection
    {
        return collect($this->available($context, $only, forRead: true))->map(function (ModuleDefinition $definition) use ($context) {
            try {
                if ($reason = $this->unsupportedReason($definition, $context)) {
                    return EnrichmentResult::skipped($reason)->forModule($definition->key);
                }

                return ($this->store->find($definition->key, $context) ?? EnrichmentResult::missing())
                    ->forModule($definition->key);
            } catch (Throwable $e) {
                return $this->failure($definition->key, $context, $e)->forModule($definition->key);
            }
        });
    }

    /**
     * Queue a pull for each module that applies. Returns how many were queued.
     * With $missingOnly, skip modules that already hold usable data, so it is
     * cheap to call on every save. Never throws: it runs inside observers.
     *
     * @param  string[]|null  $only
     */
    public function queue(EnrichmentContext $context, ?array $only = null, bool $force = false, bool $missingOnly = false): int
    {
        $queued = 0;

        try {
            $definitions = $this->available($context, $only);
        } catch (Throwable $e) {
            $this->failure('*', $context, $e);

            return 0;
        }

        foreach ($definitions as $definition) {
            try {
                if ($this->unsupportedReason($definition, $context) !== null) {
                    continue;
                }
                if ($missingOnly && $this->store->find($definition->key, $context)?->hasData()) {
                    continue;
                }

                RunModuleEnrichment::dispatch($definition->key, $context, $force);
                $queued++;
            } catch (Throwable $e) {
                $this->failure($definition->key, $context, $e);
            }
        }

        return $queued;
    }

    /**
     * Pull and store one module's data. Called by the queued job.
     * Rechecks every gate, since the flag or config may have changed.
     */
    public function run(string $key, EnrichmentContext $context, bool $force = false): EnrichmentResult
    {
        $definition = $this->available($context, [$key])[$key] ?? null;
        if ($definition === null) {
            return EnrichmentResult::skipped("Module '{$key}' is not enabled for this partner or subject.")->forModule($key);
        }

        $existing = null;

        try {
            if ($reason = $this->unsupportedReason($definition, $context)) {
                return EnrichmentResult::skipped($reason)->forModule($key);
            }

            $module = $this->module($definition);
            $existing = $this->store->find($key, $context);
            $version = $module->sourceVersion($context);

            if (! $force && $version !== null && $existing !== null
                && in_array($existing->status, [EnrichmentStatus::Ok, EnrichmentStatus::Empty], true)
                && $existing->sourceVersion === $version) {
                return $existing->forModule($key);
            }

            $result = $module->enrich($context)->forModule($key);
        } catch (Throwable $e) {
            $result = $this->failure($key, $context, $e, $existing)->forModule($key);
        }

        try {
            $this->store->put($context, $result);
        } catch (Throwable $e) {
            $this->failure($key, $context, $e);
        }

        return $result;
    }

    /**
     * Modules that pass the flag, partner and subject-type gates.
     *
     * @param  string[]|null  $only
     * @return array<string, ModuleDefinition>
     */
    private function available(EnrichmentContext $context, ?array $only, bool $forRead = false): array
    {
        if (! $this->enabled()) {
            return [];
        }

        return array_filter(
            $this->registry->all(),
            fn (ModuleDefinition $d) => ($only === null || in_array($d->key, $only, true))
                && $d->allowsPartner($context->owningPartner())
                && (! $forRead || $d->allowsPartner($context->partner))
                && $d->allowsSubject($context->subjectType),
        );
    }

    private function unsupportedReason(ModuleDefinition $definition, EnrichmentContext $context): ?string
    {
        return $this->module($definition)->supports($context)
            ? null
            : "Module '{$definition->key}' does not support this subject.";
    }

    private function module(ModuleDefinition $definition): EnrichmentModule
    {
        return $this->container->make($definition->class);
    }

    /** Log the error; keep the last good data as Stale if there is some. */
    private function failure(string $key, EnrichmentContext $context, Throwable $e, ?EnrichmentResult $existing = null): EnrichmentResult
    {
        Log::warning('Enrichment module failed', [
            'module' => $key,
            'subject' => $context->subjectType.':'.$context->subjectId,
            'partner' => $context->partner,
            'exception' => $e,
        ]);

        $reason = class_basename($e).': '.Str::limit($e->getMessage(), 200);

        return $existing?->hasData() ? $existing->stale($reason) : EnrichmentResult::failed($reason);
    }
}
