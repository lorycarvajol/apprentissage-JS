<?php

namespace App\Controllers;

use App\Config\Database;
use App\Services\AuthService;
use App\Helpers\Response;

class DashboardController
{
    /**
     * GET /api/dashboard/stats
     * Statistiques de base de l'utilisateur courant : points, exercices
     * réussis, temps passé sur les exercices.
     */
    public static function stats(): void
    {
        $user = AuthService::getCurrentUser();

        if (!$user) {
            Response::error('Non authentifié', 401);
            return;
        }

        $pdo = Database::getConnection();
        $userId = $user->getId();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(points), 0) FROM points WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $totalPoints = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT exercice_id) FROM exercice_soumis
            WHERE user_id = :user_id AND is_correct = 1"
        );
        $stmt->execute(['user_id' => $userId]);
        $exercicesCompleted = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(time_spent), 0) FROM exercice_soumis WHERE user_id = :user_id"
        );
        $stmt->execute(['user_id' => $userId]);
        $timeSpentSeconds = (int) $stmt->fetchColumn();

        Response::success([
            'total_points' => $totalPoints,
            'exercices_completed' => $exercicesCompleted,
            'time_spent_minutes' => (int) round($timeSpentSeconds / 60),
        ], 200);
    }
}
