<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Support\ApiException;
use App\Support\ApiResponse;
use App\Support\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Admin-only user management: list the team and create/promote commercial
 * accounts. Gated by RoleMiddleware(['admin']) on the route group.
 */
class UserController
{
    public function index(Request $request, Response $response): Response
    {
        $users = User::orderBy('name')->get()->map(fn (User $u) => [
            'id'    => $u->id,
            'name'  => $u->name,
            'email' => $u->email,
            'role'  => $u->role,
        ])->all();

        return ApiResponse::success($response, $users);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = (array) ($request->getParsedBody() ?? []);

        (new Validator($body))
            ->required('name', 'Name')
            ->minLength('name', 2, 'Name')
            ->required('email', 'Email')
            ->email('email')
            ->required('password', 'Password')
            ->password('password')
            ->in('role', ['admin', 'commercial'], 'Role')
            ->throwIfFails();

        if (User::where('email', $body['email'])->exists()) {
            throw ApiException::conflict('Email is already registered');
        }

        $user = User::create([
            'name'     => $body['name'],
            'email'    => $body['email'],
            'password' => password_hash($body['password'], PASSWORD_BCRYPT),
            'role'     => $body['role'] ?? 'commercial',
        ]);

        return ApiResponse::success($response, [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ], 201);
    }
}
