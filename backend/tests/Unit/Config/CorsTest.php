<?php

namespace Tests\Unit\Config;

use App\Config\Cors;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CorsTest extends TestCase
{
    private array $savedEnv = [];

    protected function setUp(): void
    {
        $this->savedEnv = [
            'APP_ENV' => $_ENV['APP_ENV'] ?? null,
            'CORS_ORIGIN' => $_ENV['CORS_ORIGIN'] ?? null,
        ];
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }
    }

    private function isAllowed(string $origin): bool
    {
        $method = new ReflectionMethod(Cors::class, 'isAllowed');
        $method->setAccessible(true);
        return $method->invoke(null, $origin);
    }

    private function useDevelopment(string $corsOrigin = 'http://localhost:5200'): void
    {
        $_ENV['APP_ENV'] = 'development';
        $_ENV['CORS_ORIGIN'] = $corsOrigin;
    }

    private function useProduction(string $corsOrigin = 'https://js.tondomaine.fr'): void
    {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['CORS_ORIGIN'] = $corsOrigin;
    }

    // ------------------------------------------------------------------
    // Développement : la souplesse localhost reste en place
    // ------------------------------------------------------------------

    public function testDevAcceptsLocalhostWithoutPort(): void
    {
        $this->useDevelopment();
        $this->assertTrue($this->isAllowed('http://localhost'));
    }

    public function testDevAcceptsLocalhostWithPort(): void
    {
        $this->useDevelopment();
        $this->assertTrue($this->isAllowed('http://localhost:5200'));
    }

    public function testDevAccepts127001WithPort(): void
    {
        $this->useDevelopment();
        $this->assertTrue($this->isAllowed('http://127.0.0.1:5200'));
    }

    public function testDevRejectsHttpsLocalhost(): void
    {
        // le check est volontairement strict sur http:// -- en dev il n'y a
        // pas de TLS local, un https:// inattendu ne doit pas être autorisé
        $this->useDevelopment();
        $this->assertFalse($this->isAllowed('https://localhost:5200'));
    }

    public function testDevRejectsArbitraryDomain(): void
    {
        $this->useDevelopment();
        $this->assertFalse($this->isAllowed('http://evil.com'));
    }

    public function testDevRejectsLocalhostAsSubstringNotExactHost(): void
    {
        $this->useDevelopment();
        $this->assertFalse($this->isAllowed('http://notlocalhost:5200'));
        $this->assertFalse($this->isAllowed('http://localhost.evil.com'));
    }

    public function testDevRejectsEmptyOrigin(): void
    {
        $this->useDevelopment();
        $this->assertFalse($this->isAllowed(''));
    }

    // ------------------------------------------------------------------
    // Production : CORS_ORIGIN fait seule autorité
    // ------------------------------------------------------------------

    public function testProductionAcceptsConfiguredOrigin(): void
    {
        $this->useProduction();
        $this->assertTrue($this->isAllowed('https://js.tondomaine.fr'));
    }

    public function testProductionRejectsLocalhost(): void
    {
        // le point clé de la mise en ligne : la tolérance localhost du mode dev
        // ne doit surtout pas survivre en production, sinon n'importe quelle
        // page servie depuis un localhost pourrait appeler l'API authentifiée
        // (les requêtes portent le cookie de refresh, Allow-Credentials: true)
        $this->useProduction();
        $this->assertFalse($this->isAllowed('http://localhost:5200'));
        $this->assertFalse($this->isAllowed('http://127.0.0.1:5200'));
    }

    public function testProductionRejectsUnlistedDomain(): void
    {
        $this->useProduction();
        $this->assertFalse($this->isAllowed('https://evil.com'));
    }

    public function testProductionRejectsHttpVariantOfConfiguredOrigin(): void
    {
        // comparaison stricte de chaîne : le schéma fait partie de l'origine
        $this->useProduction();
        $this->assertFalse($this->isAllowed('http://js.tondomaine.fr'));
    }

    public function testAcceptsAnyOriginFromCommaSeparatedList(): void
    {
        $this->useProduction('https://js.tondomaine.fr, https://admin.tondomaine.fr');
        $this->assertTrue($this->isAllowed('https://js.tondomaine.fr'));
        $this->assertTrue($this->isAllowed('https://admin.tondomaine.fr'));
        $this->assertFalse($this->isAllowed('https://autre.tondomaine.fr'));
    }

    public function testProductionWithoutCorsOriginAllowsNothing(): void
    {
        // une CORS_ORIGIN oubliée doit tout refuser, pas retomber sur un
        // défaut permissif
        $this->useProduction('');
        $this->assertFalse($this->isAllowed('https://js.tondomaine.fr'));
        $this->assertFalse($this->isAllowed('http://localhost:5200'));
    }
}
