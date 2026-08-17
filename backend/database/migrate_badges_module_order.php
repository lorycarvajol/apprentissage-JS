<?php

/**
 * Aligne les badges déjà en base sur schema.sql, pour ce que `INSERT IGNORE` ne
 * peut pas faire : mettre à jour une ligne existante.
 *
 * Cas traité : « Débutant » visait `{"module_id": 1}`. GamificationService
 * interprétait cette valeur comme un identifiant technique, et les modules du
 * curriculum ont reçu les id 2 à 10 — l'id 1 ayant été consommé puis libéré par
 * un module de test créé lors de la mise en ligne. Le badge était devenu
 * inobtenable sans que rien ne le signale : la condition retourne false quand le
 * module n'a aucun chapitre, ce qui est aussi le cas quand il n'existe plus.
 *
 * La condition cible désormais `module_order`, c'est-à-dire le rang affiché du
 * module (« Module 1 »), stable quel que soit l'ordre de création du contenu.
 * schema.sql est corrigé pour toute installation neuve ; ce script est là pour
 * les bases existantes.
 *
 * Les six badges ajoutés en même temps (Le Novice, Main sûre, Compagnon, Bourse
 * garnie, Copiste, Veilleur) n'ont besoin de rien : l'INSERT IGNORE de
 * schema.sql les insère au prochain démarrage du conteneur, les 19 existants
 * étant ignorés sur leur nom.
 *
 * Idempotent : relancé, il ne trouve plus rien à corriger.
 *
 * Usage : php database/migrate_badges_module_order.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

// safeLoad() : pas de fichier .env dans le conteneur, la configuration arrive
// par l'environnement.
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$pdo = Database::getConnection();

echo "=== Alignement des conditions de badges ===\n\n";

$stmt = $pdo->prepare("SELECT id, condition_value FROM badges WHERE name = :name");
$stmt->execute(['name' => 'Débutant']);
$badge = $stmt->fetch();

if (!$badge) {
    echo "Badge « Débutant » absent — rien à faire.\n";
} elseif (str_contains((string) $badge['condition_value'], 'module_order')) {
    echo "Badge « Débutant » déjà aligné sur module_order.\n";
} else {
    $update = $pdo->prepare(
        "UPDATE badges SET condition_value = :valeur WHERE id = :id"
    );
    $update->execute([
        'valeur' => '{"module_order": 1}',
        'id' => $badge['id'],
    ]);
    echo "✓ « Débutant » : {$badge['condition_value']} → {\"module_order\": 1}\n";
}

$total = (int) $pdo->query("SELECT COUNT(*) FROM badges")->fetchColumn();
echo "\n=== Terminé : $total badges en base ===\n";
