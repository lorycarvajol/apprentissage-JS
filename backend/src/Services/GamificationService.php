<?php

namespace App\Services;

use App\Config\Database;
use PDOException;

class GamificationService
{
    /**
     * Mettre à jour le streak d'activité quotidienne d'un utilisateur.
     * Incrémente si la dernière activité était hier, ne change rien si c'est
     * déjà aujourd'hui, réinitialise à 1 si la série est rompue.
     */
    public static function updateStreak(int $userId): void
    {
        $pdo = Database::getConnection();
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        try {
            $stmt = $pdo->prepare("SELECT current_streak, longest_streak, last_activity_date FROM streaks WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch();

            if (!$row) {
                $stmt = $pdo->prepare(
                    "INSERT INTO streaks (user_id, current_streak, longest_streak, last_activity_date)
                    VALUES (:user_id, 1, 1, :today)"
                );
                $stmt->execute(['user_id' => $userId, 'today' => $today]);
                return;
            }

            if ($row['last_activity_date'] === $today) {
                return;
            }

            $newStreak = ($row['last_activity_date'] === $yesterday) ? ((int) $row['current_streak'] + 1) : 1;
            $newLongest = max($newStreak, (int) $row['longest_streak']);

            $stmt = $pdo->prepare(
                "UPDATE streaks SET current_streak = :current_streak, longest_streak = :longest_streak, last_activity_date = :today
                WHERE user_id = :user_id"
            );
            $stmt->execute([
                'current_streak' => $newStreak,
                'longest_streak' => $newLongest,
                'today' => $today,
                'user_id' => $userId,
            ]);
        } catch (PDOException $e) {
            error_log("Error updating streak: " . $e->getMessage());
        }
    }

    /**
     * Vérifier les conditions de chaque badge non encore obtenu et attribuer
     * ceux qui sont désormais remplis (badge + points bonus).
     */
    public static function checkAndAwardBadges(int $userId): void
    {
        $pdo = Database::getConnection();

        try {
            $stmt = $pdo->prepare(
                "SELECT b.* FROM badges b
                WHERE b.id NOT IN (
                    SELECT badge_id FROM user_badges WHERE user_id = :user_id
                )"
            );
            $stmt->execute(['user_id' => $userId]);
            $candidateBadges = $stmt->fetchAll();

            foreach ($candidateBadges as $badge) {
                $conditionValue = json_decode($badge['condition_value'] ?? '{}', true) ?? [];

                if (self::isConditionMet($pdo, $userId, $badge['condition_type'], $conditionValue)) {
                    self::awardBadge($pdo, $userId, (int) $badge['id'], (int) $badge['points_reward'], $badge['name']);
                }
            }
        } catch (PDOException $e) {
            error_log("Error checking badges: " . $e->getMessage());
        }
    }

    private static function isConditionMet(\PDO $pdo, int $userId, string $conditionType, array $conditionValue): bool
    {
        switch ($conditionType) {
            case 'complete_chapter':
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM progression WHERE user_id = :user_id AND completed_at IS NOT NULL"
                );
                $stmt->execute(['user_id' => $userId]);
                return ((int) $stmt->fetchColumn()) >= ($conditionValue['count'] ?? 1);

            case 'complete_module':
                // `module_order` désigne le rang affiché du module (« Module 2 »),
                // et non son id technique. Les badges sont semés par schema.sql
                // avant qu'aucun module n'existe : y écrire un auto-incrément
                // revient à parier sur l'ordre de création du contenu. Le pari a
                // été perdu — « Débutant » visait module_id 1, or les modules du
                // curriculum ont reçu les id 2 à 10, l'id 1 ayant été consommé
                // puis libéré par un module de test. Le badge était devenu
                // inobtenable, silencieusement : la condition retourne false
                // quand le module n'a aucun chapitre, ce qui est aussi le cas
                // quand il n'existe pas.
                //
                // `module_id` reste accepté pour tout badge existant qui s'en
                // sert encore.
                $moduleId = $conditionValue['module_id'] ?? null;

                if (isset($conditionValue['module_order'])) {
                    $stmt = $pdo->prepare(
                        "SELECT id FROM modules WHERE order_index = :ordre ORDER BY id ASC LIMIT 1"
                    );
                    $stmt->execute(['ordre' => (int) $conditionValue['module_order']]);
                    $moduleId = $stmt->fetchColumn();

                    if ($moduleId === false) {
                        return false;
                    }
                }

                if ($moduleId === null) {
                    return false;
                }
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM chapitres WHERE module_id = :module_id");
                $stmt->execute(['module_id' => $moduleId]);
                $totalChapitres = (int) $stmt->fetchColumn();

                if ($totalChapitres === 0) {
                    return false;
                }

                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM progression p
                    INNER JOIN chapitres c ON c.id = p.chapitre_id
                    WHERE p.user_id = :user_id AND c.module_id = :module_id AND p.completed_at IS NOT NULL"
                );
                $stmt->execute(['user_id' => $userId, 'module_id' => $moduleId]);
                return ((int) $stmt->fetchColumn()) >= $totalChapitres;

            case 'streak_days':
                $stmt = $pdo->prepare("SELECT current_streak FROM streaks WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $userId]);
                $currentStreak = (int) $stmt->fetchColumn();
                return $currentStreak >= ($conditionValue['days'] ?? 1);

            case 'first_try_success':
                $stmt = $pdo->prepare(
                    "SELECT COUNT(DISTINCT exercice_id) FROM exercice_soumis
                    WHERE user_id = :user_id AND is_correct = 1 AND attempt_number = 1"
                );
                $stmt->execute(['user_id' => $userId]);
                return ((int) $stmt->fetchColumn()) >= ($conditionValue['count'] ?? 1);

            case 'complete_all_modules':
                // module_ids (optionnel) restreint le décompte à un sous-ensemble de modules.
                // Sans module_ids, compte tous les modules.
                $moduleIds = $conditionValue['module_ids'] ?? null;

                if ($moduleIds) {
                    $placeholders = implode(',', array_fill(0, count($moduleIds), '?'));
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM chapitres WHERE module_id IN ($placeholders)");
                    $stmt->execute($moduleIds);
                } else {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM chapitres");
                }
                $totalChapitres = (int) $stmt->fetchColumn();

                if ($totalChapitres === 0) {
                    return false;
                }

                if ($moduleIds) {
                    $placeholders = implode(',', array_fill(0, count($moduleIds), '?'));
                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*) FROM progression p
                        INNER JOIN chapitres c ON c.id = p.chapitre_id
                        WHERE p.user_id = ? AND p.completed_at IS NOT NULL AND c.module_id IN ($placeholders)"
                    );
                    $stmt->execute([$userId, ...$moduleIds]);
                } else {
                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*) FROM progression WHERE user_id = :user_id AND completed_at IS NOT NULL"
                    );
                    $stmt->execute(['user_id' => $userId]);
                }
                return ((int) $stmt->fetchColumn()) >= $totalChapitres;

            case 'points_total':
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(points), 0) FROM points WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $userId]);
                return ((int) $stmt->fetchColumn()) >= ($conditionValue['count'] ?? 1);

            case 'time_of_day':
                $currentHour = (int) date('H');
                return $currentHour >= ($conditionValue['start_hour'] ?? 0)
                    && $currentHour < ($conditionValue['end_hour'] ?? 24);

            case 'late_success':
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM exercice_soumis
                    WHERE user_id = :user_id AND is_correct = 1 AND attempt_number >= :min_attempt"
                );
                $stmt->execute(['user_id' => $userId, 'min_attempt' => $conditionValue['min_attempt'] ?? 6]);
                return ((int) $stmt->fetchColumn()) >= 1;

            default:
                return false;
        }
    }

    private static function awardBadge(\PDO $pdo, int $userId, int $badgeId, int $pointsReward, string $badgeName): void
    {
        $stmt = $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (:user_id, :badge_id)");
        $stmt->execute(['user_id' => $userId, 'badge_id' => $badgeId]);

        if ($pointsReward > 0) {
            $stmt = $pdo->prepare(
                "INSERT INTO points (user_id, points, reason, reference_type, reference_id)
                VALUES (:user_id, :points, :reason, 'badge', :reference_id)"
            );
            $stmt->execute([
                'user_id' => $userId,
                'points' => $pointsReward,
                'reason' => 'Badge obtenu : ' . $badgeName,
                'reference_id' => $badgeId,
            ]);
        }
    }

    /**
     * Récupérer, pour un utilisateur, tous les badges (obtenus ou non) ainsi
     * que son streak courant/record.
     */
    public static function getUserBadgesSummary(int $userId): array
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare(
            "SELECT b.id, b.name, b.description, b.category, b.points_reward,
                    ub.earned_at
            FROM badges b
            LEFT JOIN user_badges ub ON ub.badge_id = b.id AND ub.user_id = :user_id
            ORDER BY (ub.earned_at IS NULL), b.id ASC"
        );
        $stmt->execute(['user_id' => $userId]);
        $badges = array_map(function (array $row) {
            $earned = $row['earned_at'] !== null;

            return [
                'id' => (int) $row['id'],
                // Mystère total tant que le badge n'est pas obtenu : aucun indice
                // sur son nom ou la condition qui le débloque.
                'name' => $earned ? $row['name'] : '???',
                'description' => $earned ? $row['description'] : null,
                'category' => $row['category'],
                'points_reward' => (int) $row['points_reward'],
                'earned' => $earned,
                'earned_at' => $row['earned_at'],
            ];
        }, $stmt->fetchAll());

        $stmt = $pdo->prepare("SELECT current_streak, longest_streak FROM streaks WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $streakRow = $stmt->fetch();

        return [
            'badges' => $badges,
            'streak' => [
                'current' => $streakRow ? (int) $streakRow['current_streak'] : 0,
                'longest' => $streakRow ? (int) $streakRow['longest_streak'] : 0,
            ],
        ];
    }
}
