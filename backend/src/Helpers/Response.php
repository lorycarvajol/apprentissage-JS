<?php

namespace App\Helpers;

class Response
{
    /**
     * Envoyer une réponse JSON avec les bons headers UTF-8
     */
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Envoyer une réponse de succès
     */
    public static function success(array $data = [], int $statusCode = 200): void
    {
        self::json(array_merge(['success' => true], $data), $statusCode);
    }

    /**
     * Envoyer une réponse d'erreur
     */
    public static function error(string $message, int $statusCode = 400, array $extra = []): void
    {
        self::json(
            array_merge([
                'success' => false,
                'error' => $message
            ], $extra),
            $statusCode
        );
    }
}
