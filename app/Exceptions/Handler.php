<?php

namespace App\Exceptions;

use Config;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            return false;
        });
    }

    /**
     * @param Request $request
     * @param Throwable $e
     * @return JsonResponse|Response|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     * @return mixed
     */
    public function render($request, Throwable $e): mixed
    {
        $response = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
        ];

        if (Config::get('app.debug')) {
            $response['details'] = [
                'exception' => get_class($e),
                'trace' => $e->getTrace(),
            ];
        }

        $statusCode = 500;
        if ($e->getCode()) {
            $statusCode = $e->getCode();
        }

        return response()->json($response, $statusCode);
    }
}
