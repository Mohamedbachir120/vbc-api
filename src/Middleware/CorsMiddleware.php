<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Allows the frontend (served from a different origin, e.g. v-card.artimmo.dz
 * calling the API on v-card2.artimmo.dz) to call this API from the browser.
 * Without this, every fetch() from the SPA is blocked by the browser's CORS
 * check even though curl/Postman work fine.
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $origin = $request->getHeaderLine('Origin');

        $response = $this->isPreflight($request)
            ? $this->emptyResponse()
            : $handler->handle($request);

        return $this->withCorsHeaders($response, $origin);
    }

    private function isPreflight(Request $request): bool
    {
        return $request->getMethod() === 'OPTIONS';
    }

    private function emptyResponse(): Response
    {
        $factory = new \Slim\Psr7\Factory\ResponseFactory();

        return $factory->createResponse(204);
    }

    private function withCorsHeaders(Response $response, string $origin): Response
    {
        $allowed = $this->allowedOrigins();

        $allowOrigin = in_array('*', $allowed, true)
            ? '*'
            : (in_array($origin, $allowed, true) ? $origin : '');

        if ($allowOrigin === '') {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withHeader('Vary', 'Origin');
    }

    /** @return string[] */
    private function allowedOrigins(): array
    {
        $raw = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*';

        return array_map('trim', explode(',', $raw));
    }
}
