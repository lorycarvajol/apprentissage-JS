<?php

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Models\Exercice;
use App\Services\AuthService;
use App\Services\GamificationService;
use App\Services\ProgressionService;
use App\Helpers\Response;
use PDO;

class ExerciceController
{
    /**
     * GET /api/exercices
     * Récupérer tous les exercices (admin uniquement en pratique : inclut
     * solution_code, jamais exposé aux endpoints publics de contenu).
     */
    public static function index(): void
    {
        try {
            $exercices = Exercice::findAll();

            $exercicesArray = array_map(fn($exercice) => $exercice->toArray(true), $exercices);

            Response::success(['exercices' => $exercicesArray], 200);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la récupération des exercices', 500);
        }
    }

    /**
     * GET /api/exercices/{id}
     * Récupérer un exercice par son ID (admin uniquement en pratique, voir index()).
     */
    public static function show(int $id): void
    {
        try {
            $exercice = Exercice::findById($id);

            if (!$exercice) {
                Response::error('Exercice non trouvé', 404);
                return;
            }

            Response::success(['exercice' => $exercice->toArray(true)], 200);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la récupération de l\'exercice', 500);
        }
    }

    /**
     * POST /api/exercices
     * Créer un nouvel exercice (admin only)
     */
    public static function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || empty($data['title']) || !isset($data['chapitre_id']) ||
                empty($data['description']) || !isset($data['order_index'])) {
                Response::error('Titre, chapitre_id, description et ordre requis', 400);
                return;
            }

            $exercice = new Exercice();
            $exercice->setChapitreId((int) $data['chapitre_id'])
                     ->setTitle($data['title'])
                     ->setDescription($data['description'])
                     ->setInstructions($data['instructions'] ?? null)
                     ->setStarterCode($data['starter_code'] ?? null)
                     ->setSolutionCode($data['solution_code'] ?? null)
                     ->setExpectedOutput($data['expected_output'] ?? null)
                     ->setDifficulty($data['difficulty'] ?? 'medium')
                     ->setPoints((int) ($data['points'] ?? 10))
                     ->setOrderIndex((int) $data['order_index']);

            if ($exercice->create()) {
                Response::success([
                    'message' => 'Exercice créé avec succès',
                    'exercice' => $exercice->toArray(true)
                ], 201);
            } else {
                Response::error('Erreur lors de la création de l\'exercice', 500);
            }
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la création de l\'exercice', 500);
        }
    }

    /**
     * PUT /api/exercices/{id}
     * Mettre à jour un exercice (admin only)
     */
    public static function update(int $id): void
    {
        try {
            $exercice = Exercice::findById($id);

            if (!$exercice) {
                Response::error('Exercice non trouvé', 404);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                Response::error('Données invalides', 400);
                return;
            }

            if (isset($data['chapitre_id'])) {
                $exercice->setChapitreId((int) $data['chapitre_id']);
            }
            if (isset($data['title'])) {
                $exercice->setTitle($data['title']);
            }
            if (isset($data['description'])) {
                $exercice->setDescription($data['description']);
            }
            if (isset($data['instructions'])) {
                $exercice->setInstructions($data['instructions']);
            }
            if (isset($data['starter_code'])) {
                $exercice->setStarterCode($data['starter_code']);
            }
            if (isset($data['solution_code'])) {
                $exercice->setSolutionCode($data['solution_code']);
            }
            if (isset($data['expected_output'])) {
                $exercice->setExpectedOutput($data['expected_output']);
            }
            if (isset($data['difficulty'])) {
                $exercice->setDifficulty($data['difficulty']);
            }
            if (isset($data['points'])) {
                $exercice->setPoints((int) $data['points']);
            }
            if (isset($data['order_index'])) {
                $exercice->setOrderIndex((int) $data['order_index']);
            }

            if ($exercice->update()) {
                Response::success([
                    'message' => 'Exercice mis à jour avec succès',
                    'exercice' => $exercice->toArray(true)
                ], 200);
            } else {
                Response::error('Erreur lors de la mise à jour de l\'exercice', 500);
            }
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la mise à jour de l\'exercice', 500);
        }
    }

    /**
     * DELETE /api/exercices/{id}
     * Supprimer un exercice (admin only)
     */
    public static function destroy(int $id): void
    {
        try {
            $exercice = Exercice::findById($id);

            if (!$exercice) {
                Response::error('Exercice non trouvé', 404);
                return;
            }

            if ($exercice->delete()) {
                Response::success(['message' => 'Exercice supprimé avec succès'], 200);
            } else {
                Response::error('Erreur lors de la suppression de l\'exercice', 500);
            }
        } catch (\Exception $e) {
            Response::error('Erreur lors de la suppression de l\'exercice', 500);
        }
    }

    /**
     * Normaliser une sortie avant comparaison : tous les espaces/tabulations/
     * retours à la ligne sont supprimés (pas seulement réduits), insensible
     * à la casse. Le but est de ne pénaliser que les vraies différences de
     * logique, jamais des détails de présentation — y compris quand c'est
     * la solution de référence elle-même qui n'a pas de séparateur entre
     * deux echo, contrairement à un étudiant qui ajoute un \n (bonne pratique).
     */
    private static function normalizeOutput(string $output): string
    {
        $normalized = preg_replace('/\s+/', '', $output);
        return mb_strtolower($normalized ?? '', 'UTF-8');
    }

    /**
     * POST /api/exercices/{id}/submit
     * Contrairement au backend PHP dont le sandbox tournait côté serveur
     * (CodeExecutionService/proc_open), le JS soumis est exécuté côté client
     * dans un Web Worker isolé (voir frontend/src/utils/jsSandbox.js) : le
     * client envoie ici le résultat déjà capturé (output/error/timed_out),
     * pas le code brut à exécuter. Ce endpoint ne fait que comparer cette
     * sortie à expected_output (calculé une fois par l'admin au moment de la
     * création de l'exercice, via le même sandbox), enregistrer la tentative
     * et attribuer les points si correct.
     */
    public static function submit(int $id): void
    {
        if (!AuthMiddleware::authenticate()) {
            return;
        }

        $exercice = Exercice::findById($id);

        if (!$exercice) {
            Response::error('Exercice non trouvé', 404);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['code']) || trim($data['code']) === '') {
            Response::error('Code requis', 400);
            return;
        }

        $user = AuthService::getCurrentUser();
        $submittedCode = $data['code'];
        $timeSpent = isset($data['time_spent']) ? (int) $data['time_spent'] : 0;

        $output = (string) ($data['output'] ?? '');
        $error = isset($data['error']) && $data['error'] !== '' ? (string) $data['error'] : null;
        $timedOut = !empty($data['timed_out']);

        $expectedOutput = $exercice->getExpectedOutput() ?? '';

        $isCorrect = !$timedOut && $error === null
            && self::normalizeOutput($output) === self::normalizeOutput($expectedOutput);

        $pdo = Database::getConnection();

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM exercice_soumis WHERE user_id = :user_id AND exercice_id = :exercice_id"
        );
        $stmt->execute(['user_id' => $user->getId(), 'exercice_id' => $id]);
        $attemptNumber = (int) $stmt->fetchColumn() + 1;

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM exercice_soumis
            WHERE user_id = :user_id AND exercice_id = :exercice_id AND is_correct = 1"
        );
        $stmt->execute(['user_id' => $user->getId(), 'exercice_id' => $id]);
        $alreadySolved = ((int) $stmt->fetchColumn()) > 0;

        $pointsEarned = ($isCorrect && !$alreadySolved) ? $exercice->getPoints() : 0;

        $stmt = $pdo->prepare(
            "INSERT INTO exercice_soumis
            (user_id, exercice_id, submitted_code, is_correct, execution_result, points_earned, attempt_number, time_spent)
            VALUES (:user_id, :exercice_id, :submitted_code, :is_correct, :execution_result, :points_earned, :attempt_number, :time_spent)"
        );
        $stmt->execute([
            'user_id' => $user->getId(),
            'exercice_id' => $id,
            'submitted_code' => $submittedCode,
            'is_correct' => $isCorrect ? 1 : 0,
            'execution_result' => json_encode([
                'output' => $output,
                'error' => $error,
                'timed_out' => $timedOut,
            ]),
            'points_earned' => $pointsEarned,
            'attempt_number' => $attemptNumber,
            'time_spent' => $timeSpent,
        ]);

        if ($pointsEarned > 0) {
            $stmt = $pdo->prepare(
                "INSERT INTO points (user_id, points, reason, reference_type, reference_id)
                VALUES (:user_id, :points, :reason, 'exercice', :reference_id)"
            );
            $stmt->execute([
                'user_id' => $user->getId(),
                'points' => $pointsEarned,
                'reason' => 'Exercice réussi : ' . $exercice->getTitle(),
                'reference_id' => $id,
            ]);
        }

        if ($isCorrect) {
            ProgressionService::recalculateChapitreProgression($user->getId(), $exercice->getChapitreId());
            GamificationService::updateStreak($user->getId());
            GamificationService::checkAndAwardBadges($user->getId());
        }

        Response::success([
            'is_correct' => $isCorrect,
            'output' => $output,
            'error' => $error,
            'timed_out' => $timedOut,
            'points_earned' => $pointsEarned,
            'attempt_number' => $attemptNumber,
            'expected_output' => $isCorrect ? null : trim($expectedOutput),
        ], 200);
    }
}
