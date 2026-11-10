<?php

declare(strict_types=1);

namespace App\Support;

use Psr\Http\Message\ResponseInterface as Response;

class ApiResponse
{
    public static function success(Response $response, mixed $data, int $status = 200): Response
    {
        $payload = ['success' => true, 'data' => $data];
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public static function noContent(Response $response): Response
    {
        return $response->withStatus(204);
    }

    public static function error(
        Response $response,
        int $status,
        string $code,
        string $message,
        ?array $details = null
    ): Response {
        $payload = [
            'success' => false,
            'code'    => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $payload['details'] = $details;
        }

        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
