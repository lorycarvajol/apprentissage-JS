<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class User
{
    private ?int $id = null;
    private string $username;
    private string $email;
    private string $password;
    private ?string $firstName = null;
    private ?string $lastName = null;
    private string $role = 'student';
    private bool $isActive = true;
    private bool $emailVerified = false;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;
    private ?string $lastLoginAt = null;

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function getLastLoginAt(): ?string
    {
        return $this->lastLoginAt;
    }

    // Setters
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function create(): bool
    {
        try {
            $pdo = Database::getConnection();
            $sql = "INSERT INTO users (username, email, password, first_name, last_name, role, is_active)
                    VALUES (:username, :email, :password, :first_name, :last_name, :role, :is_active)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'username' => $this->username,
                'email' => $this->email,
                'password' => $this->password,
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'role' => $this->role,
                'is_active' => $this->isActive ? 1 : 0
            ]);

            $this->id = (int)$pdo->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouver un utilisateur par ID
     */
    public static function findById(int $id): ?self
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $data = $stmt->fetch();

            if (!$data) {
                return null;
            }

            return self::hydrate($data);
        } catch (PDOException $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trouver un utilisateur par email
     */
    public static function findByEmail(string $email): ?self
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $data = $stmt->fetch();

            if (!$data) {
                return null;
            }

            return self::hydrate($data);
        } catch (PDOException $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trouver un utilisateur par username
     */
    public static function findByUsername(string $username): ?self
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $data = $stmt->fetch();

            if (!$data) {
                return null;
            }

            return self::hydrate($data);
        } catch (PDOException $e) {
            error_log("Error finding user by username: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mettre à jour la dernière connexion
     */
    public function updateLastLogin(): bool
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer définitivement l'utilisateur (droit à l'oubli RGPD).
     * Les tables liées (progression, points, badges, etc.) sont supprimées
     * automatiquement via les contraintes ON DELETE CASCADE.
     */
    public function delete(): bool
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            return $stmt->execute(['id' => $this->id]);
        } catch (PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour l'utilisateur
     */
    public function update(): bool
    {
        try {
            $pdo = Database::getConnection();
            $sql = "UPDATE users SET
                    username = :username,
                    email = :email,
                    first_name = :first_name,
                    last_name = :last_name,
                    role = :role,
                    is_active = :is_active
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                'id' => $this->id,
                'username' => $this->username,
                'email' => $this->email,
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'role' => $this->role,
                'is_active' => $this->isActive ? 1 : 0
            ]);
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hydrater un objet User à partir des données de la BDD
     */
    private static function hydrate(array $data): self
    {
        $user = new self();
        $user->id = (int)$data['id'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->firstName = $data['first_name'];
        $user->lastName = $data['last_name'];
        $user->role = $data['role'];
        $user->isActive = (bool)$data['is_active'];
        $user->emailVerified = (bool)($data['email_verified'] ?? false);
        $user->createdAt = $data['created_at'];
        $user->updatedAt = $data['updated_at'];
        $user->lastLoginAt = $data['last_login_at'];

        return $user;
    }

    /**
     * Convertir l'utilisateur en tableau (sans le mot de passe)
     */
    public function toArray(bool $includePassword = false): array
    {
        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'role' => $this->role,
            'is_active' => $this->isActive,
            'email_verified' => $this->emailVerified,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'last_login_at' => $this->lastLoginAt
        ];

        if ($includePassword) {
            $data['password'] = $this->password;
        }

        return $data;
    }
}
