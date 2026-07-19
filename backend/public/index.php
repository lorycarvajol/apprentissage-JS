<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Cors;
use App\Helpers\Router;
use App\Controllers\AuthController;
use App\Controllers\ModuleController;
use App\Controllers\ChapitreController;
use App\Controllers\TheorieController;
use App\Controllers\ExerciceController;
use App\Controllers\DashboardController;
use App\Controllers\GamificationController;
use App\Middleware\AuthMiddleware;
use Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Gérer CORS
Cors::handle();

// Définir le type de contenu JSON
header('Content-Type: application/json; charset=utf-8');

// Gestion des erreurs
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    error_log("Error [$errno]: $errstr in $errfile on line $errline");

    if ($_ENV['APP_DEBUG'] === 'true') {
        http_response_code(500);
        echo json_encode([
            'error' => 'Internal Server Error',
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Internal Server Error']);
    }
    exit;
});

// Créer le routeur
$router = new Router();

// ================================================================
// Routes d'authentification (publiques)
// ================================================================
$router->group('api/auth', function($router) {
    $router->post('register', [AuthController::class, 'register']);
    $router->post('login', [AuthController::class, 'login']);
    $router->post('logout', [AuthController::class, 'logout']);
    $router->post('refresh', [AuthController::class, 'refresh']);
    $router->get('me', [AuthController::class, 'me']);
    $router->get('me/export', [AuthController::class, 'exportData']);
    $router->delete('me', [AuthController::class, 'deleteAccount']);
    $router->post('forgot-password', [AuthController::class, 'forgotPassword']);
    $router->post('reset-password', [AuthController::class, 'resetPassword']);
    $router->post('verify-email', [AuthController::class, 'verifyEmail']);
    $router->post('resend-verification', [AuthController::class, 'resendVerification']);
    $router->post('check-password-strength', [AuthController::class, 'checkPasswordStrength']);
});

// ================================================================
// Routes du contenu pédagogique (modules, chapitres, théories)
// ================================================================

// Routes modules
$router->group('api/modules', function($router) {
    $router->get('', [ModuleController::class, 'index']);
    $router->get('{id}', [ModuleController::class, 'show']);
    $router->get('{id}/chapitres', [ModuleController::class, 'showWithChapitres']);
    $router->post('', function() {
        if (!AuthMiddleware::requireAdmin()) return;
        ModuleController::store();
    });
    $router->put('{id}', function($id) {
        if (!AuthMiddleware::requireAdmin()) return;
        ModuleController::update($id);
    });
    $router->delete('{id}', function($id) {
        if (!AuthMiddleware::requireAdmin()) return;
        ModuleController::destroy($id);
    });
});

// Routes chapitres
$router->group('api/chapitres', function($router) {
    $router->get('', [ChapitreController::class, 'index']);
    $router->get('{id}', [ChapitreController::class, 'show']);
    $router->get('{id}/theories', [ChapitreController::class, 'showWithTheories']);
    $router->get('{id}/exercices', [ChapitreController::class, 'showWithExercices']);
    $router->get('{id}/content', [ChapitreController::class, 'showWithContent']);
    $router->post('', function() {
        if (!AuthMiddleware::requireAdmin()) return;
        ChapitreController::store();
    });
    $router->put('{id}', function($id) {
        if (!AuthMiddleware::requireAdmin()) return;
        ChapitreController::update($id);
    });
    $router->delete('{id}', function($id) {
        if (!AuthMiddleware::requireAdmin()) return;
        ChapitreController::destroy($id);
    });
});

// Routes theories
$router->group('api/theories', function($router) {
    $router->get('', [TheorieController::class, 'index']);
    $router->get('{id}', [TheorieController::class, 'show']);
    $router->post('', function() {
        if (!AuthMiddleware::requireAdmin()) return;
        TheorieController::store();
    });
    $router->put('{id}', function($id) {
        if (!AuthMiddleware::requireAdmin()) return;
        TheorieController::update($id);
    });
    $router->delete('{id}', function($id) {
        if (!AuthMiddleware::requireAdmin()) return;
        TheorieController::destroy($id);
    });
    $router->post('{id}/read', [TheorieController::class, 'markAsRead']);
});

// Routes exercices
$router->group('api/exercices', function($router) {
    $router->get('', function() {
        if (!AuthMiddleware::requireAdmin()) return;
        ExerciceController::index();
    });
    $router->get('{id}', function($id) {
        if (!AuthMiddleware::requireAdmin()) return;
        ExerciceController::show($id);
    });
    $router->post('', function() {
        if (!AuthMiddleware::requireAdmin()) return;
        ExerciceController::store();
    });
    $router->put('{id}', function($id) {
        if (!AuthMiddleware::requireAdmin()) return;
        ExerciceController::update($id);
    });
    $router->delete('{id}', function($id) {
        if (!AuthMiddleware::requireAdmin()) return;
        ExerciceController::destroy($id);
    });
    $router->post('{id}/submit', [ExerciceController::class, 'submit']);
});

// Routes dashboard
$router->group('api/dashboard', function($router) {
    $router->get('stats', [DashboardController::class, 'stats']);
});

// Routes gamification (badges, streaks)
$router->group('api/gamification', function($router) {
    $router->get('summary', [GamificationController::class, 'summary']);
});

// ================================================================
// Route racine - API Info
// ================================================================
$router->get('', function() {
    echo json_encode([
        'name' => 'API Plateforme d\'apprentissage JavaScript',
        'version' => '1.0.0',
        'status' => 'online',
        'endpoints' => [
            'health' => 'GET /api/health',
            'auth' => [
                'register' => 'POST /api/auth/register',
                'login' => 'POST /api/auth/login',
                'logout' => 'POST /api/auth/logout',
                'me' => 'GET /api/auth/me',
                'refresh' => 'POST /api/auth/refresh',
                'forgot-password' => 'POST /api/auth/forgot-password',
                'reset-password' => 'POST /api/auth/reset-password',
                'verify-email' => 'POST /api/auth/verify-email',
                'resend-verification' => 'POST /api/auth/resend-verification'
            ],
            'modules' => [
                'list' => 'GET /api/modules',
                'show' => 'GET /api/modules/{id}',
                'with_chapitres' => 'GET /api/modules/{id}/chapitres'
            ],
            'chapitres' => [
                'list' => 'GET /api/chapitres',
                'show' => 'GET /api/chapitres/{id}',
                'with_theories' => 'GET /api/chapitres/{id}/theories',
                'with_exercices' => 'GET /api/chapitres/{id}/exercices',
                'with_content' => 'GET /api/chapitres/{id}/content'
            ],
            'theories' => [
                'list' => 'GET /api/theories',
                'show' => 'GET /api/theories/{id}'
            ],
            'documentation' => 'Voir API_DOCUMENTATION.md'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

// ================================================================
// Route de test
// ================================================================
$router->get('api/health', function() {
    echo json_encode([
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ]);
});

// ================================================================
// Routes protégées (exemples)
// ================================================================
$router->get('api/protected', function() {
    if (!AuthMiddleware::authenticate()) {
        return;
    }

    echo json_encode([
        'message' => 'Vous êtes authentifié !',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// ================================================================
// Dispatcher la requête
// ================================================================
try {
    $router->dispatch();
} catch (Exception $e) {
    error_log("Router exception: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : 'Une erreur est survenue'
    ]);
}
