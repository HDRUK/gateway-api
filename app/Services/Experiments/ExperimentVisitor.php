<?php

namespace App\Services\Experiments;

readonly class ExperimentVisitor
{
    public ?string $subject;

    public function __construct(
        public ?int $userId,
        ?string $sessionId = null,
    ) {
        $this->subject = $userId ? 'user:' . $userId : $sessionId;
    }
}
