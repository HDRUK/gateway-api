<?php

namespace Tests\Unit\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Guards against the failure mode behind GAT-9999's Horizon timeout spike: a
 * job with no $timeout silently inherits whatever supervisor/queue it lands
 * on (e.g. the default queue's 200s cap, sized for an unrelated job) and
 * only surfaces as a production TimeoutExceededException once it grows past
 * that ceiling. Every queueable job must size its own timeout explicitly.
 */
class JobTimeoutConventionTest extends TestCase
{
    /**
     * Not real queue jobs in the Horizon sense — instantiated and run
     * synchronously, so an inherited supervisor timeout doesn't apply.
     */
    private const EXEMPT = [
        \App\Jobs\TestFederation::class,
    ];

    public function test_every_queueable_job_declares_an_explicit_timeout(): void
    {
        $missing = [];

        foreach ($this->shouldQueueJobClasses() as $class) {
            if (in_array($class, self::EXEMPT, true)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if (! $reflection->hasProperty('timeout')) {
                $missing[] = $class;

                continue;
            }

            $timeout = $reflection->getDefaultProperties()['timeout'] ?? null;

            // A handful of jobs size $timeout from config in their constructor
            // (e.g. `$this->timeout = config('jobs.default_timeout')`) rather
            // than as a literal property default — reflection can't see that
            // without instantiating, so fall back to a source scan for it.
            if ($timeout === null && ! preg_match('/\$this->timeout\s*=/', file_get_contents($reflection->getFileName()))) {
                $missing[] = $class;
            }
        }

        $this->assertEmpty(
            $missing,
            "The following jobs have no explicit \$timeout and would silently inherit "
            ."whatever Horizon supervisor/queue they're dispatched to:\n- ".implode("\n- ", $missing)
        );
    }

    /** @return array<int, class-string> */
    private function shouldQueueJobClasses(): array
    {
        $classes = [];

        foreach (Finder::create()->in(app_path('Jobs'))->name('*.php')->files() as $file) {
            $class = 'App\\Jobs\\'.$file->getBasename('.php');

            if (! class_exists($class)) {
                continue;
            }

            if (is_subclass_of($class, ShouldQueue::class) || in_array(ShouldQueue::class, class_implements($class) ?: [], true)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
