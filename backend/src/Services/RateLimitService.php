<?php

namespace App\Services;

use App\Config\Database;
use PDO;

class RateLimitService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutes en secondes

    private const MAX_IP_ATTEMPTS = 20;
    private const IP_WINDOW_MINUTES = 15;

    /**
     * Vérifier si une adresse IP a dépassé le nombre d'échecs autorisé,
     * tous comptes confondus (protège contre l'énumération de comptes et
     * le credential stuffing, même sur des identifiants inconnus).
     */
    public static function isIpLocked(string $ip): bool
    {
        if ($ip === '') {
            return false;
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) as attempts FROM login_history
                WHERE ip_address = :ip
                AND status = 'failed'
                AND created_at > (NOW() - INTERVAL " . self::IP_WINDOW_MINUTES . " MINUTE)"
            );
            $stmt->execute(['ip' => $ip]);
            $result = $stmt->fetch();

            return ((int)($result['attempts'] ?? 0)) >= self::MAX_IP_ATTEMPTS;
        } catch (\PDOException $e) {
            error_log("Error checking IP lock status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un utilisateur est bloqué
     */
    public static function isLocked(int $userId): bool
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                "SELECT locked_until FROM users
                WHERE id = :id
                AND locked_until > NOW()"
            );

            $stmt->execute(['id' => $userId]);
            $result = $stmt->fetch();

            return $result !== false;

        } catch (\PDOException $e) {
            error_log("Error checking lock status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enregistrer une tentative de connexion échouée
     */
    public static function recordFailedAttempt(int $userId): array
    {
        try {
            $pdo = Database::getConnection();

            // Incrémenter le compteur
            $stmt = $pdo->prepare(
                "UPDATE users
                SET failed_login_attempts = failed_login_attempts + 1
                WHERE id = :id"
            );
            $stmt->execute(['id' => $userId]);

            // Vérifier le nombre total de tentatives
            $stmt = $pdo->prepare(
                "SELECT failed_login_attempts FROM users WHERE id = :id"
            );
            $stmt->execute(['id' => $userId]);
            $result = $stmt->fetch();

            $attempts = (int)$result['failed_login_attempts'];

            // Si le nombre max est atteint, bloquer le compte
            if ($attempts >= self::MAX_ATTEMPTS) {
                $lockoutUntil = date('Y-m-d H:i:s', time() + self::LOCKOUT_TIME);

                $stmt = $pdo->prepare(
                    "UPDATE users
                    SET locked_until = :locked_until
                    WHERE id = :id"
                );
                $stmt->execute([
                    'locked_until' => $lockoutUntil,
                    'id' => $userId
                ]);

                return [
                    'locked' => true,
                    'attempts' => $attempts,
                    'lockout_minutes' => self::LOCKOUT_TIME / 60
                ];
            }

            return [
                'locked' => false,
                'attempts' => $attempts,
                'remaining' => self::MAX_ATTEMPTS - $attempts
            ];

        } catch (\PDOException $e) {
            error_log("Error recording failed attempt: " . $e->getMessage());
            return ['locked' => false, 'attempts' => 0];
        }
    }

    /**
     * Réinitialiser les tentatives après une connexion réussie
     */
    public static function resetAttempts(int $userId): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                "UPDATE users
                SET failed_login_attempts = 0,
                    locked_until = NULL
                WHERE id = :id"
            );
            $stmt->execute(['id' => $userId]);

        } catch (\PDOException $e) {
            error_log("Error resetting attempts: " . $e->getMessage());
        }
    }

    /**
     * Enregistrer une tentative de connexion dans l'historique
     */
    public static function logLoginAttempt(
        ?int $userId,
        string $status,
        ?string $failureReason = null
    ): void {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                "INSERT INTO login_history
                (user_id, ip_address, user_agent, status, failure_reason)
                VALUES (:user_id, :ip, :user_agent, :status, :failure_reason)"
            );

            $stmt->execute([
                'user_id' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'status' => $status,
                'failure_reason' => $failureReason
            ]);

        } catch (\PDOException $e) {
            error_log("Error logging login attempt: " . $e->getMessage());
        }
    }
}
