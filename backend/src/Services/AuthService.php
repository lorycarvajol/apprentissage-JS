<?php

namespace App\Services;

use App\Config\Database;
use App\Config\JwtConfig;
use App\Models\User;
use Exception;

class AuthService
{
    /**
     * Inscrire un nouvel utilisateur.
     * Le compte est créé non vérifié : aucun token n'est délivré tant que
     * l'email n'a pas été confirmé (voir EmailVerificationService).
     */
    public static function register(array $data): array
    {
        // Validation des données
        $errors = self::validateRegistration($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Vérifier si l'email existe déjà
        if (User::findByEmail($data['email'])) {
            return [
                'success' => false,
                'errors' => ['email' => 'Cet email est déjà utilisé']
            ];
        }

        // Vérifier si le username existe déjà
        if (User::findByUsername($data['username'])) {
            return [
                'success' => false,
                'errors' => ['username' => 'Ce nom d\'utilisateur est déjà pris']
            ];
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setUsername($data['username'])
            ->setEmail($data['email'])
            ->setPassword(password_hash($data['password'], PASSWORD_DEFAULT))
            ->setFirstName($data['first_name'] ?? null)
            ->setLastName($data['last_name'] ?? null);

        if (!$user->create()) {
            return [
                'success' => false,
                'errors' => ['general' => 'Erreur lors de la création du compte']
            ];
        }

        $verificationToken = EmailVerificationService::createVerificationToken($user->getId());
        $verificationUrl = ($_ENV['FRONTEND_URL'] ?? '') . '/verify-email?token=' . $verificationToken;

        MailerService::sendVerificationEmail($user->getEmail(), $user->getUsername(), $verificationUrl);

        $result = [
            'success' => true,
            'message' => 'Inscription réussie. Vérifiez votre email pour activer votre compte.',
            'user' => $user->toArray(),
        ];

        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            $result['verification_token'] = $verificationToken; // ATTENTION: À retirer en production
            $result['verification_url'] = $verificationUrl;
        }

        return $result;
    }

    /**
     * Connecter un utilisateur
     */
    public static function login(string $identifier, string $password, bool $rememberMe = false): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Limiter par IP en premier : protège même contre les tentatives sur
        // des identifiants qui n'existent pas (énumération, credential stuffing)
        if (RateLimitService::isIpLocked($ip)) {
            return [
                'success' => false,
                'error' => 'Trop de tentatives depuis cette adresse. Veuillez réessayer plus tard.'
            ];
        }

        // Chercher l'utilisateur par email ou username
        $user = User::findByEmail($identifier);
        if (!$user) {
            $user = User::findByUsername($identifier);
        }

        // Vérifier si l'utilisateur existe
        if (!$user) {
            RateLimitService::logLoginAttempt(null, 'failed', 'Unknown identifier');
            return [
                'success' => false,
                'error' => 'Identifiants incorrects'
            ];
        }

        // Vérifier si le compte est bloqué
        if (RateLimitService::isLocked($user->getId())) {
            RateLimitService::logLoginAttempt($user->getId(), 'failed', 'Account locked');
            return [
                'success' => false,
                'error' => 'Votre compte est temporairement bloqué suite à trop de tentatives. Veuillez réessayer dans 15 minutes.'
            ];
        }

        // Vérifier le mot de passe
        if (!password_verify($password, $user->getPassword())) {
            // Enregistrer la tentative échouée
            $limitResult = RateLimitService::recordFailedAttempt($user->getId());
            RateLimitService::logLoginAttempt($user->getId(), 'failed', 'Invalid password');

            if ($limitResult['locked']) {
                return [
                    'success' => false,
                    'error' => 'Trop de tentatives de connexion. Votre compte est bloqué pour ' . $limitResult['lockout_minutes'] . ' minutes.'
                ];
            }

            return [
                'success' => false,
                'error' => 'Identifiants incorrects',
                'remaining_attempts' => $limitResult['remaining'] ?? null
            ];
        }

        // Vérifier si le compte est actif
        if (!$user->isActive()) {
            RateLimitService::logLoginAttempt($user->getId(), 'failed', 'Account inactive');
            return [
                'success' => false,
                'error' => 'Votre compte est désactivé'
            ];
        }

        // Vérifier si l'email a été confirmé
        if (!$user->isEmailVerified()) {
            RateLimitService::logLoginAttempt($user->getId(), 'failed', 'Email not verified');
            return [
                'success' => false,
                'error' => 'Veuillez vérifier votre adresse email avant de vous connecter',
                'email_not_verified' => true
            ];
        }

        // Connexion réussie - réinitialiser les tentatives
        RateLimitService::resetAttempts($user->getId());
        RateLimitService::logLoginAttempt($user->getId(), 'success');

        // Mettre à jour la dernière connexion
        $user->updateLastLogin();

        // Générer le token JWT (courte durée) et le refresh token (cookie httpOnly)
        $token = self::generateToken($user);
        $refresh = RefreshTokenService::issue($user->getId(), $rememberMe);

        return [
            'success' => true,
            'user' => $user->toArray(),
            'token' => $token,
            'expires_in' => JwtConfig::getExpirationSeconds(),
            'refresh_token' => $refresh['token'],
            'refresh_ttl_seconds' => $refresh['ttl_seconds'],
            'remember' => $rememberMe,
        ];
    }

    /**
     * Générer un token JWT pour un utilisateur
     */
    private static function generateToken(User $user): string
    {
        $payload = [
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()
        ];

        return JwtConfig::encode($payload);
    }

    /**
     * Vérifier un token JWT
     */
    public static function verifyToken(string $token): ?array
    {
        try {
            $decoded = JwtConfig::decode($token);
            return (array)$decoded->data;
        } catch (Exception $e) {
            error_log("Token verification failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extraire le token du header Authorization
     */
    public static function extractTokenFromHeader(): ?string
    {
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            return null;
        }

        $authHeader = $headers['Authorization'];

        // Format: "Bearer <token>"
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Obtenir l'utilisateur courant depuis le token
     */
    public static function getCurrentUser(): ?User
    {
        $token = self::extractTokenFromHeader();

        if (!$token) {
            return null;
        }

        $payload = self::verifyToken($token);

        if (!$payload || !isset($payload['user_id'])) {
            return null;
        }

        return User::findById($payload['user_id']);
    }

    /**
     * Valider les données d'inscription
     */
    private static function validateRegistration(array $data): array
    {
        $errors = [];

        // Username
        if (empty($data['username'])) {
            $errors['username'] = 'Le nom d\'utilisateur est requis';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Le nom d\'utilisateur doit contenir au moins 3 caractères';
        } elseif (strlen($data['username']) > 50) {
            $errors['username'] = 'Le nom d\'utilisateur ne peut pas dépasser 50 caractères';
        }

        // Email
        if (empty($data['email'])) {
            $errors['email'] = 'L\'email est requis';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide';
        }

        // Password - Utiliser la validation forte
        if (empty($data['password'])) {
            $errors['password'] = 'Le mot de passe est requis';
        } else {
            $passwordValidation = PasswordResetService::validatePassword($data['password']);
            if (!$passwordValidation['valid']) {
                $errors['password'] = $passwordValidation['message'];
            }
        }

        // Password confirmation
        if (isset($data['password_confirmation']) && $data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Les mots de passe ne correspondent pas';
        }

        return $errors;
    }

    /**
     * Rafraîchir une session à partir du refresh token (cookie httpOnly).
     * Fait tourner le refresh token (rotation) et émet un nouvel access token.
     */
    public static function refreshSession(string $rawRefreshToken): ?array
    {
        $rotated = RefreshTokenService::rotate($rawRefreshToken);

        if (!$rotated) {
            return null;
        }

        /** @var User $user */
        $user = $rotated['user'];

        if (!$user->isActive() || !$user->isEmailVerified()) {
            RefreshTokenService::revokeAllForUser($user->getId());
            return null;
        }

        return [
            'user' => $user->toArray(),
            'token' => self::generateToken($user),
            'expires_in' => JwtConfig::getExpirationSeconds(),
            'refresh_token' => $rotated['token'],
            'refresh_ttl_seconds' => $rotated['ttl_seconds'],
            'remember' => $rotated['remember'],
        ];
    }

    /**
     * Exporter toutes les données personnelles d'un utilisateur (droit à la
     * portabilité RGPD) : profil, progression, points, streak, badges,
     * théories lues et exercices soumis.
     */
    public static function exportUserData(User $user): array
    {
        $pdo = Database::getConnection();
        $userId = $user->getId();

        $fetchAll = function (string $sql) use ($pdo, $userId) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll();
        };

        return [
            'exported_at' => date('c'),
            'profil' => $user->toArray(),
            'progression' => $fetchAll(
                "SELECT chapitre_id, total_theories, theories_completed, total_exercices,
                        exercices_completed, completion_percentage, time_spent, started_at, completed_at
                 FROM progression WHERE user_id = :user_id"
            ),
            'points' => $fetchAll(
                "SELECT points, reason, reference_type, reference_id, earned_at
                 FROM points WHERE user_id = :user_id"
            ),
            'streak' => $fetchAll(
                "SELECT current_streak, longest_streak, last_activity_date
                 FROM streaks WHERE user_id = :user_id"
            ),
            'badges' => $fetchAll(
                "SELECT badge_id, earned_at FROM user_badges WHERE user_id = :user_id"
            ),
            'theories_lues' => $fetchAll(
                "SELECT theorie_id, time_spent, completed_at FROM theorie_lue WHERE user_id = :user_id"
            ),
            'exercices_soumis' => $fetchAll(
                "SELECT exercice_id, submitted_code, is_correct, points_earned, attempt_number, time_spent, submitted_at
                 FROM exercice_soumis WHERE user_id = :user_id"
            ),
        ];
    }
}
