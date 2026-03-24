<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait Responses
{
    public function okResponse(mixed $data): JsonResponse
    {
        return response()->json([
            'message' => 'success',
            'data' => $data,
        ], Response::HTTP_OK);
    }

    public function okResponseExtended(mixed $data, string $extendedName, mixed $extendedData): JsonResponse
    {
        return response()->json([
            'message' => 'success',
            'data' => $data,
            $extendedName => $extendedData,
        ], Response::HTTP_OK);
    }

    public function createdResponse(mixed $data): JsonResponse
    {
        return response()->json([
            'message' => 'success',
            'data' => $data,

        ], Response::HTTP_CREATED);
    }

    public function badRequestResponse(mixed $data = null): JsonResponse
    {
        return response()->json([
            'message' => 'bad request',
            'data' => $data,
        ], Response::HTTP_BAD_REQUEST);
    }

    public function unauthorisedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'unauthorised',
            'data' => null,
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function forbiddenResponse(mixed $data = null): JsonResponse
    {
        return response()->json([
            'message' => 'forbidden',
            'data' => $data,
        ], Response::HTTP_FORBIDDEN);
    }

    public function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'not found',
            'data' => null,
        ], Response::HTTP_NOT_FOUND);
    }

    public function errorResponse(mixed $error = null): JsonResponse
    {
        return response()->json([
            'message' => 'unexpected error',
            'data' => $error,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function notImplementedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'not implemented',
            'data' => null,
        ], Response::HTTP_NOT_IMPLEMENTED);
    }

    public function conflictResponse(mixed $data = null): JsonResponse
    {
        return response()->json([
            'message' => 'conflict',
            'data' => $data,
        ], Response::HTTP_CONFLICT);
    }

    public function noContent(): JsonResponse
    {
        return response()->json([
            'message' => 'no content',
            'data' => null,
        ], Response::HTTP_NO_CONTENT);
    }


    public function unprocessableContent(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

}
