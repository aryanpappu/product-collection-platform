<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Return a success JSON response
     */
    protected function successResponse(mixed $data, string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = ['data' => $data];

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response
     */
    protected function errorResponse(string $error, mixed $messages = null, int $statusCode = 400): JsonResponse
    {
        $response = ['error' => $error];

        if ($messages) {
            $response['messages'] = $messages;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated JSON response
     */
    protected function paginatedResponse($paginator, callable $transformer = null): JsonResponse
    {
        $data = $transformer ? $paginator->map($transformer) : $paginator->items();

        return response()->json([
            'data' => $data,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Return a not found JSON response
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, null, 404);
    }
}
