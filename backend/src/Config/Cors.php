<?php

namespace App\Config;

class Cors
{
    /**
     * Origines toujours acceptées HORS production : les ports Vite utilisés en
     * développement. En production, cette souplesse disparaît complètement —
     * seule CORS_ORIGIN fait foi (voir isDevelopment()).
     */
    private const DEV_ORIGIN_PATTERN = '/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/';

    public static function handle(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Une requête same-origin n'envoie pas d'en-tête Origin. C'est le cas
        // nominal en production : nginx sert le SPA et l'API sous le même
        // domaine (voir docker/nginx/default.conf), donc le navigateur ne fait
        // ni requête préliminaire ni contrôle CORS. Rien à émettre.
        if ($origin === '') {
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(204);
                exit;
            }
            return;
        }

        // Vary: Origin — sans lui, un cache intermédiaire pourrait resservir à
        // une origine la réponse mise en cache pour une autre.
        header('Vary: Origin');

        if (!self::isAllowed($origin)) {
            // Origine inconnue : on n'émet AUCUN en-tête Access-Control-*. Le
            // navigateur bloquera l'appel de lui-même. Renvoyer une origine de
            // repli (l'ancien comportement) donnait une réponse trompeuse, et
            // aurait masqué une CORS_ORIGIN mal configurée en production.
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(403);
                exit;
            }
            return;
        }

        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Max-Age: 86400');
            http_response_code(204);
            exit;
        }
    }

    /**
     * Une origine est acceptée si elle figure dans CORS_ORIGIN (liste séparée
     * par des virgules), ou — hors production seulement — s'il s'agit d'un
     * localhost.
     */
    private static function isAllowed(string $origin): bool
    {
        if (in_array($origin, self::allowedOrigins(), true)) {
            return true;
        }

        return self::isDevelopment() && preg_match(self::DEV_ORIGIN_PATTERN, $origin) === 1;
    }

    /**
     * @return string[]
     */
    private static function allowedOrigins(): array
    {
        $configured = trim((string) ($_ENV['CORS_ORIGIN'] ?? ''));

        if ($configured === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $configured)),
            fn(string $value): bool => $value !== ''
        ));
    }

    private static function isDevelopment(): bool
    {
        return ($_ENV['APP_ENV'] ?? 'development') !== 'production';
    }
}
