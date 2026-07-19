<?php

namespace Tests\Unit\Controllers;

use App\Controllers\ExerciceController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * normalizeOutput() est le cœur de la correction des exercices : la sortie
 * capturée côté client (jsSandbox.js) est comparée à expected_output après
 * ce passage de normalisation. Une régression ici casserait silencieusement
 * la correction de tous les exercices du curriculum.
 */
class ExerciceControllerNormalizeOutputTest extends TestCase
{
    private function normalize(string $output): string
    {
        $method = new ReflectionMethod(ExerciceController::class, 'normalizeOutput');
        $method->setAccessible(true);
        return $method->invoke(null, $output);
    }

    public function testIdenticalStringsMatch(): void
    {
        $this->assertSame($this->normalize('Bonjour'), $this->normalize('Bonjour'));
    }

    public function testWhitespaceDifferencesAreIgnored(): void
    {
        // un \n en trop/en moins entre deux console.log ne doit jamais
        // faire échouer une solution par ailleurs correcte
        $this->assertSame(
            $this->normalize("Bonjour\nAna"),
            $this->normalize('Bonjour Ana')
        );
    }

    public function testCaseDifferencesAreIgnored(): void
    {
        $this->assertSame($this->normalize('BONJOUR'), $this->normalize('bonjour'));
    }

    public function testAllWhitespaceIsStrippedNotJustCollapsed(): void
    {
        // preg_replace('/\s+/', '', ...) supprime TOUT l'espace, il ne
        // réduit pas juste les suites d'espaces à un seul -- "a b" et "ab"
        // sont donc considérés identiques par ce mécanisme
        $this->assertSame($this->normalize('a b'), $this->normalize('ab'));
    }

    public function testDifferentContentDoesNotMatch(): void
    {
        $this->assertNotSame($this->normalize('180'), $this->normalize('120'));
    }

    public function testHandlesAccentedUtf8CharactersCaseFolding(): void
    {
        // raison d'être de mb_strtolower(..., 'UTF-8') plutôt que
        // strtolower() : ce dernier n'est pas multibyte-safe
        $this->assertSame($this->normalize('ÉCRIRE'), $this->normalize('écrire'));
    }

    public function testEmptyStringNormalizesToEmptyString(): void
    {
        $this->assertSame('', $this->normalize(''));
    }
}
