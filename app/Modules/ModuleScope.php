<?php

namespace App\Modules;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use InvalidArgumentException;

/**
 * Modules for one subject, as returned by ModuleManager::for() or the
 * HasModules trait:
 *
 *   $dataset->modules()->only('closer')->resolve();   // read stored results
 *   $dataset->modules(params: ['wave' => 3])->refresh(); // queue a pull
 *   $this->modules($dataset)->refreshMissing();       // queue only if nothing stored
 *   $this->modules($dataset)->within(fn ($scope) => ...); // with log context
 */
final class ModuleScope
{
    /** @param string[]|null $only */
    public function __construct(
        private readonly ModuleManager $manager,
        private readonly EnrichmentContext $context,
        private readonly ?array $only = null,
    ) {
    }

    /** Limit to the named modules. An unknown name is an error, to catch typos. */
    public function only(string ...$keys): self
    {
        foreach ($keys as $key) {
            if (! $this->manager->registry()->has($key)) {
                throw new InvalidArgumentException("Unknown enrichment module '{$key}'.");
            }
        }

        return new self($this->manager, $this->context, $keys);
    }

    public function context(): EnrichmentContext
    {
        return $this->context;
    }

    /** @return Collection<string, EnrichmentResult> */
    public function resolve(): Collection
    {
        return $this->manager->resolve($this->context, $this->only);
    }

    /** Queue a pull for each module that applies. Returns how many were queued. */
    public function refresh(bool $force = false): int
    {
        return $this->manager->queue($this->context, $this->only, $force);
    }

    /**
     * Queue a pull only for modules with no usable data stored yet (nothing,
     * or a failed first pull). Cheap enough to call on every save.
     */
    public function refreshMissing(): int
    {
        return $this->manager->queue($this->context, $this->only, missingOnly: true);
    }

    /**
     * Run the callback with the subject, partner and origin added to the log
     * context; the previous context is restored afterwards, even on error.
     *
     * @template T
     *
     * @param  callable(self): T  $callback
     * @return T
     */
    public function within(callable $callback): mixed
    {
        return Context::scope(fn () => $callback($this), [
            'module_subject' => $this->context->subjectType.':'.$this->context->subjectId,
            'module_partner' => $this->context->partner,
            'module_origin' => $this->context->origin,
        ]);
    }
}
