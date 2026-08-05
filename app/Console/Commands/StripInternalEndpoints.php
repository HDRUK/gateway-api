<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StripInternalEndpoints extends Command
{
    protected $signature = 'app:strip-internal-endpoints {path=storage/api-docs/api-docs.json}';
    protected $description = 'Removes operations marked x-internal from a generated OpenAPI spec, run after l5-swagger:generate.';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));

        if (!file_exists($path)) {
            $this->error("Spec not found at {$path}");
            return self::FAILURE;
        }

        // Decoded as objects (not associative arrays): json_decode(..., true) collapses
        // every empty JSON object ({}) into a PHP array indistinguishable from an empty
        // JSON array ([]), so re-encoding silently corrupts any "parameters": {} or
        // "content": {} elsewhere in the spec into "[]", which openapi-generator then
        // rejects outright. Staying in object-land preserves that distinction.
        $spec = json_decode(file_get_contents($path), false, 512, JSON_THROW_ON_ERROR);
        $removed = 0;
        $emptyRoutes = [];

        foreach ($spec->paths ?? new \stdClass() as $route => $operations) {
            foreach (get_object_vars($operations) as $method => $operation) {
                if (!is_object($operation)) {
                    continue;
                }
                $internal = filter_var($operation->{'x-internal'} ?? false, FILTER_VALIDATE_BOOLEAN);
                if (!$internal) {
                    continue;
                }
                unset($spec->paths->{$route}->{$method});
                $removed++;
            }
            if (empty(get_object_vars($spec->paths->{$route}))) {
                $emptyRoutes[] = $route;
            }
        }

        foreach ($emptyRoutes as $route) {
            unset($spec->paths->{$route});
        }

        file_put_contents($path, json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("Removed {$removed} internal operation(s) from {$path}");

        return self::SUCCESS;
    }
}
