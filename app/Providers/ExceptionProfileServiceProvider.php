<?php

namespace App\Providers;

use App\Exceptions\AliasReplyScannerException;
use App\Exceptions\BadRequestException;
use App\Exceptions\EmailTemplateException;
use App\Exceptions\FederationSecretException;
use App\Exceptions\IntegrationPermissionException;
use App\Exceptions\InternalServerErrorException;
use App\Exceptions\MailSendException;
use App\Exceptions\MauroServiceException;
use App\Exceptions\MMCException;
use App\Exceptions\NotFoundException;
use App\Exceptions\Profiles\LegacyDomainExceptionProfile;
use App\Exceptions\ResourceAlreadyExistsException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\UnprocessableException;
use HDRUK\ErrorHandler\Facades\ErrorHandler;
use Illuminate\Support\ServiceProvider;

/**
 * Maps our own App\Exceptions\* classes to a profile that preserves their
 * pre-existing behaviour (client-safe message + status passed straight
 * through) rather than the package's canned templates, which are meant for
 * framework exceptions whose raw message isn't safe to expose.
 *
 * Each class's error-code prefix lives on the class itself (its
 * ERROR_BOUNDS constant) so it can't drift from hand-typed strings here.
 * The per-verb sequence numbers below are not yet audited against real
 * call sites — every verb defaults to '001' (see
 * LegacyDomainExceptionProfile::code()) until specific call sites are
 * catalogued and given their own suffix.
 */
class ExceptionProfileServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ([
            AliasReplyScannerException::class => [],
            BadRequestException::class => [],
            EmailTemplateException::class => [],
            FederationSecretException::class => [],
            IntegrationPermissionException::class => [],
            InternalServerErrorException::class => [],
            MailSendException::class => [],
            MauroServiceException::class => [],
            MMCException::class => [],
            NotFoundException::class => [],
            ResourceAlreadyExistsException::class => [],
            UnauthorizedException::class => [],
            UnprocessableException::class => [],
        ] as $exceptionClass => $codesByVerb) {
            ErrorHandler::mapException(
                $exceptionClass,
                LegacyDomainExceptionProfile::forClass($exceptionClass, $codesByVerb),
            );
        }
    }
}
