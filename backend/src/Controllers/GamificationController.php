<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\GamificationService;
use App\Helpers\Response;

class GamificationController
{
    /**
     * GET /api/gamification/summary
     * Badges (obtenus/verrouillés) et streak de l'utilisateur courant.
     */
    public static function summary(): void
    {
        $user = AuthService::getCurrentUser();

        if (!$user) {
            Response::error('Non authentifié', 401);
            return;
        }

        Response::success(GamificationService::getUserBadgesSummary($user->getId()), 200);
    }
}
