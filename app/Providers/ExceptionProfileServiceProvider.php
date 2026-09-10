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
 */
class ExceptionProfileServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ([
            AliasReplyScannerException::class => 'ERR-ALIAS-REPLY-SCANNER-001',
            BadRequestException::class => 'ERR-BAD-REQUEST-001',
            EmailTemplateException::class => 'ERR-EMAIL-TEMPLATE-001',
            FederationSecretException::class => 'ERR-FEDERATION-SECRET-001',
            IntegrationPermissionException::class => 'ERR-INTEGRATION-PERMISSION-001',
            InternalServerErrorException::class => 'ERR-INTERNAL-SERVER-001',
            MailSendException::class => 'ERR-MAIL-SEND-001',
            MauroServiceException::class => 'ERR-MAURO-SERVICE-001',
            MMCException::class => 'ERR-MMC-001',
            NotFoundException::class => 'ERR-NOT-FOUND-003',
            ResourceAlreadyExistsException::class => 'ERR-RESOURCE-EXISTS-001',
            UnauthorizedException::class => 'ERR-UNAUTHORIZED-001',
            UnprocessableException::class => 'ERR-UNPROCESSABLE-001',
        ] as $exceptionClass => $code) {
            ErrorHandler::mapException($exceptionClass, new LegacyDomainExceptionProfile($code));
        }
    }
}
