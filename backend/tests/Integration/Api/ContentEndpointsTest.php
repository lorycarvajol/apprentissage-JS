<?php

namespace Tests\Integration\Api;

use PHPUnit\Framework\TestCase;

/**
 * Test d'intégration HTTP de bout en bout (Router -> Controller -> Model ->
 * MySQL) contre le serveur de dev réellement démarré (scripts/start-dev.ps1),
 * en lecture seule sur les données déjà seedées par backend/database/seed_
 * moduleN.php -- aucune mutation, aucun risque pour les données de dev.
 *
 * Si le serveur n'est pas démarré, ces tests sont marqués "skipped" (pas
 * "failed") plutôt que de casser la suite entière.
 */
class ContentEndpointsTest extends TestCase
{
    private const BASE_URL = 'http://localhost:8010/api';

    protected function setUp(): void
    {
        $context = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
        $reachable = @file_get_contents(self::BASE_URL . '/modules', false, $context);

        if ($reachable === false) {
            $this->markTestSkipped(
                'Backend de dev injoignable sur ' . self::BASE_URL
                . ' -- lancer scripts/start-dev.ps1 pour exécuter ces tests d\'intégration.'
            );
        }
    }

    private function getJson(string $path): array
    {
        $response = file_get_contents(self::BASE_URL . $path);
        return json_decode($response, true);
    }

    public function testModulesEndpointReturnsAllNineSeededModulesInOrder(): void
    {
        $data = $this->getJson('/modules');

        $this->assertTrue($data['success']);
        $this->assertCount(9, $data['modules']);
        $this->assertSame(1, $data['modules'][0]['order_index']);
        $this->assertSame(9, $data['modules'][8]['order_index']);
    }

    public function testModuleChapitresEndpointReturnsOrderedChapitres(): void
    {
        $data = $this->getJson('/modules/3/chapitres');

        $this->assertTrue($data['success']);
        $this->assertSame('Fonctions', $data['module']['title']);
        $this->assertCount(3, $data['chapitres']);
        $this->assertSame([1, 2, 3], array_column($data['chapitres'], 'order_index'));
    }

    public function testChapitreContentEndpointNeverExposesSolutionOrExpectedOutput(): void
    {
        // module 3, chapitre "Déclarer et utiliser des fonctions" (2 exercices) --
        // solution_code/expected_output ne doivent jamais fuiter côté élève
        $data = $this->getJson('/chapitres/7/content');

        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['exercices']);

        foreach ($data['exercices'] as $exercice) {
            $this->assertArrayNotHasKey('solution_code', $exercice);
            $this->assertArrayNotHasKey('expected_output', $exercice);
        }
    }

    public function testUnknownModuleIdReturns404(): void
    {
        $context = stream_context_create(['http' => ['ignore_errors' => true]]);
        file_get_contents(self::BASE_URL . '/modules/999999', false, $context);

        $statusLine = $http_response_header[0] ?? '';

        $this->assertStringContainsString('404', $statusLine);
    }

    public function testSubmitExerciceWithoutAuthIsRejected(): void
    {
        // endpoint mutant (POST /exercices/{id}/submit) -- on vérifie
        // uniquement qu'il refuse une requête non authentifiée, sans
        // jamais soumettre de tentative réelle
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode(['code' => 'console.log(1)', 'output' => '1']),
                'ignore_errors' => true,
            ],
        ]);
        file_get_contents(self::BASE_URL . '/exercices/13/submit', false, $context);

        $statusLine = $http_response_header[0] ?? '';

        $this->assertStringContainsString('401', $statusLine);
    }
}
