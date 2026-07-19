<?php

namespace App\Services;

use App\Config\Database;
use App\Models\User;
use PDO;

class PasswordResetService
{
    /**
     * Générer un token de réinitialisation sécurisé
     */
    private static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Initier une demande de réinitialisation de mot de passe
     */
    public static function requestPasswordReset(string $email): array
    {
        $user = User::findByEmail($email);

        if (!$user) {
            // Pour des raisons de sécurité, ne pas révéler si l'email existe
            return [
                'success' => true,
                'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé'
            ];
        }

        // Générer un token unique
        $token = self::generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                "UPDATE users
                SET reset_token = :token,
                    reset_token_expires_at = :expires_at
                WHERE id = :id"
            );

            $stmt->execute([
                'token' => hash('sha256', $token),
                'expires_at' => $expiresAt,
                'id' => $user->getId()
            ]);

            $resetUrl = ($_ENV['FRONTEND_URL'] ?? '') . '/reset-password?token=' . $token;
            MailerService::sendPasswordResetEmail($user->getEmail(), $user->getUsername(), $resetUrl);

            if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                return [
                    'success' => true,
                    'message' => 'Lien de réinitialisation généré',
                    'token' => $token, // ATTENTION: À retirer en production
                    'reset_url' => $resetUrl
                ];
            }

            return [
                'success' => true,
                'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé'
            ];

        } catch (\PDOException $e) {
            error_log("Error requesting password reset: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Une erreur est survenue'
            ];
        }
    }

    /**
     * Vérifier la validité d'un token de réinitialisation
     */
    public static function verifyResetToken(string $token): ?User
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                "SELECT * FROM users
                WHERE reset_token = :token
                AND reset_token_expires_at > NOW()"
            );

            $stmt->execute(['token' => hash('sha256', $token)]);
            $data = $stmt->fetch();

            if (!$data) {
                return null;
            }

            return User::findById($data['id']);

        } catch (\PDOException $e) {
            error_log("Error verifying reset token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Réinitialiser le mot de passe avec un token valide
     */
    public static function resetPassword(string $token, string $newPassword): array
    {
        // Validation du mot de passe
        $validation = self::validatePassword($newPassword);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => ['password' => $validation['message']]
            ];
        }

        $user = self::verifyResetToken($token);

        if (!$user) {
            return [
                'success' => false,
                'error' => 'Token invalide ou expiré'
            ];
        }

        try {
            $pdo = Database::getConnection();
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "UPDATE users
                SET password = :password,
                    reset_token = NULL,
                    reset_token_expires_at = NULL,
                    failed_login_attempts = 0,
                    locked_until = NULL
                WHERE id = :id"
            );

            $stmt->execute([
                'password' => $hashedPassword,
                'id' => $user->getId()
            ]);

            // Un changement de mot de passe invalide toutes les sessions existantes
            RefreshTokenService::revokeAllForUser($user->getId());

            return [
                'success' => true,
                'message' => 'Mot de passe réinitialisé avec succès'
            ];

        } catch (\PDOException $e) {
            error_log("Error resetting password: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Une erreur est survenue'
            ];
        }
    }

    /**
     * Valider la force du mot de passe
     */
    public static function validatePassword(string $password): array
    {
        if (strlen($password) < 8) {
            return [
                'valid' => false,
                'message' => 'Le mot de passe doit contenir au moins 8 caractères'
            ];
        }

        // Vérifier la présence d'au moins une lettre majuscule
        if (!preg_match('/[A-Z]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Le mot de passe doit contenir au moins une lettre majuscule'
            ];
        }

        // Vérifier la présence d'au moins une lettre minuscule
        if (!preg_match('/[a-z]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Le mot de passe doit contenir au moins une lettre minuscule'
            ];
        }

        // Vérifier la présence d'au moins un chiffre
        if (!preg_match('/[0-9]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Le mot de passe doit contenir au moins un chiffre'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Mot de passe valide'
        ];
    }

    /**
     * Calculer la force du mot de passe (0-100)
     */
    public static function calculatePasswordStrength(string $password): int
    {
        $strength = 0;

        // Longueur (max 40 points)
        $length = strlen($password);
        $strength += min(40, $length * 4);

        // Lettres majuscules (10 points)
        if (preg_match('/[A-Z]/', $password)) {
            $strength += 10;
        }

        // Lettres minuscules (10 points)
        if (preg_match('/[a-z]/', $password)) {
            $strength += 10;
        }

        // Chiffres (10 points)
        if (preg_match('/[0-9]/', $password)) {
            $strength += 10;
        }

        // Caractères spéciaux (20 points)
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $strength += 20;
        }

        // Mix de caractères (10 points)
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);

        if (($hasUpper + $hasLower + $hasNumber + $hasSpecial) >= 3) {
            $strength += 10;
        }

        return min(100, $strength);
    }
}
