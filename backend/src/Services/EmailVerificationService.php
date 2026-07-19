<?php

namespace App\Services;

use App\Config\Database;
use App\Models\User;
use PDOException;

class EmailVerificationService
{
    /**
     * Générer un token de vérification et l'attacher (hashé) à un utilisateur.
     * Retourne le token brut, à envoyer par email (ou afficher en mode debug).
     */
    public static function createVerificationToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE users SET email_verification_token = :hash WHERE id = :id"
        );
        $stmt->execute([
            'hash' => hash('sha256', $token),
            'id' => $userId,
        ]);

        return $token;
    }

    /**
     * Valider un email à partir d'un token de vérification.
     */
    public static function verify(string $token): array
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE email_verification_token = :hash"
            );
            $stmt->execute(['hash' => hash('sha256', $token)]);
            $row = $stmt->fetch();

            if (!$row) {
                return [
                    'success' => false,
                    'error' => 'Lien de vérification invalide ou déjà utilisé',
                ];
            }

            $stmt = $pdo->prepare(
                "UPDATE users SET email_verified = 1, email_verification_token = NULL WHERE id = :id"
            );
            $stmt->execute(['id' => $row['id']]);

            return [
                'success' => true,
                'message' => 'Email vérifié avec succès. Vous pouvez maintenant vous connecter.',
            ];
        } catch (PDOException $e) {
            error_log("Error verifying email: " . $e->getMessage());
            return ['success' => false, 'error' => 'Une erreur est survenue'];
        }
    }

    /**
     * Renvoyer un email de vérification. Réponse volontairement générique
     * pour ne pas révéler si l'email existe ou est déjà vérifié.
     */
    public static function resend(string $email): array
    {
        $generic = [
            'success' => true,
            'message' => "Si ce compte existe et n'est pas encore vérifié, un email de vérification a été envoyé",
        ];

        $user = User::findByEmail($email);

        if (!$user || $user->isEmailVerified()) {
            return $generic;
        }

        $token = self::createVerificationToken($user->getId());
        $url = ($_ENV['FRONTEND_URL'] ?? '') . '/verify-email?token=' . $token;

        MailerService::sendVerificationEmail($user->getEmail(), $user->getUsername(), $url);

        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            return $generic + [
                'verification_token' => $token, // ATTENTION: À retirer en production
                'verification_url' => $url,
            ];
        }

        return $generic;
    }
}
