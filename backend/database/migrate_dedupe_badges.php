<?php

/**
 * Dédoublonne la table `badges` et pose la contrainte d'unicité qui empêche la
 * réapparition du problème.
 *
 * Pourquoi ce script existe : `docker/backend-entrypoint.sh` joue migrate.php à
 * chaque démarrage du conteneur backend, et le seed des badges était un INSERT
 * nu sur une table sans clé unique. Aucune erreur « Duplicate entry » n'était
 * donc jamais levée, et migrate.php — qui ne fait qu'ignorer cette erreur —
 * réinsérait les 19 badges à chaque fois. Constaté en production le 17/08/2026 :
 * 95 lignes après cinq déploiements, et des titres obtenus en double dans la
 * salle des trophées.
 *
 * schema.sql est désormais correct (UNIQUE sur `name` + INSERT IGNORE), ce qui
 * suffit pour toute installation neuve. Ce script est là pour les bases déjà
 * polluées, qu'un CREATE TABLE IF NOT EXISTS ne corrigera jamais.
 *
 * L'ordre des opérations n'est pas négociable :
 *   1. user_badges est repointé vers le badge survivant AVANT toute suppression.
 *      badges.id est référencé par user_badges avec ON DELETE CASCADE :
 *      supprimer les doublons d'abord effacerait des titres réellement acquis.
 *   2. Les lignes user_badges qui feraient doublon après repointage sont
 *      retirées, sinon le repointage violerait UNIQUE (user_id, badge_id).
 *   3. Les badges en double sont supprimés.
 *   4. La contrainte d'unicité est posée — elle échouerait s'il restait des
 *      doublons, d'où sa position en dernier.
 *
 * Idempotent : relancé sur une base saine, il ne trouve aucun doublon et
 * constate que la contrainte est déjà là.
 *
 * Usage : php database/migrate_dedupe_badges.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

// safeLoad() (et non load()) : ce script est lancé dans le conteneur backend,
// où il n'y a pas de fichier .env — la configuration arrive par l'environnement.
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$pdo = Database::getConnection();

echo "=== Dédoublonnage de la table badges ===\n\n";

$total = (int) $pdo->query("SELECT COUNT(*) FROM badges")->fetchColumn();
$distincts = (int) $pdo->query("SELECT COUNT(DISTINCT name) FROM badges")->fetchColumn();

echo "Badges en base : $total\n";
echo "Noms distincts : $distincts\n\n";

if ($total > $distincts) {
    // 1. Retirer les user_badges qui entreraient en collision après repointage :
    //    l'utilisateur possède déjà le badge survivant, sa copie ne sert à rien.
    $supprimesCollision = $pdo->exec("
        DELETE ub FROM user_badges ub
        JOIN badges b ON b.id = ub.badge_id
        JOIN (SELECT name, MIN(id) AS keep_id FROM badges GROUP BY name) c
             ON c.name = b.name
        JOIN user_badges garde
             ON garde.user_id = ub.user_id AND garde.badge_id = c.keep_id
        WHERE ub.badge_id <> c.keep_id
    ");
    echo "Titres en double retirés de user_badges : $supprimesCollision\n";

    // 2. Repointer ce qui reste vers le badge survivant. Ce cas se produit quand
    //    l'utilisateur a obtenu un doublon SANS avoir l'original : le titre est
    //    légitime, seule sa cible est à corriger.
    $repointes = $pdo->exec("
        UPDATE user_badges ub
        JOIN badges b ON b.id = ub.badge_id
        JOIN (SELECT name, MIN(id) AS keep_id FROM badges GROUP BY name) c
             ON c.name = b.name
        SET ub.badge_id = c.keep_id
        WHERE ub.badge_id <> c.keep_id
    ");
    echo "Titres repointés vers le badge d'origine : $repointes\n";

    // 3. Supprimer les doublons, désormais sans aucun user_badges attaché.
    $supprimes = $pdo->exec("
        DELETE b FROM badges b
        JOIN (SELECT name, MIN(id) AS keep_id FROM badges GROUP BY name) c
             ON c.name = b.name
        WHERE b.id <> c.keep_id
    ");
    echo "Badges en double supprimés : $supprimes\n\n";
} else {
    echo "Aucun doublon à supprimer.\n\n";
}

// 4. Poser la contrainte d'unicité si elle manque.
$contrainte = $pdo->query("
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'badges'
      AND INDEX_NAME = 'uq_badges_name'
")->fetchColumn();

if ((int) $contrainte > 0) {
    echo "La contrainte uq_badges_name existe déjà.\n";
} else {
    $pdo->exec("ALTER TABLE badges ADD UNIQUE KEY uq_badges_name (name)");
    echo "✓ Contrainte uq_badges_name ajoutée.\n";
}

$final = (int) $pdo->query("SELECT COUNT(*) FROM badges")->fetchColumn();
echo "\n=== Terminé : $final badges ===\n";
