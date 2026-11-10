<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;
use App\Support\ApiException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Restricts a route group to one or more roles. Must run after AuthMiddleware
 * (which sets the "userId" request attribute from the JWT).
 *
 * Usage: $group->add(new RoleMiddleware(['admin']));
 */
class RoleMiddleware implements MiddlewareInterface
{
    /** @param string[] $allowedRoles */
    public function __construct(private array $allowedRoles)
    {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $userId = (int) $request->getAttribute('userId');
        $user = User::find($userId);

        if (!$user) {
            throw ApiException::unauthorized('Invalid or expired token');
        }

        if (!in_array($user->role, $this->allowedRoles, true)) {
            throw ApiException::forbidden('You do not have permission to perform this action');
        }

        $request = $request->withAttribute('user', $user);

        return $handler->handle($request);
    }
}
