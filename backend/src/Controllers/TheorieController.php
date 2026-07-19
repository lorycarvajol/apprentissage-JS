<?php

namespace App\Controllers;

use App\Models\Theorie;
use App\Services\AuthService;
use App\Services\GamificationService;
use App\Services\ProgressionService;
use App\Helpers\Response;

class TheorieController
{
    /**
     * GET /api/theories
     * Récupérer toutes les théories
     */
    public static function index(): void
    {
        try {
            $theories = Theorie::findAll();

            $theoriesArray = array_map(fn($theorie) => $theorie->toArray(), $theories);

            Response::success(['theories' => $theoriesArray], 200);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la récupération des théories', 500);
        }
    }

    /**
     * GET /api/theories/{id}
     * Récupérer une théorie par son ID
     */
    public static function show(int $id): void
    {
        try {
            $theorie = Theorie::findById($id);

            if (!$theorie) {
                Response::error('Théorie non trouvée', 404);
                return;
            }

            Response::success(['theorie' => $theorie->toArray()], 200);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la récupération de la théorie', 500);
        }
    }

    /**
     * POST /api/theories
     * Créer une nouvelle théorie (admin only)
     */
    public static function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || empty($data['title']) || !isset($data['chapitre_id']) ||
                empty($data['content']) || !isset($data['order_index'])) {
                Response::error('Titre, chapitre_id, contenu et ordre requis', 400);
                return;
            }

            $theorie = new Theorie();
            $theorie->setChapitreId((int) $data['chapitre_id'])
                    ->setTitle($data['title'])
                    ->setContent($data['content'])
                    ->setOrderIndex((int) $data['order_index'])
                    ->setEstimatedTime($data['estimated_time'] ?? 10);

            if ($theorie->create()) {
                Response::success([
                    'message' => 'Théorie créée avec succès',
                    'theorie' => $theorie->toArray()
                ], 201);
            } else {
                Response::error('Erreur lors de la création de la théorie', 500);
            }
        } catch (\Exception $e) {
            Response::error('Erreur lors de la création de la théorie', 500);
        }
    }

    /**
     * PUT /api/theories/{id}
     * Mettre à jour une théorie (admin only)
     */
    public static function update(int $id): void
    {
        try {
            $theorie = Theorie::findById($id);

            if (!$theorie) {
                Response::error('Théorie non trouvée', 404);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                Response::error('Données invalides', 400);
                return;
            }

            if (isset($data['chapitre_id'])) {
                $theorie->setChapitreId((int) $data['chapitre_id']);
            }
            if (isset($data['title'])) {
                $theorie->setTitle($data['title']);
            }
            if (isset($data['content'])) {
                $theorie->setContent($data['content']);
            }
            if (isset($data['order_index'])) {
                $theorie->setOrderIndex((int) $data['order_index']);
            }
            if (isset($data['estimated_time'])) {
                $theorie->setEstimatedTime((int) $data['estimated_time']);
            }

            if ($theorie->update()) {
                Response::success([
                    'message' => 'Théorie mise à jour avec succès',
                    'theorie' => $theorie->toArray()
                ], 200);
            } else {
                Response::error('Erreur lors de la mise à jour de la théorie', 500);
            }
        } catch (\Exception $e) {
            Response::error('Erreur lors de la mise à jour de la théorie', 500);
        }
    }

    /**
     * POST /api/theories/{id}/read
     * Marquer une théorie comme lue par l'utilisateur courant, recalculer sa
     * progression sur le chapitre et vérifier l'attribution de nouveaux badges.
     */
    public static function markAsRead(int $id): void
    {
        $user = AuthService::getCurrentUser();

        if (!$user) {
            Response::error('Non authentifié', 401);
            return;
        }

        if (!Theorie::findById($id)) {
            Response::error('Théorie non trouvée', 404);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $timeSpent = isset($data['time_spent']) ? (int) $data['time_spent'] : 0;

        ProgressionService::markTheorieAsRead($user->getId(), $id, $timeSpent);
        GamificationService::updateStreak($user->getId());
        GamificationService::checkAndAwardBadges($user->getId());

        Response::success([], 200);
    }

    /**
     * DELETE /api/theories/{id}
     * Supprimer une théorie (admin only)
     */
    public static function destroy(int $id): void
    {
        try {
            $theorie = Theorie::findById($id);

            if (!$theorie) {
                Response::error('Théorie non trouvée', 404);
                return;
            }

            if ($theorie->delete()) {
                Response::success(['message' => 'Théorie supprimée avec succès'], 200);
            } else {
                Response::error('Erreur lors de la suppression de la théorie', 500);
            }
        } catch (\Exception $e) {
            Response::error('Erreur lors de la suppression de la théorie', 500);
        }
    }
}
