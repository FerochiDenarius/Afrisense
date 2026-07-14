<?php

declare(strict_types=1);

namespace AfriSense\Backend\Helpers;

class Response
{
    /**
     * Return a standardized success response.
     */
    public static function success(string $message = 'Success.', array $data = [], int $statusCode = 200): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'status_code' => $statusCode,
        ];
    }

    /**
     * Return a standardized error response.
     */
    public static function error(string $message = 'An error occurred.', int $statusCode = 400, array $errors = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'status_code' => $statusCode,
        ];
    }

    /**
     * Send a JSON response and terminate the request.
     */
    public static function json(array $payload, ?int $statusCode = null): void
    {
        $code = $statusCode ?? (int) ($payload['status_code'] ?? 200);
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_THROW_ON_ERROR);
        exit;
    }
}
