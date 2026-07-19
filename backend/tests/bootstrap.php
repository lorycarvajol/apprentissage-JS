<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Mêmes variables d'environnement que l'application (JWT_SECRET, DB_*...) --
// les tests d'intégration tapent la vraie base apprentissage_js en lecture
// seule via l'API déjà démarrée (scripts/start-dev.ps1), pas de base dédiée.
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();
