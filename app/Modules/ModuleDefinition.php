<?php

namespace App\Modules;

/** One validated entry from config/modules.php. */
final class ModuleDefinition
{
    /**
     * @param  class-string<\App\Modules\Contracts\EnrichmentModule>  $class
     * @param  string[]  $partners  partner contexts the module runs for; empty means none
     * @param  class-string[]  $subjects  model classes the module applies to
     */
    public function __construct(
        public readonly string $key,
        public readonly string $class,
        public readonly array $partners,
        public readonly array $subjects,
    ) {
    }

    public function allowsPartner(string $partner): bool
    {
        return in_array($partner, $this->partners, true);
    }

    public function allowsSubject(string $subjectType): bool
    {
        foreach ($this->subjects as $subject) {
            if (is_a($subjectType, $subject, true)) {
                return true;
            }
        }

        return false;
    }
}
