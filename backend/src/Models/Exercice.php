<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Exercice
{
    private ?int $id = null;
    private int $chapitre_id;
    private string $title;
    private string $description;
    private ?string $instructions = null;
    private ?string $starter_code = null;
    private ?string $html_fixture = null;
    private ?string $solution_code = null;
    private ?string $expected_output = null;
    private ?array $test_cases = null;
    private string $difficulty = 'medium';
    private int $points = 10;
    private int $order_index;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    // ================================================================
    // Getters
    // ================================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChapitreId(): int
    {
        return $this->chapitre_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function getStarterCode(): ?string
    {
        return $this->starter_code;
    }

    /**
     * HTML de départ inséré dans le <body> de l'iframe sandboxée avant
     * exécution -- non NULL uniquement pour un exercice avec DOM réel (voir
     * runJsWithDom() côté frontend). NULL pour un exercice classique, qui
     * continue de passer par le Worker (runJs()).
     */
    public function getHtmlFixture(): ?string
    {
        return $this->html_fixture;
    }

    public function getSolutionCode(): ?string
    {
        return $this->solution_code;
    }

    public function getExpectedOutput(): ?string
    {
        return $this->expected_output;
    }

    public function getTestCases(): ?array
    {
        return $this->test_cases;
    }

    public function getDifficulty(): string
    {
        return $this->difficulty;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function getOrderIndex(): int
    {
        return $this->order_index;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }

    // ================================================================
    // Setters
    // ================================================================

    public function setChapitreId(int $chapitre_id): self
    {
        $this->chapitre_id = $chapitre_id;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setInstructions(?string $instructions): self
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function setStarterCode(?string $starter_code): self
    {
        $this->starter_code = $starter_code;
        return $this;
    }

    public function setHtmlFixture(?string $html_fixture): self
    {
        $this->html_fixture = $html_fixture;
        return $this;
    }

    public function setSolutionCode(?string $solution_code): self
    {
        $this->solution_code = $solution_code;
        return $this;
    }

    /**
     * Sortie de référence attendue, calculée côté client (sandbox JS dans le
     * navigateur) au moment où l'admin enregistre solution_code, puisqu'il
     * n'y a pas de moteur JS côté serveur pour la recalculer à la volée
     * comme le faisait CodeExecutionService pour PHP.
     */
    public function setExpectedOutput(?string $expected_output): self
    {
        $this->expected_output = $expected_output;
        return $this;
    }

    public function setTestCases(?array $test_cases): self
    {
        $this->test_cases = $test_cases;
        return $this;
    }

    public function setDifficulty(string $difficulty): self
    {
        if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
            throw new \InvalidArgumentException('Invalid difficulty level');
        }
        $this->difficulty = $difficulty;
        return $this;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }

    public function setOrderIndex(int $order_index): self
    {
        $this->order_index = $order_index;
        return $this;
    }

    // ================================================================
    // Static methods (Finders)
    // ================================================================

    /**
     * Récupérer tous les exercices d'un chapitre
     */
    public static function findByChapitreId(int $chapitreId): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT * FROM exercices
            WHERE chapitre_id = ?
            ORDER BY order_index ASC
        ");
        $stmt->execute([$chapitreId]);

        $exercices = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $exercices[] = self::hydrate($row);
        }

        return $exercices;
    }

    /**
     * Récupérer un exercice par son ID
     */
    public static function findById(int $id): ?self
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM exercices WHERE id = ?");
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::hydrate($row) : null;
    }

    /**
     * Récupérer tous les exercices, triés selon l'ordre d'affichage réel
     * (modules.order_index puis chapitres.order_index) plutôt que par les id
     * techniques : rien ne garantit que les deux coïncident (voir Chapitre::findAll).
     */
    public static function findAll(): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT exercices.* FROM exercices
            INNER JOIN chapitres ON chapitres.id = exercices.chapitre_id
            INNER JOIN modules ON modules.id = chapitres.module_id
            ORDER BY modules.order_index ASC, chapitres.order_index ASC, exercices.order_index ASC
        ");
        $stmt->execute();

        $exercices = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $exercices[] = self::hydrate($row);
        }

        return $exercices;
    }

    /**
     * Récupérer les exercices par difficulté
     */
    public static function findByDifficulty(string $difficulty): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT * FROM exercices
            WHERE difficulty = ?
            ORDER BY chapitre_id ASC, order_index ASC
        ");
        $stmt->execute([$difficulty]);

        $exercices = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $exercices[] = self::hydrate($row);
        }

        return $exercices;
    }

    // ================================================================
    // CRUD Operations
    // ================================================================

    /**
     * Créer un nouvel exercice
     */
    public function create(): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            INSERT INTO exercices (
                chapitre_id, title, description, instructions,
                starter_code, html_fixture, solution_code, expected_output, test_cases,
                difficulty, points, order_index
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $testCasesJson = $this->test_cases ? json_encode($this->test_cases) : null;

        $result = $stmt->execute([
            $this->chapitre_id,
            $this->title,
            $this->description,
            $this->instructions,
            $this->starter_code,
            $this->html_fixture,
            $this->solution_code,
            $this->expected_output,
            $testCasesJson,
            $this->difficulty,
            $this->points,
            $this->order_index
        ]);

        if ($result) {
            $this->id = (int) $db->lastInsertId();
        }

        return $result;
    }

    /**
     * Mettre à jour un exercice
     */
    public function update(): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            UPDATE exercices
            SET chapitre_id = ?, title = ?, description = ?, instructions = ?,
                starter_code = ?, html_fixture = ?, solution_code = ?, expected_output = ?, test_cases = ?,
                difficulty = ?, points = ?, order_index = ?
            WHERE id = ?
        ");

        $testCasesJson = $this->test_cases ? json_encode($this->test_cases) : null;

        return $stmt->execute([
            $this->chapitre_id,
            $this->title,
            $this->description,
            $this->instructions,
            $this->starter_code,
            $this->html_fixture,
            $this->solution_code,
            $this->expected_output,
            $testCasesJson,
            $this->difficulty,
            $this->points,
            $this->order_index,
            $this->id
        ]);
    }

    /**
     * Supprimer un exercice
     */
    public function delete(): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("DELETE FROM exercices WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    // ================================================================
    // Helper methods
    // ================================================================

    /**
     * Hydrater un objet Exercice depuis un tableau
     */
    public static function hydrate(array $data): self
    {
        $exercice = new self();
        $exercice->id = (int) $data['id'];
        $exercice->chapitre_id = (int) $data['chapitre_id'];
        $exercice->title = $data['title'];
        $exercice->description = $data['description'];
        $exercice->instructions = $data['instructions'];
        $exercice->starter_code = $data['starter_code'];
        $exercice->html_fixture = $data['html_fixture'] ?? null;
        $exercice->solution_code = $data['solution_code'];
        $exercice->expected_output = $data['expected_output'] ?? null;
        $exercice->test_cases = $data['test_cases'] ? json_decode($data['test_cases'], true) : null;
        $exercice->difficulty = $data['difficulty'];
        $exercice->points = (int) $data['points'];
        $exercice->order_index = (int) $data['order_index'];
        $exercice->created_at = $data['created_at'];
        $exercice->updated_at = $data['updated_at'];

        return $exercice;
    }

    /**
     * Convertir en tableau (pour JSON)
     * @param bool $includeSolution Inclure la solution ? (false par défaut pour ne pas spoiler)
     */
    public function toArray(bool $includeSolution = false): array
    {
        $data = [
            'id' => $this->id,
            'chapitre_id' => $this->chapitre_id,
            'title' => $this->title,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'starter_code' => $this->starter_code,
            'html_fixture' => $this->html_fixture,
            'test_cases' => $this->test_cases,
            'difficulty' => $this->difficulty,
            'points' => $this->points,
            'order_index' => $this->order_index,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];

        // Ajouter la solution seulement si demandé (admin ou correction) : ne
        // jamais exposer solution_code/expected_output aux endpoints publics,
        // sinon la sortie attendue spoile l'exercice avant même la soumission.
        if ($includeSolution) {
            $data['solution_code'] = $this->solution_code;
            $data['expected_output'] = $this->expected_output;
        }

        return $data;
    }
}
