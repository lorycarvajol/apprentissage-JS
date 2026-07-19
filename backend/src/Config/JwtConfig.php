<?php

namespace App\Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtConfig
{
    private static function getSecret(): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? '';

        if ($secret === '' || $secret === 'your-secret-key-change-this-in-production') {
            throw new \RuntimeException(
                'JWT_SECRET doit être défini avec une valeur forte et unique dans .env (voir .env.example)'
            );
        }

        return $secret;
    }

    public static function getExpirationSeconds(): int
    {
        return (int)($_ENV['JWT_EXPIRATION'] ?? 900);
    }

    public static function encode(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + self::getExpirationSeconds();

        $token = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => $payload
        ];

        return JWT::encode($token, self::getSecret(), 'HS256');
    }

    public static function decode(string $token): object
    {
        return JWT::decode($token, new Key(self::getSecret(), 'HS256'));
    }
}
