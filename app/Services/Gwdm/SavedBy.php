<?php

namespace App\Services\Gwdm;

use App\Enums\DatasetOrigin;

readonly class SavedBy
{
    private function __construct(
        public ?int $userId,
        public ?DatasetOrigin $origin,
    ) {
    }

    public static function person(int $userId, DatasetOrigin $origin): self
    {
        return new self($userId, $origin);
    }

    public static function unknown(): self
    {
        return new self(null, null);
    }

    /** @return array{user_id: ?int, origin: ?DatasetOrigin} */
    public function toColumns(): array
    {
        return [
            'user_id' => $this->userId,
            'origin' => $this->origin,
        ];
    }
}
