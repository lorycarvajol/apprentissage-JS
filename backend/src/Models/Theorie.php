<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Theorie
{
    private ?int $id = null;
    private int $chapitre_id;
    private string $title;
    private string $content;
    private int $order_index;
    private int $estimated_time = 10;
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

    public function getContent(): string
    {
        return $this->content;
    }

    public function getOrderIndex(): int
    {
        return $this->order_index;
    }

    public function getEstimatedTime(): int
    {
        return $this->estimated_time;
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

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function setOrderIndex(int $order_index): self
    {
        $this->order_index = $order_index;
        return $this;
    }

    public function setEstimatedTime(int $estimated_time): self
    {
        $this->estimated_time = $estimated_time;
        return $this;
    }

    // ================================================================
    // Static methods (Finders)
    // ================================================================

    /**
     * Récupérer toutes les théories d'un chapitre
     */
    public static function findByChapitreId(int $chapitreId): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT * FROM theories
            WHERE chapitre_id = ?
            ORDER BY order_index ASC
        ");
        $stmt->execute([$chapitreId]);

        $theories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $theories[] = self::hydrate($row);
        }

        return $theories;
    }

    /**
     * Récupérer une théorie par son ID
     */
    public static function findById(int $id): ?self
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM theories WHERE id = ?");
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::hydrate($row) : null;
    }

    /**
     * Récupérer toutes les théories, triées selon l'ordre d'affichage réel
     * (modules.order_index puis chapitres.order_index) plutôt que par les id
     * techniques : rien ne garantit que les deux coïncident (voir Chapitre::findAll).
     */
    public static function findAll(): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT theories.* FROM theories
            INNER JOIN chapitres ON chapitres.id = theories.chapitre_id
            INNER JOIN modules ON modules.id = chapitres.module_id
            ORDER BY modules.order_index ASC, chapitres.order_index ASC, theories.order_index ASC
        ");
        $stmt->execute();

        $theories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $theories[] = self::hydrate($row);
        }

        return $theories;
    }

    // ================================================================
    // CRUD Operations
    // ================================================================

    /**
     * Créer une nouvelle théorie
     */
    public function create(): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            INSERT INTO theories (chapitre_id, title, content, order_index, estimated_time)
            VALUES (?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $this->chapitre_id,
            $this->title,
            $this->content,
            $this->order_index,
            $this->estimated_time
        ]);

        if ($result) {
            $this->id = (int) $db->lastInsertId();
        }

        return $result;
    }

    /**
     * Mettre à jour une théorie
     */
    public function update(): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            UPDATE theories
            SET chapitre_id = ?, title = ?, content = ?, order_index = ?, estimated_time = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $this->chapitre_id,
            $this->title,
            $this->content,
            $this->order_index,
            $this->estimated_time,
            $this->id
        ]);
    }

    /**
     * Supprimer une théorie
     */
    public function delete(): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("DELETE FROM theories WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    // ================================================================
    // Helper methods
    // ================================================================

    /**
     * Hydrater un objet Theorie depuis un tableau
     */
    public static function hydrate(array $data): self
    {
        $theorie = new self();
        $theorie->id = (int) $data['id'];
        $theorie->chapitre_id = (int) $data['chapitre_id'];
        $theorie->title = $data['title'];
        $theorie->content = $data['content'];
        $theorie->order_index = (int) $data['order_index'];
        $theorie->estimated_time = (int) $data['estimated_time'];
        $theorie->created_at = $data['created_at'];
        $theorie->updated_at = $data['updated_at'];

        return $theorie;
    }

    /**
     * Convertir en tableau (pour JSON)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'chapitre_id' => $this->chapitre_id,
            'title' => $this->title,
            'content' => $this->content,
            'order_index' => $this->order_index,
            'estimated_time' => $this->estimated_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
