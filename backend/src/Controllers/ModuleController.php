<?php

namespace App\Controllers;

use App\Models\Module;
use App\Helpers\Response;

class ModuleController
{
    /**
     * GET /api/modules
     * Récupérer tous les modules
     */
    public static function index(): void
    {
        try {
            $publishedOnly = $_GET['published'] ?? true;
            $publishedOnly = filter_var($publishedOnly, FILTER_VALIDATE_BOOLEAN);

            $modules = Module::findAll($publishedOnly);
            $modulesArray = array_map(fn($module) => $module->toArray(), $modules);

            Response::success(['modules' => $modulesArray]);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la récupération des modules', 500);
        }
    }

    /**
     * GET /api/modules/{id}
     * Récupérer un module par son ID
     */
    public static function show(int $id): void
    {
        try {
            $module = Module::findById($id);

            if (!$module) {
                Response::error('Module non trouvé', 404);
                return;
            }

            Response::success(['module' => $module->toArray()]);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la récupération du module', 500);
        }
    }

    /**
     * GET /api/modules/{id}/chapitres
     * Récupérer un module avec ses chapitres
     */
    public static function showWithChapitres(int $id): void
    {
        try {
            $data = Module::findByIdWithChapitres($id);

            if (!$data) {
                Response::error('Module non trouvé', 404);
                return;
            }

            Response::success([
                'module' => $data['module']->toArray(),
                'chapitres' => array_map(fn($chapitre) => $chapitre->toArray(), $data['chapitres'])
            ]);
        } catch (\Exception $e) {
            Response::error('Erreur lors de la récupération du module', 500);
        }
    }

    /**
     * POST /api/modules
     * Créer un nouveau module (admin only)
     */
    public static function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || empty($data['title']) || !isset($data['order_index'])) {
                Response::error('Titre et ordre requis', 400);
                return;
            }

            $module = new Module();
            $module->setTitle($data['title'])
                   ->setDescription($data['description'] ?? null)
                   ->setOrderIndex((int) $data['order_index'])
                   ->setIsPublished($data['is_published'] ?? true);

            if ($module->create()) {
                Response::success([
                    'message' => 'Module créé avec succès',
                    'module' => $module->toArray()
                ], 201);
            } else {
                Response::error('Erreur lors de la création du module', 500);
            }
        } catch (\Exception $e) {
            Response::error('Erreur lors de la création du module', 500);
        }
    }

    /**
     * PUT /api/modules/{id}
     * Mettre à jour un module (admin only)
     */
    public static function update(int $id): void
    {
        try {
            $module = Module::findById($id);

            if (!$module) {
                Response::error('Module non trouvé', 404);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                Response::error('Données invalides', 400);
                return;
            }

            if (isset($data['title'])) {
                $module->setTitle($data['title']);
            }
            if (isset($data['description'])) {
                $module->setDescription($data['description']);
            }
            if (isset($data['order_index'])) {
                $module->setOrderIndex((int) $data['order_index']);
            }
            if (isset($data['is_published'])) {
                $module->setIsPublished((bool) $data['is_published']);
            }

            if ($module->update()) {
                Response::success([
                    'message' => 'Module mis à jour avec succès',
                    'module' => $module->toArray()
                ]);
            } else {
                Response::error('Erreur lors de la mise à jour du module', 500);
            }
        } catch (\Exception $e) {
            Response::error('Erreur lors de la mise à jour du module', 500);
        }
    }

    /**
     * DELETE /api/modules/{id}
     * Supprimer un module (admin only)
     */
    public static function destroy(int $id): void
    {
        try {
            $module = Module::findById($id);

            if (!$module) {
                Response::error('Module non trouvé', 404);
                return;
            }

            if ($module->delete()) {
                Response::success(['message' => 'Module supprimé avec succès']);
            } else {
                Response::error('Erreur lors de la suppression du module', 500);
            }
        } catch (\Exception $e) {
            Response::error('Erreur lors de la suppression du module', 500);
        }
    }
}
