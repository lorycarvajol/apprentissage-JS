<?php

namespace App\Controllers;

use App\Models\Chapitre;
use App\Models\Module;
use App\Helpers\Response;

class ChapitreController
{
    /**
     * GET /api/chapitres
     * Récupérer tous les chapitres
     */
    public static function index(): void
    {
        try {
            $publishedOnly = $_GET['published'] ?? true;
            $publishedOnly = filter_var($publishedOnly, FILTER_VALIDATE_BOOLEAN);

            $chapitres = Chapitre::findAll($publishedOnly);

            $chapitresArray = array_map(fn($chapitre) => $chapitre->toArray(), $chapitres);

            Response::success(['chapitres' => $chapitresArray], 200);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la récupération des chapitres', 500);
        }
    }

    /**
     * GET /api/chapitres/{id}
     * Récupérer un chapitre par son ID
     */
    public static function show(int $id): void
    {
        try {
            $chapitre = Chapitre::findById($id);

            if (!$chapitre) {
                Response::error('Chapitre non trouvé', 404);
                return;
            }

            Response::success(['chapitre' => $chapitre->toArray()], 200);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la récupération du chapitre', 500);
        }
    }

    /**
     * GET /api/chapitres/{id}/theories
     * Récupérer un chapitre avec ses théories
     */
    public static function showWithTheories(int $id): void
    {
        try {
            $data = Chapitre::findByIdWithContent($id);

            if (!$data) {
                Response::error('Chapitre non trouvé', 404);
                return;
            }

            Response::success([
                'chapitre' => $data['chapitre']->toArray(),
                'theories' => array_map(fn($theorie) => $theorie->toArray(), $data['theories'])
            ], 200);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la récupération du chapitre', 500);
        }
    }

    /**
     * GET /api/chapitres/{id}/exercices
     * Récupérer un chapitre avec ses exercices
     */
    public static function showWithExercices(int $id): void
    {
        try {
            $data = Chapitre::findByIdWithContent($id);

            if (!$data) {
                Response::error('Chapitre non trouvé', 404);
                return;
            }

            Response::success([
                'chapitre' => $data['chapitre']->toArray(),
                'exercices' => array_map(fn($exercice) => $exercice->toArray(false), $data['exercices'])
            ], 200);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la récupération du chapitre', 500);
        }
    }

    /**
     * GET /api/chapitres/{id}/content
     * Récupérer un chapitre avec tout son contenu (théories + exercices)
     */
    public static function showWithContent(int $id): void
    {
        try {
            $data = Chapitre::findByIdWithContent($id);

            if (!$data) {
                Response::error('Chapitre non trouvé', 404);
                return;
            }

            $module = Module::findById($data['chapitre']->getModuleId());

            Response::success([
                'chapitre' => $data['chapitre']->toArray(),
                'module' => $module?->toArray(),
                'theories' => array_map(fn($theorie) => $theorie->toArray(), $data['theories']),
                'exercices' => array_map(fn($exercice) => $exercice->toArray(false), $data['exercices'])
            ], 200);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la récupération du chapitre', 500);
        }
    }

    /**
     * POST /api/chapitres
     * Créer un nouveau chapitre (admin only)
     */
    public static function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || empty($data['title']) || !isset($data['module_id']) || !isset($data['order_index'])) {
                Response::error('Titre, module_id et ordre requis', 400);
                return;
            }

            $chapitre = new Chapitre();
            $chapitre->setModuleId((int) $data['module_id'])
                     ->setTitle($data['title'])
                     ->setDescription($data['description'] ?? null)
                     ->setOrderIndex((int) $data['order_index'])
                     ->setIsPublished($data['is_published'] ?? true);

            if ($chapitre->create()) {
                Response::success([
                    'message' => 'Chapitre créé avec succès',
                    'chapitre' => $chapitre->toArray()
                ], 201);
            } else {
                Response::error('Erreur lors de la création du chapitre', 500);
            }
        } catch (\Exception $e) {
            Response::error('Erreur lors de la création du chapitre', 500);
        }
    }

    /**
     * PUT /api/chapitres/{id}
     * Mettre à jour un chapitre (admin only)
     */
    public static function update(int $id): void
    {
        try {
            $chapitre = Chapitre::findById($id);

            if (!$chapitre) {
                Response::error('Chapitre non trouvé', 404);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                Response::error('Données invalides', 400);
                return;
            }

            if (isset($data['module_id'])) {
                $chapitre->setModuleId((int) $data['module_id']);
            }
            if (isset($data['title'])) {
                $chapitre->setTitle($data['title']);
            }
            if (isset($data['description'])) {
                $chapitre->setDescription($data['description']);
            }
            if (isset($data['order_index'])) {
                $chapitre->setOrderIndex((int) $data['order_index']);
            }
            if (isset($data['is_published'])) {
                $chapitre->setIsPublished((bool) $data['is_published']);
            }

            if ($chapitre->update()) {
                Response::success([
                    'message' => 'Chapitre mis à jour avec succès',
                    'chapitre' => $chapitre->toArray()
                ], 200);
            } else {
                Response::error('Erreur lors de la mise à jour du chapitre', 500);
            }
        } catch (\Exception $e) {
            Response::error('Erreur lors de la mise à jour du chapitre', 500);
        }
    }

    /**
     * DELETE /api/chapitres/{id}
     * Supprimer un chapitre (admin only)
     */
    public static function destroy(int $id): void
    {
        try {
            $chapitre = Chapitre::findById($id);

            if (!$chapitre) {
                Response::error('Chapitre non trouvé', 404);
                return;
            }

            if ($chapitre->delete()) {
                Response::success(['message' => 'Chapitre supprimé avec succès'], 200);
            } else {
                Response::error('Erreur lors de la suppression du chapitre', 500);
            }
        } catch (\Exception $e) {
            Response::error('Erreur lors de la suppression du chapitre', 500);
        }
    }
}
