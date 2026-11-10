<?php

declare(strict_types=1);

namespace App\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use UnexpectedValueException;

class JwtHelper
{
    public static function issue(int $userId): string
    {
        $secret = $_ENV['JWT_SECRET'];
        $ttl = (int) ($_ENV['JWT_EXPIRES_IN'] ?? 604800);
        $now = time();

        $payload = [
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + $ttl,
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Returns the decoded payload (object) or null if the token is invalid/expired.
     */
    public static function verify(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
        } catch (ExpiredException|SignatureInvalidException|UnexpectedValueException $e) {
            return null;
        }
    }
}
