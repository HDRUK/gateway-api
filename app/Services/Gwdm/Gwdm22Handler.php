<?php

namespace App\Services\Gwdm;

class Gwdm22Handler extends Gwdm2xHandler
{
    public function toSearchableFields(array $envelope): array
    {
        return array_merge(parent::toSearchableFields($envelope), [
            'duoCodes' => $this->normalizeDelimited(
                data_get($envelope, 'metadata.accessibility.usage.duoCodes', '')
            ),
        ]);
    }
}
