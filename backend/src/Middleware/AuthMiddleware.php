<?php

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware
{
    /**
     * Vérifier que l'utilisateur est authentifié
     */
    public static function authenticate(): bool
    {
        $user = AuthService::getCurrentUser();

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Non authentifié',
                'message' => 'Vous devez être connecté pour accéder à cette ressource'
            ]);
            return false;
        }

        return true;
    }

    /**
     * Vérifier que l'utilisateur a le rôle requis
     */
    public static function requireRole(string $role): bool
    {
        $user = AuthService::getCurrentUser();

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Non authentifié',
                'message' => 'Vous devez être connecté pour accéder à cette ressource'
            ]);
            return false;
        }

        if ($user->getRole() !== $role) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Accès interdit',
                'message' => 'Vous n\'avez pas les permissions nécessaires'
            ]);
            return false;
        }

        return true;
    }

    /**
     * Vérifier que l'utilisateur est admin
     */
    public static function requireAdmin(): bool
    {
        return self::requireRole('admin');
    }

    /**
     * Vérifier que l'utilisateur est étudiant
     */
    public static function requireStudent(): bool
    {
        return self::requireRole('student');
    }
}
