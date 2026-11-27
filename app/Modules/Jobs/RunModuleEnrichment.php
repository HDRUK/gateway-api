<?php

namespace App\Modules\Jobs;

use App\Modules\EnrichmentContext;
use App\Modules\ModuleManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Pulls one module's data for one subject. The manager records failures as
 * results, so the job itself doesn't fail or retry.
 */
class RunModuleEnrichment implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    /** Drop the uniqueness lock if a run never finishes. */
    public int $uniqueFor = 3600;

    public function __construct(
        public readonly string $module,
        public readonly EnrichmentContext $context,
        public readonly bool $force = false,
    ) {
        // Dispatched from model observers inside save transactions: wait for
        // the commit so the job sees the new metadata, and never runs for a
        // save that rolled back.
        $this->afterCommit = true;
    }

    /** One queued pull per module, subject and params. */
    public function uniqueId(): string
    {
        return implode('|', [
            $this->module,
            $this->context->subjectType,
            $this->context->subjectId,
            $this->context->paramsHash(),
        ]);
    }

    public function handle(ModuleManager $manager): void
    {
        $manager->run($this->module, $this->context, $this->force);
    }
}
