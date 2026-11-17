<?php

namespace App\Services\Experiments;

readonly class ExperimentSubmission
{
    /**
     * @param array<string, mixed> $answers
     */
    public function __construct(
        public int $experimentId,
        public array $answers,
        public string $path,
        public bool $signedIn,
        public ?int $userId,
        public ?string $userType,
        public ?string $contactEmail,
    ) {
    }
}
