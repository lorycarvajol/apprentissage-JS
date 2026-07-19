<?php

namespace Tests\Unit\Config;

use App\Config\JwtConfig;
use PHPUnit\Framework\TestCase;

class JwtConfigTest extends TestCase
{
    private ?string $originalSecret;
    private ?string $originalExpiration;

    protected function setUp(): void
    {
        $this->originalSecret = $_ENV['JWT_SECRET'] ?? null;
        $this->originalExpiration = $_ENV['JWT_EXPIRATION'] ?? null;

        $_ENV['JWT_SECRET'] = 'test-secret-for-phpunit-only-not-used-in-prod';
        $_ENV['JWT_EXPIRATION'] = '900';
    }

    protected function tearDown(): void
    {
        // restaure l'environnement réel (.env) pour ne pas fuiter vers
        // d'autres tests qui s'attendent au vrai JWT_SECRET
        if ($this->originalSecret === null) {
            unset($_ENV['JWT_SECRET']);
        } else {
            $_ENV['JWT_SECRET'] = $this->originalSecret;
        }

        if ($this->originalExpiration === null) {
            unset($_ENV['JWT_EXPIRATION']);
        } else {
            $_ENV['JWT_EXPIRATION'] = $this->originalExpiration;
        }
    }

    public function testEncodeDecodeRoundTripPreservesPayload(): void
    {
        $token = JwtConfig::encode(['user_id' => 7, 'role' => 'student']);
        $decoded = JwtConfig::decode($token);

        $this->assertSame(7, $decoded->data->user_id);
        $this->assertSame('student', $decoded->data->role);
    }

    public function testEncodedTokenCarriesExpirationClaim(): void
    {
        $before = time();
        $token = JwtConfig::encode(['user_id' => 1]);
        $decoded = JwtConfig::decode($token);

        $this->assertGreaterThanOrEqual($before + 900, $decoded->exp);
    }

    public function testDecodingTamperedTokenThrows(): void
    {
        $token = JwtConfig::encode(['user_id' => 1]);
        $tampered = substr($token, 0, -4) . 'xxxx';

        // firebase/php-jwt lève des sous-classes d'UnexpectedValueException
        // (SignatureInvalidException, etc.) sur une signature invalide
        $this->expectException(\UnexpectedValueException::class);
        JwtConfig::decode($tampered);
    }

    public function testMissingSecretThrowsRuntimeException(): void
    {
        unset($_ENV['JWT_SECRET']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/JWT_SECRET/');
        JwtConfig::encode(['user_id' => 1]);
    }

    public function testPlaceholderSecretThrowsRuntimeException(): void
    {
        // garde-fou anti-déploiement-avec-la-valeur-par-défaut du .env.example
        $_ENV['JWT_SECRET'] = 'your-secret-key-change-this-in-production';

        $this->expectException(\RuntimeException::class);
        JwtConfig::encode(['user_id' => 1]);
    }
}
