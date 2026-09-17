<?php

namespace App\Exceptions\Profiles;

use HDRUK\ErrorHandler\Profiles\BaseExceptionProfile;
use Throwable;

/**
 * Preserves the pre-package behaviour for our own App\Exceptions\* classes:
 * these already carry a deliberately client-safe message and HTTP status
 * (set via their constructor's $code), so unlike framework exceptions they
 * are passed straight through rather than mapped to a canned template.
 *
 * Also used, via its defaults, as the error-handler's fallback profile:
 * this codebase's convention of throwing a plain Exception/RuntimeException
 * directly with a client-safe message isn't limited to the named
 * App\Exceptions\* classes, so anything left unmapped needs the same
 * passthrough rather than the package's canned "something went wrong"
 * template.
 *
 * code() is not a fixed string: it's the exception class's own
 * ERROR_BOUNDS constant (e.g. 'ERR-NOT-FOUND-') plus the HTTP verb in play
 * when the exception surfaces, plus a per-verb sequence number — e.g.
 * ERR-NOT-FOUND-DELETE-002 (see forClass()). This is purely an
 * internal/reporting identifier — the client-facing 'code' field is
 * overwritten with the numeric HTTP status in bootstrap/app.php's
 * render() closure — so varying it per verb costs nothing at the response
 * boundary.
 */
class LegacyDomainExceptionProfile extends BaseExceptionProfile
{
    /**
     * @param  array<string, string>  $codesByVerb  HTTP verb (GET/POST/...) => sequence suffix (e.g. '001').
     */
    public function __construct(
        protected string $bounds = 'ERR-UNKNOWN-',
        protected array $codesByVerb = [],
    ) {
    }

    /**
     * @param  class-string<\Throwable>  $exceptionClass  Must define a public ERROR_BOUNDS constant, e.g. 'ERR-NOT-FOUND-'.
     * @param  array<string, string>  $codesByVerb
     */
    public static function forClass(string $exceptionClass, array $codesByVerb = []): self
    {
        return new self($exceptionClass::ERROR_BOUNDS, $codesByVerb);
    }

    public function code(): string
    {
        $verb = strtoupper(request()->method());
        $suffix = $this->codesByVerb[$verb] ?? '001';

        return "{$this->bounds}{$verb}-{$suffix}";
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
