<?php

/**
 * Ajoute la colonne exercices.html_fixture à la base existante (module 5,
 * exercices avec DOM réel via runJsWithDom() -- voir ROADMAP.md). Script à
 * usage unique sur la base de dev déjà seedée : schema.sql inclut déjà cette
 * colonne pour toute nouvelle installation via migrate.php, ce script sert
 * uniquement à mettre à jour une base existante sans repartir de zéro.
 *
 * Idempotent comme les scripts seed_moduleN.php : vérifie la présence de la
 * colonne avant de l'ajouter, sans danger à rejouer.
 *
 * Usage : php database/migrate_add_html_fixture.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$pdo = Database::getConnection();

echo "=== Ajout de exercices.html_fixture ===\n\n";

$check = $pdo->query("
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'exercices'
    AND COLUMN_NAME = 'html_fixture'
");

if ((int) $check->fetchColumn() > 0) {
    echo "La colonne html_fixture existe déjà, arrêt du script.\n";
    exit(0);
}

$pdo->exec("
    ALTER TABLE exercices
    ADD COLUMN html_fixture TEXT NULL COMMENT 'HTML de départ pour un exercice avec DOM réel (runJsWithDom), NULL sinon'
    AFTER starter_code
");

echo "✓ Colonne html_fixture ajoutée à exercices\n";
