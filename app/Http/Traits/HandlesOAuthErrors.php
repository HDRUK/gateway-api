<?php

namespace App\Http\Traits;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Response;
use Laravel\Passport\Http\Controllers\ConvertsPsrResponses;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Nyholm\Psr7\Response as Psr7Response;

trait HandlesOAuthErrors
{
    use ConvertsPsrResponses;

    /**
     * Perform the given callback with exception handling.
     *
     * @param  \Closure  $callback
     * @return \Illuminate\Http\Response
     */
    protected function withErrorHandling($callback): Response
    {
        $factory = new HttpFoundationFactory();

        try {
            $result = $callback();

            if ($result instanceof ResponseInterface) {
                $symfonyResponse = $factory->createResponse($result);

                return new Response(
                    $symfonyResponse->getContent(),
                    $symfonyResponse->getStatusCode(),
                    $symfonyResponse->headers->all()
                );
            }

            if ($result instanceof Response) {
                return $result;
            }

            return new Response($result);
        } catch (OAuthServerException $e) {
            $this->exceptionHandler()->report($e);

            $psrResponse = $e->generateHttpResponse(new Psr7Response());
            $symfonyResponse = $factory->createResponse($psrResponse);

            return new Response(
                $symfonyResponse->getContent(),
                $symfonyResponse->getStatusCode(),
                $symfonyResponse->headers->all()
            );
        } catch (Exception $e) {
            $this->exceptionHandler()->report($e);

            return new Response($this->configuration()->get('app.debug') ? $e->getMessage() : 'Error.', 500);
        }
    }

    /**
     * Get the configuration repository instance.
     *
     * @return \Illuminate\Contracts\Config\Repository
     */
    protected function configuration(): Repository
    {
        return Container::getInstance()->make(Repository::class);
    }

    /**
     * Get the exception handler instance.
     *
     * @return \Illuminate\Contracts\Debug\ExceptionHandler
     */
    protected function exceptionHandler(): ExceptionHandler
    {
        return Container::getInstance()->make(ExceptionHandler::class);
    }
}
