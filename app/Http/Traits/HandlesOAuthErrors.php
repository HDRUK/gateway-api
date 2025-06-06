<?php

namespace App\Http\Traits;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Nyholm\Psr7\Response as Psr7Response;
use Illuminate\Contracts\Debug\ExceptionHandler;
use League\OAuth2\Server\Exception\OAuthServerException;
use Laravel\Passport\Http\Controllers\ConvertsPsrResponses;

trait HandlesOAuthErrors
{
    use ConvertsPsrResponses;

    /**
     * Perform the given callback with exception handling.
     *
     * @param  \Closure  $callback
     * @return \Illuminate\Http\Response
     */
    protected function withErrorHandling($callback)
    {
        try {
            return $callback();
        } catch (OAuthServerException $e) {
            $this->exceptionHandler()->report($e);

            return $this->convertResponse(
                $e->generateHttpResponse(new Psr7Response())
            );
        } catch (Exception $e) {
            $this->exceptionHandler()->report($e);

            return new Response($this->configuration()->get('app.debug') ?
                $e->getMessage() : 'Error.', 500);
        }
    }

    /**
     * Get the configuration repository instance.
     *
     * @return \Illuminate\Contracts\Config\Repository
     */
    protected function configuration()
    {
        return Container::getInstance()->make(Repository::class);
    }

    /**
     * Get the exception handler instance.
     *
     * @return \Illuminate\Contracts\Debug\ExceptionHandler
     */
    protected function exceptionHandler()
    {
        return Container::getInstance()->make(ExceptionHandler::class);
    }
}
