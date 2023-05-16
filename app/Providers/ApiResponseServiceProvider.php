<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Routing\ResponseFactory;

class ApiResponseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $response = app(ResponseFactory::class);

        $response->macro('gatewayApiError',
            function ($code, $message, $errors = null, $data = null) use ($response) {
                $responseData = [
                    'code' => $code,
                    'message' => $message,
                    'errors' => $errors,
                    'data' => $data,
                ];

                return $response->json($responseData, $code);
            }
        );
    }
}
