<?php

namespace Tests\Unit\Config;

use App\Config\Cors;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CorsTest extends TestCase
{
    private function isLocalhost(string $origin): bool
    {
        $method = new ReflectionMethod(Cors::class, 'isLocalhost');
        $method->setAccessible(true);
        return $method->invoke(null, $origin);
    }

    public function testAcceptsLocalhostWithoutPort(): void
    {
        $this->assertTrue($this->isLocalhost('http://localhost'));
    }

    public function testAcceptsLocalhostWithPort(): void
    {
        $this->assertTrue($this->isLocalhost('http://localhost:5200'));
    }

    public function testAccepts127001WithPort(): void
    {
        $this->assertTrue($this->isLocalhost('http://127.0.0.1:5200'));
    }

    public function testRejectsHttpsLocalhost(): void
    {
        // le check est volontairement strict sur http:// -- en dev il n'y a
        // pas de TLS local, un https:// inattendu ne doit pas être autorisé
        $this->assertFalse($this->isLocalhost('https://localhost:5200'));
    }

    public function testRejectsArbitraryDomain(): void
    {
        $this->assertFalse($this->isLocalhost('http://evil.com'));
    }

    public function testRejectsLocalhostAsSubstringNotExactHost(): void
    {
        $this->assertFalse($this->isLocalhost('http://notlocalhost:5200'));
        $this->assertFalse($this->isLocalhost('http://localhost.evil.com'));
    }

    public function testRejectsEmptyOrigin(): void
    {
        $this->assertFalse($this->isLocalhost(''));
    }
}
