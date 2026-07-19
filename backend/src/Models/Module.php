<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Module
{
    private ?int $id = null;
    private string $title;
    private ?string $description = null;
    private int $order_index;
    private bool $is_published = true;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    // ================================================================
    // Getters
    // ================================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getOrderIndex(): int
    {
        return $this->order_index;
    }

    public function isPublished(): bool
    {
        return $this->is_published;
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

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setOrderIndex(int $order_index): self
    {
        $this->order_index = $order_index;
        return $this;
    }

    public function setIsPublished(bool $is_published): self
    {
        $this->is_published = $is_published;
        return $this;
    }

    // ================================================================
    // Static methods (Finders)
    // ================================================================

    /**
     * Récupérer tous les modules publiés, triés par ordre
     */
    public static function findAll(bool $publishedOnly = true): array
    {
        $db = Database::getConnection();

        $sql = "SELECT * FROM modules";
        if ($publishedOnly) {
            $sql .= " WHERE is_published = 1";
        }
        $sql .= " ORDER BY order_index ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute();

        $modules = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $modules[] = self::hydrate($row);
        }

        return $modules;
    }

    /**
     * Récupérer un module par son ID
     */
    public static function findById(int $id): ?self
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM modules WHERE id = ?");
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::hydrate($row) : null;
    }

    /**
     * Récupérer un module avec ses chapitres
     */
    public static function findByIdWithChapitres(int $id): ?array
    {
        $module = self::findById($id);
        if (!$module) {
            return null;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM chapitres
            WHERE module_id = ? AND is_published = 1
            ORDER BY order_index ASC
        ");
        $stmt->execute([$id]);

        $chapitres = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $chapitres[] = Chapitre::hydrate($row);
        }

        return [
            'module' => $module,
            'chapitres' => $chapitres
        ];
    }

    // ================================================================
    // CRUD Operations
    // ================================================================

    /**
     * Créer un nouveau module
     */
    public function create(): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            INSERT INTO modules (title, description, order_index, is_published)
            VALUES (?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $this->title,
            $this->description,
            $this->order_index,
            $this->is_published
        ]);

        if ($result) {
            $this->id = (int) $db->lastInsertId();
        }

        return $result;
    }

    /**
     * Mettre à jour un module
     */
    public function update(): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            UPDATE modules
            SET title = ?, description = ?, order_index = ?, is_published = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $this->title,
            $this->description,
            $this->order_index,
            $this->is_published,
            $this->id
        ]);
    }

    /**
     * Supprimer un module
     */
    public function delete(): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("DELETE FROM modules WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    // ================================================================
    // Helper methods
    // ================================================================

    /**
     * Hydrater un objet Module depuis un tableau
     */
    private static function hydrate(array $data): self
    {
        $module = new self();
        $module->id = (int) $data['id'];
        $module->title = $data['title'];
        $module->description = $data['description'];
        $module->order_index = (int) $data['order_index'];
        $module->is_published = (bool) $data['is_published'];
        $module->created_at = $data['created_at'];
        $module->updated_at = $data['updated_at'];

        return $module;
    }

    /**
     * Convertir en tableau (pour JSON)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'order_index' => $this->order_index,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
