<?php

namespace App\Services;

use App\Config\Database;
use PDOException;

class ProgressionService
{
    /**
     * Enregistrer la lecture d'une théorie par un utilisateur (idempotent) et
     * recalculer la progression du chapitre associé.
     */
    public static function markTheorieAsRead(int $userId, int $theorieId, int $timeSpent): void
    {
        $pdo = Database::getConnection();

        try {
            $stmt = $pdo->prepare("SELECT chapitre_id FROM theories WHERE id = :id");
            $stmt->execute(['id' => $theorieId]);
            $chapitreId = $stmt->fetchColumn();

            if ($chapitreId === false) {
                return;
            }

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM theorie_lue WHERE user_id = :user_id AND theorie_id = :theorie_id"
            );
            $stmt->execute(['user_id' => $userId, 'theorie_id' => $theorieId]);
            $alreadyRead = ((int) $stmt->fetchColumn()) > 0;

            if (!$alreadyRead) {
                $stmt = $pdo->prepare(
                    "INSERT INTO theorie_lue (user_id, theorie_id, time_spent)
                    VALUES (:user_id, :theorie_id, :time_spent)"
                );
                $stmt->execute([
                    'user_id' => $userId,
                    'theorie_id' => $theorieId,
                    'time_spent' => $timeSpent,
                ]);
            }

            self::recalculateChapitreProgression($userId, (int) $chapitreId);
        } catch (PDOException $e) {
            error_log("Error marking theorie as read: " . $e->getMessage());
        }
    }

    /**
     * Recalculer et upserter la ligne de progression d'un utilisateur pour un
     * chapitre donné, à partir des théories lues et des exercices réussis.
     */
    public static function recalculateChapitreProgression(int $userId, int $chapitreId): void
    {
        $pdo = Database::getConnection();

        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM theories WHERE chapitre_id = :chapitre_id");
            $stmt->execute(['chapitre_id' => $chapitreId]);
            $totalTheories = (int) $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM exercices WHERE chapitre_id = :chapitre_id");
            $stmt->execute(['chapitre_id' => $chapitreId]);
            $totalExercices = (int) $stmt->fetchColumn();

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM theorie_lue tl
                INNER JOIN theories t ON t.id = tl.theorie_id
                WHERE tl.user_id = :user_id AND t.chapitre_id = :chapitre_id"
            );
            $stmt->execute(['user_id' => $userId, 'chapitre_id' => $chapitreId]);
            $theoriesCompleted = (int) $stmt->fetchColumn();

            $stmt = $pdo->prepare(
                "SELECT COUNT(DISTINCT es.exercice_id) FROM exercice_soumis es
                INNER JOIN exercices e ON e.id = es.exercice_id
                WHERE es.user_id = :user_id AND e.chapitre_id = :chapitre_id AND es.is_correct = 1"
            );
            $stmt->execute(['user_id' => $userId, 'chapitre_id' => $chapitreId]);
            $exercicesCompleted = (int) $stmt->fetchColumn();

            $totalItems = $totalTheories + $totalExercices;
            $completedItems = $theoriesCompleted + $exercicesCompleted;
            $percentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 2) : 0.00;
            $isComplete = $totalItems > 0 && $completedItems >= $totalItems;

            $stmt = $pdo->prepare(
                "INSERT INTO progression
                    (user_id, chapitre_id, total_theories, theories_completed,
                     total_exercices, exercices_completed, completion_percentage,
                     started_at, completed_at)
                VALUES
                    (:user_id, :chapitre_id, :total_theories, :theories_completed,
                     :total_exercices, :exercices_completed, :completion_percentage,
                     NOW(), :completed_at)
                ON DUPLICATE KEY UPDATE
                    total_theories = VALUES(total_theories),
                    theories_completed = VALUES(theories_completed),
                    total_exercices = VALUES(total_exercices),
                    exercices_completed = VALUES(exercices_completed),
                    completion_percentage = VALUES(completion_percentage),
                    completed_at = COALESCE(progression.completed_at, VALUES(completed_at))"
            );
            $stmt->execute([
                'user_id' => $userId,
                'chapitre_id' => $chapitreId,
                'total_theories' => $totalTheories,
                'theories_completed' => $theoriesCompleted,
                'total_exercices' => $totalExercices,
                'exercices_completed' => $exercicesCompleted,
                'completion_percentage' => $percentage,
                'completed_at' => $isComplete ? date('Y-m-d H:i:s') : null,
            ]);
        } catch (PDOException $e) {
            error_log("Error recalculating chapitre progression: " . $e->getMessage());
        }
    }
}
