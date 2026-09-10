<?php

namespace App\Exceptions\Profiles;

use HDRUK\ErrorHandler\Profiles\BaseExceptionProfile;
use Throwable;

/**
 * Preserves the pre-package behaviour for our own App\Exceptions\* classes:
 * these already carry a deliberately client-safe message and HTTP status
 * (set via their constructor's $code), so unlike framework exceptions they
 * are passed straight through rather than mapped to a canned template.
 */
class LegacyDomainExceptionProfile extends BaseExceptionProfile
{
    public function __construct(protected string $code)
    {
    }

    public function code(): string
    {
        return $this->code;
    }

    public function httpStatus(Throwable $e): int
    {
        return $e->getCode() ?: 500;
    }

    public function publicMessageTemplate(): string
    {
        return '{message}';
    }

    public function publicContext(Throwable $e): array
    {
        return ['message' => $e->getMessage()];
    }
}
