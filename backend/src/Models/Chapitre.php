<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Chapitre
{
    private ?int $id = null;
    private int $module_id;
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

    public function getModuleId(): int
    {
        return $this->module_id;
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

    public function setModuleId(int $module_id): self
    {
        $this->module_id = $module_id;
        return $this;
    }

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
     * Récupérer tous les chapitres d'un module
     */
    public static function findByModuleId(int $moduleId, bool $publishedOnly = true): array
    {
        $db = Database::getConnection();

        $sql = "SELECT * FROM chapitres WHERE module_id = ?";
        if ($publishedOnly) {
            $sql .= " AND is_published = 1";
        }
        $sql .= " ORDER BY order_index ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$moduleId]);

        $chapitres = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $chapitres[] = self::hydrate($row);
        }

        return $chapitres;
    }

    /**
     * Récupérer un chapitre par son ID
     */
    public static function findById(int $id): ?self
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM chapitres WHERE id = ?");
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::hydrate($row) : null;
    }

    /**
     * Récupérer un chapitre avec ses théories et exercices
     */
    public static function findByIdWithContent(int $id): ?array
    {
        $chapitre = self::findById($id);
        if (!$chapitre) {
            return null;
        }

        $db = Database::getConnection();

        // Récupérer les théories
        $stmt = $db->prepare("
            SELECT * FROM theories
            WHERE chapitre_id = ?
            ORDER BY order_index ASC
        ");
        $stmt->execute([$id]);

        $theories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $theories[] = Theorie::hydrate($row);
        }

        // Récupérer les exercices
        $stmt = $db->prepare("
            SELECT * FROM exercices
            WHERE chapitre_id = ?
            ORDER BY order_index ASC
        ");
        $stmt->execute([$id]);

        $exercices = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $exercices[] = Exercice::hydrate($row);
        }

        return [
            'chapitre' => $chapitre,
            'theories' => $theories,
            'exercices' => $exercices
        ];
    }

    /**
     * Récupérer tous les chapitres, triés selon l'ordre d'affichage réel des
     * modules (modules.order_index) plutôt que leur id technique : rien ne
     * garantit que les deux coïncident (modules réordonnés après coup, etc.).
     */
    public static function findAll(bool $publishedOnly = true): array
    {
        $db = Database::getConnection();

        $sql = "SELECT chapitres.* FROM chapitres
                INNER JOIN modules ON modules.id = chapitres.module_id";
        if ($publishedOnly) {
            $sql .= " WHERE chapitres.is_published = 1";
        }
        $sql .= " ORDER BY modules.order_index ASC, chapitres.order_index ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute();

        $chapitres = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $chapitres[] = self::hydrate($row);
        }

        return $chapitres;
    }

    // ================================================================
    // CRUD Operations
    // ================================================================

    /**
     * Créer un nouveau chapitre
     */
    public function create(): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            INSERT INTO chapitres (module_id, title, description, order_index, is_published)
            VALUES (?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $this->module_id,
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
     * Mettre à jour un chapitre
     */
    public function update(): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            UPDATE chapitres
            SET module_id = ?, title = ?, description = ?, order_index = ?, is_published = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $this->module_id,
            $this->title,
            $this->description,
            $this->order_index,
            $this->is_published,
            $this->id
        ]);
    }

    /**
     * Supprimer un chapitre
     */
    public function delete(): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("DELETE FROM chapitres WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    // ================================================================
    // Helper methods
    // ================================================================

    /**
     * Hydrater un objet Chapitre depuis un tableau
     */
    public static function hydrate(array $data): self
    {
        $chapitre = new self();
        $chapitre->id = (int) $data['id'];
        $chapitre->module_id = (int) $data['module_id'];
        $chapitre->title = $data['title'];
        $chapitre->description = $data['description'];
        $chapitre->order_index = (int) $data['order_index'];
        $chapitre->is_published = (bool) $data['is_published'];
        $chapitre->created_at = $data['created_at'];
        $chapitre->updated_at = $data['updated_at'];

        return $chapitre;
    }

    /**
     * Convertir en tableau (pour JSON)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'module_id' => $this->module_id,
            'title' => $this->title,
            'description' => $this->description,
            'order_index' => $this->order_index,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
