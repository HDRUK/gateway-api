<?php

namespace App\Modules;

use App\Modules\Contracts\EnrichmentModule;
use InvalidArgumentException;

/**
 * The modules configured in config/modules.php, validated once. Holds no
 * per-request state, so it is safe as a singleton under Octane.
 */
final class ModuleRegistry
{
    /** @var array<string, ModuleDefinition> */
    private array $definitions = [];

    public function __construct(array $modules)
    {
        foreach ($modules as $key => $config) {
            $class = $config['class'] ?? null;

            if (! is_string($class) || ! is_subclass_of($class, EnrichmentModule::class)) {
                throw new InvalidArgumentException("Module '{$key}' must name a class implementing ".EnrichmentModule::class.'.');
            }

            $this->definitions[$key] = new ModuleDefinition(
                key: $key,
                class: $class,
                partners: array_values($config['partners'] ?? []),
                subjects: array_values($config['subjects'] ?? []),
            );
        }
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    public function get(string $key): ?ModuleDefinition
    {
        return $this->definitions[$key] ?? null;
    }

    /** @return array<string, ModuleDefinition> */
    public function all(): array
    {
        return $this->definitions;
    }
}
