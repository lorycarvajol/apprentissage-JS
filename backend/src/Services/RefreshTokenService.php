<?php

namespace App\Services;

use App\Config\Database;
use App\Models\User;
use PDOException;

class RefreshTokenService
{
    private const PERSISTENT_TTL_DAYS = 30; // "Se souvenir de moi" coché
    private const SESSION_TTL_DAYS = 1;     // Filet de sécurité serveur pour un cookie de session

    /**
     * Émettre un nouveau refresh token pour un utilisateur.
     * Le token brut n'est jamais stocké : seul son hash SHA-256 est persisté.
     */
    public static function issue(int $userId, bool $remember): array
    {
        $rawToken = bin2hex(random_bytes(32));
        $ttlDays = $remember ? self::PERSISTENT_TTL_DAYS : self::SESSION_TTL_DAYS;
        $ttlSeconds = $ttlDays * 86400;
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlSeconds);

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                "INSERT INTO refresh_tokens (user_id, token_hash, remember, expires_at)
                VALUES (:user_id, :token_hash, :remember, :expires_at)"
            );
            $stmt->execute([
                'user_id' => $userId,
                'token_hash' => hash('sha256', $rawToken),
                'remember' => $remember ? 1 : 0,
                'expires_at' => $expiresAt,
            ]);

            return ['token' => $rawToken, 'ttl_seconds' => $ttlSeconds, 'remember' => $remember];
        } catch (PDOException $e) {
            error_log("Error issuing refresh token: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Faire tourner un refresh token : révoque l'ancien, en émet un nouveau
     * en conservant le choix "remember me" d'origine.
     */
    public static function rotate(string $rawToken): ?array
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                "SELECT user_id, remember FROM refresh_tokens
                WHERE token_hash = :hash AND expires_at > NOW()"
            );
            $stmt->execute(['hash' => hash('sha256', $rawToken)]);
            $row = $stmt->fetch();

            if (!$row) {
                return null;
            }

            self::revoke($rawToken);

            $user = User::findById((int)$row['user_id']);
            if (!$user) {
                return null;
            }

            $issued = self::issue($user->getId(), (bool)$row['remember']);

            return ['user' => $user] + $issued;
        } catch (PDOException $e) {
            error_log("Error rotating refresh token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Révoquer un refresh token précis (logout).
     */
    public static function revoke(string $rawToken): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM refresh_tokens WHERE token_hash = :hash");
            $stmt->execute(['hash' => hash('sha256', $rawToken)]);
        } catch (PDOException $e) {
            error_log("Error revoking refresh token: " . $e->getMessage());
        }
    }

    /**
     * Révoquer tous les refresh tokens d'un utilisateur (ex: compte désactivé,
     * mot de passe changé, "déconnexion de tous les appareils").
     */
    public static function revokeAllForUser(int $userId): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM refresh_tokens WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("Error revoking user refresh tokens: " . $e->getMessage());
        }
    }

    /**
     * Nettoyer les tokens expirés (à brancher sur une tâche planifiée).
     */
    public static function cleanExpiredTokens(): void
    {
        try {
            $pdo = Database::getConnection();
            $pdo->exec("DELETE FROM refresh_tokens WHERE expires_at < NOW()");
        } catch (PDOException $e) {
            error_log("Error cleaning expired refresh tokens: " . $e->getMessage());
        }
    }
}
