<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\EmailVerificationService;
use App\Services\PasswordResetService;
use App\Services\RefreshTokenService;
use App\Helpers\Response;

class AuthController
{
    private const REFRESH_COOKIE_NAME = 'refresh_token';
    private const REFRESH_COOKIE_PATH = '/api/auth';

    /**
     * Poser le cookie httpOnly contenant le refresh token.
     */
    private static function setRefreshCookie(string $token, int $ttlSeconds, bool $remember): void
    {
        setcookie(self::REFRESH_COOKIE_NAME, $token, [
            // Cookie de session (expire à la fermeture du navigateur) si "remember me" n'est pas coché
            'expires' => $remember ? time() + $ttlSeconds : 0,
            'path' => self::REFRESH_COOKIE_PATH,
            'secure' => ($_ENV['APP_ENV'] ?? 'development') === 'production',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function clearRefreshCookie(): void
    {
        setcookie(self::REFRESH_COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => self::REFRESH_COOKIE_PATH,
            'secure' => ($_ENV['APP_ENV'] ?? 'development') === 'production',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * POST /api/auth/register
     * Inscription d'un nouvel utilisateur (email à vérifier avant connexion)
     */
    public static function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            Response::error('Données invalides', 400);
            return;
        }

        $result = AuthService::register($data);

        if (!$result['success']) {
            Response::json($result, 400);
            return;
        }

        $response = [
            'message' => $result['message'],
            'user' => $result['user'],
        ];

        if (isset($result['verification_token'])) {
            $response['verification_token'] = $result['verification_token'];
            $response['verification_url'] = $result['verification_url'];
        }

        Response::success($response, 201);
    }

    /**
     * POST /api/auth/login
     * Connexion d'un utilisateur
     */
    public static function login(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['identifier']) || empty($data['password'])) {
            Response::error('Email/Username et mot de passe requis', 400);
            return;
        }

        $rememberMe = (bool)($data['remember_me'] ?? false);
        $result = AuthService::login($data['identifier'], $data['password'], $rememberMe);

        if (!$result['success']) {
            Response::json($result, 401);
            return;
        }

        self::setRefreshCookie($result['refresh_token'], $result['refresh_ttl_seconds'], $rememberMe);

        Response::success([
            'message' => 'Connexion réussie',
            'user' => $result['user'],
            'token' => $result['token'],
            'expires_in' => $result['expires_in'],
        ], 200);
    }

    /**
     * POST /api/auth/refresh
     * Émettre un nouvel access token à partir du refresh token (cookie httpOnly)
     */
    public static function refresh(): void
    {
        $rawRefreshToken = $_COOKIE[self::REFRESH_COOKIE_NAME] ?? null;

        if (!$rawRefreshToken) {
            Response::error('Session expirée, veuillez vous reconnecter', 401);
            return;
        }

        $result = AuthService::refreshSession($rawRefreshToken);

        if (!$result) {
            self::clearRefreshCookie();
            Response::error('Session invalide ou expirée, veuillez vous reconnecter', 401);
            return;
        }

        self::setRefreshCookie($result['refresh_token'], $result['refresh_ttl_seconds'], $result['remember']);

        Response::success([
            'user' => $result['user'],
            'token' => $result['token'],
            'expires_in' => $result['expires_in'],
        ], 200);
    }

    /**
     * GET /api/auth/me
     * Obtenir les informations de l'utilisateur courant
     */
    public static function me(): void
    {
        $user = AuthService::getCurrentUser();

        if (!$user) {
            Response::error('Non authentifié', 401);
            return;
        }

        Response::success(['user' => $user->toArray()], 200);
    }

    /**
     * GET /api/auth/me/export
     * Exporter toutes les données personnelles de l'utilisateur courant (RGPD)
     */
    public static function exportData(): void
    {
        $user = AuthService::getCurrentUser();

        if (!$user) {
            Response::error('Non authentifié', 401);
            return;
        }

        Response::success(AuthService::exportUserData($user), 200);
    }

    /**
     * DELETE /api/auth/me
     * Supprimer définitivement le compte de l'utilisateur courant (droit à l'oubli RGPD)
     */
    public static function deleteAccount(): void
    {
        $user = AuthService::getCurrentUser();

        if (!$user) {
            Response::error('Non authentifié', 401);
            return;
        }

        RefreshTokenService::revokeAllForUser($user->getId());

        if (!$user->delete()) {
            Response::error('Erreur lors de la suppression du compte', 500);
            return;
        }

        self::clearRefreshCookie();

        Response::success(['message' => 'Compte supprimé avec succès'], 200);
    }

    /**
     * POST /api/auth/logout
     * Déconnexion : révoque le refresh token côté serveur et efface le cookie
     */
    public static function logout(): void
    {
        $rawRefreshToken = $_COOKIE[self::REFRESH_COOKIE_NAME] ?? null;

        if ($rawRefreshToken) {
            RefreshTokenService::revoke($rawRefreshToken);
        }

        self::clearRefreshCookie();

        Response::success(['message' => 'Déconnexion réussie'], 200);
    }

    /**
     * POST /api/auth/verify-email
     * Confirmer l'adresse email à partir du token reçu
     */
    public static function verifyEmail(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['token'])) {
            Response::error('Token requis', 400);
            return;
        }

        $result = EmailVerificationService::verify($data['token']);

        Response::json($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /api/auth/resend-verification
     * Renvoyer un email de vérification
     */
    public static function resendVerification(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['email'])) {
            Response::error('Email requis', 400);
            return;
        }

        $result = EmailVerificationService::resend($data['email']);

        Response::json($result, 200);
    }

    /**
     * POST /api/auth/forgot-password
     * Demander un lien de réinitialisation de mot de passe
     */
    public static function forgotPassword(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['email'])) {
            Response::error('Email requis', 400);
            return;
        }

        $result = PasswordResetService::requestPasswordReset($data['email']);

        Response::json($result, 200);
    }

    /**
     * POST /api/auth/reset-password
     * Réinitialiser le mot de passe avec un token
     */
    public static function resetPassword(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['token']) || empty($data['password'])) {
            Response::error('Token et nouveau mot de passe requis', 400);
            return;
        }

        $result = PasswordResetService::resetPassword($data['token'], $data['password']);

        if (!$result['success']) {
            Response::json($result, 400);
            return;
        }

        Response::json($result, 200);
    }

    /**
     * POST /api/auth/check-password-strength
     * Vérifier la force d'un mot de passe
     */
    public static function checkPasswordStrength(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['password'])) {
            Response::error('Mot de passe requis', 400);
            return;
        }

        $strength = PasswordResetService::calculatePasswordStrength($data['password']);
        $validation = PasswordResetService::validatePassword($data['password']);

        Response::json([
            'strength' => $strength,
            'valid' => $validation['valid'],
            'message' => $validation['message']
        ], 200);
    }
}
