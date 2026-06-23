<?php

namespace App\DeploymentSteps;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for deployment steps.
 *
 * CONSTRAINTS (by design):
 *   - Run-once: a step that has been recorded in deployment_steps will and should never run again,
 *     even if its handle() method is changed. To re-run logic, create a new step file.
 *   - Forward-only: there is no rollback mechanism. Steps are intended for data fixes,
 *     seeding, and one-time operations where reversal is impractical or meaningless.
 */
abstract class DeploymentStep
{
    public function __construct(protected OutputInterface $output) {}

    abstract public function handle(): void;

    protected function call(string $command, array $arguments = []): int
    {
        return Artisan::call($command, $arguments, $this->output);
    }

    protected function info(string $message): void
    {
        $this->output->writeln("<info>{$message}</info>");
    }

    protected function warn(string $message): void
    {
        $this->output->writeln("<comment>{$message}</comment>");
    }

    protected function error(string $message): void
    {
        $this->output->writeln("<error>{$message}</error>");
    }
}
