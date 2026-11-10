<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Support\ApiException;
use App\Support\ApiResponse;
use App\Support\JwtHelper;
use App\Support\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function register(Request $request, Response $response): Response
    {
        $body = (array) ($request->getParsedBody() ?? []);

        (new Validator($body))
            ->required('name', 'Name')
            ->minLength('name', 2, 'Name')
            ->required('email', 'Email')
            ->email('email')
            ->required('password', 'Password')
            ->password('password')
            ->throwIfFails();

        if (User::where('email', $body['email'])->exists()) {
            throw ApiException::conflict('Email is already registered');
        }

        $user = User::create([
            'name'     => $body['name'],
            'email'    => $body['email'],
            'password' => password_hash($body['password'], PASSWORD_BCRYPT),
        ]);

        $token = JwtHelper::issue($user->id);

        return ApiResponse::success($response, [
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
            'token' => $token,
        ], 201);
    }

    public function login(Request $request, Response $response): Response
    {
        $body = (array) ($request->getParsedBody() ?? []);

        (new Validator($body))
            ->required('email', 'Email')
            ->required('password', 'Password')
            ->throwIfFails();

        $user = User::where('email', $body['email'])->first();

        if (!$user || !password_verify($body['password'], $user->password)) {
            throw ApiException::unauthorized('Invalid email or password');
        }

        $token = JwtHelper::issue($user->id);

        return ApiResponse::success($response, [
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
            'token' => $token,
        ], 200);
    }

    public function me(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('userId');

        $user = User::find($userId);

        if (!$user) {
            throw ApiException::unauthorized('Invalid or expired token');
        }

        return ApiResponse::success($response, [
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'role'      => $user->role,
            'createdAt' => $user->created_at?->toIso8601String(),
            'updatedAt' => $user->updated_at?->toIso8601String(),
        ]);
    }
}
