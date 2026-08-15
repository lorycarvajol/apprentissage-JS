<?php

/**
 * Script de migration de base de données
 * Exécute schema.sql (schéma + badges) pour créer/mettre à jour la base.
 * Contrairement au projet PHP jumeau, il n'y a qu'un seul fichier à jouer :
 * pas de seed_data_simple.sql (aucun contenu de cours n'est pré-rempli ici).
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// safeLoad() : ce script est aussi lancé au démarrage du conteneur backend,
// où la configuration vient de Docker et où aucun fichier .env n'existe.
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

class Color {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const RESET = "\033[0m";
}

echo Color::BLUE . "=================================================\n" . Color::RESET;
echo Color::BLUE . "   Migration de la base de données\n" . Color::RESET;
echo Color::BLUE . "=================================================\n\n" . Color::RESET;

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'apprentissage_js';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    echo Color::YELLOW . "[1/2] Connexion au serveur MySQL...\n" . Color::RESET;
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo Color::GREEN . "✓ Connexion établie\n\n" . Color::RESET;

    echo Color::YELLOW . "[2/2] Création du schéma de base de données...\n" . Color::RESET;
    $schemaFile = __DIR__ . '/schema.sql';

    if (!file_exists($schemaFile)) {
        throw new Exception("Fichier schema.sql non trouvé");
    }

    // Créée explicitement ici plutôt que de compter sur le CREATE DATABASE de
    // schema.sql (voir juste en dessous pourquoi ce découpage ne peut pas lui
    // faire confiance).
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $schema = file_get_contents($schemaFile);

    // Chaque CREATE TABLE de schema.sql est précédé d'un bloc de commentaires
    // "-- Table : X" sur les lignes juste au-dessus, sans point-virgule entre
    // les deux : un découpage naïf par ';' produirait donc des chunks qui
    // commencent par "--", et un simple ^--  suffirait à classer tout le
    // chunk (commentaire ET instruction SQL qui suit) comme un commentaire
    // pur, en le supprimant silencieusement — la table ne serait jamais
    // créée, sans qu'aucune erreur ne soit levée. On retire donc chaque
    // ligne de commentaire du fichier avant de découper par ';'.
    $schemaWithoutComments = preg_replace('/^--.*$/m', '', $schema);

    $statements = array_filter(
        array_map('trim', explode(';', $schemaWithoutComments)),
        fn($stmt) => $stmt !== ''
    );

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false
                    && strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo Color::RED . "Erreur: " . $e->getMessage() . "\n" . Color::RESET;
                }
            }
        }
    }

    echo Color::GREEN . "✓ Schéma créé avec succès\n" . Color::RESET;
    echo "  - Database: $dbname\n";
    echo "  - Tables: users, modules, chapitres, theories, exercices, progression,\n";
    echo "    theorie_lue, exercice_soumis, badges, user_badges, points, streaks,\n";
    echo "    quiz, questions, login_history, refresh_tokens\n\n";

    $pdo->exec("USE $dbname");

    $stats = [
        'modules' => $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn(),
        'chapitres' => $pdo->query("SELECT COUNT(*) FROM chapitres")->fetchColumn(),
        'theories' => $pdo->query("SELECT COUNT(*) FROM theories")->fetchColumn(),
        'exercices' => $pdo->query("SELECT COUNT(*) FROM exercices")->fetchColumn(),
        'badges' => $pdo->query("SELECT COUNT(*) FROM badges")->fetchColumn(),
    ];

    echo Color::BLUE . "=================================================\n" . Color::RESET;
    echo Color::GREEN . "✓ Migration terminée avec succès !\n\n" . Color::RESET;
    echo "Statistiques :\n";
    echo "  - Modules: {$stats['modules']} (vide, à créer via /admin)\n";
    echo "  - Chapitres: {$stats['chapitres']}\n";
    echo "  - Théories: {$stats['theories']}\n";
    echo "  - Exercices: {$stats['exercices']}\n";
    echo "  - Badges: {$stats['badges']}\n";
    echo Color::BLUE . "=================================================\n" . Color::RESET;

} catch (PDOException $e) {
    echo Color::RED . "\n✗ Erreur de connexion à la base de données:\n" . Color::RESET;
    echo Color::RED . $e->getMessage() . "\n\n" . Color::RESET;
    echo "Vérifiez vos paramètres dans le fichier .env :\n";
    echo "  - DB_HOST: $host\n";
    echo "  - DB_PORT: $port\n";
    echo "  - DB_USER: $username\n";
    echo "  - DB_NAME: $dbname\n";
    exit(1);
} catch (Exception $e) {
    echo Color::RED . "\n✗ Erreur: " . $e->getMessage() . "\n" . Color::RESET;
    exit(1);
}
