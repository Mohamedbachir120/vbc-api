<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\ApiException;
use App\Support\JwtHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $header = $request->getHeaderLine('Authorization');

        if (!$header || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            throw ApiException::unauthorized('Invalid or expired token');
        }

        $payload = JwtHelper::verify($matches[1]);

        if ($payload === null || !isset($payload->sub)) {
            throw ApiException::unauthorized('Invalid or expired token');
        }

        $request = $request->withAttribute('userId', (int) $payload->sub);

        return $handler->handle($request);
    }
}
