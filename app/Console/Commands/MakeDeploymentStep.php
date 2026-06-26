<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeDeploymentStep extends Command
{
    protected $signature = 'make:deployment-step {name : Snake-case description, e.g. reindex_datasets}';

    protected $description = 'Scaffold a new deployment step file';

    public function handle(): int
    {
        $name     = Str::snake($this->argument('name'));
        $prefix   = now()->format('Y_m_d_His');
        $filename = "{$prefix}_{$name}.php";
        $dir      = config('deployment.steps_path', database_path('deployment-steps'));
        $path     = "{$dir}/{$filename}";

        if (file_exists($path)) {
            $this->error("File already exists: {$path}");
            return self::FAILURE;
        }

        file_put_contents($path, $this->stub());

        $this->info("Created: {$path}");

        return self::SUCCESS;
    }

    private function stub(): string
    {
        return <<<PHP
        <?php

        use App\DeploymentSteps\DeploymentStep;

        return new class () extends DeploymentStep {
            public function handle(): void
            {
                //
            }
        };
        PHP;
    }
}
