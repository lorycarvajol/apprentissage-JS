<?php

namespace App\Config;

class Cors
{
    public static function handle(): void
    {
        // Obtenir l'origine de la requête
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Liste des origins autorisées
        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:5174',
        ];

        // Si l'origine est dans la liste ou si c'est en développement local
        if (in_array($origin, $allowedOrigins) || self::isLocalhost($origin)) {
            $allowedOrigin = $origin;
        } else {
            $allowedOrigin = $_ENV['CORS_ORIGIN'] ?? 'http://localhost:5173';
        }

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header("Access-Control-Allow-Origin: $allowedOrigin");
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
            http_response_code(200);
            exit;
        }

        // Set CORS headers for all requests
        header("Access-Control-Allow-Origin: $allowedOrigin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }

    /**
     * Vérifie si l'origine est localhost (pour le développement)
     */
    private static function isLocalhost(string $origin): bool
    {
        return preg_match('/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $origin) === 1;
    }
}
