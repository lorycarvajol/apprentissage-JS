<?php

/**
 * Ajoute les 6 exercices du Module 5 ("Le DOM et les événements"), créé en
 * théorie seule par seed_module5.php lors d'une session précédente faute de
 * support DOM dans le sandbox à l'époque. Ce script complète les 3 chapitres
 * déjà en base (ids attendus : 13, 14, 15 pour 5.1/5.2/5.3) sans recréer le
 * module ni les théories.
 *
 * Rendu possible par l'ajout de runJsWithDom() dans jsSandbox.js : une
 * iframe sandboxée (sandbox="allow-scripts allow-forms", pas de
 * allow-same-origin) donne accès à un vrai DOM -- querySelector,
 * addEventListener, createElement... -- via exercices.html_fixture (le HTML
 * de départ inséré dans l'iframe avant exécution). Voir ROADMAP.md pour le
 * raisonnement complet (pourquoi une vraie iframe plutôt qu'un DOM simulé).
 *
 * Comme il n'y a pas de vrai utilisateur pour cliquer/soumettre pendant la
 * correction automatique, chaque exercice simule lui-même l'interaction
 * (element.click(), form.requestSubmit()) directement dans le code -- déjà
 * fourni dans starter_code, l'apprenant n'écrit que la logique métier
 * (écouteur, validation...). Résultat vérifié via une iframe réelle
 * (Chrome headless piloté par CDP) avant d'écrire ce script, pas deviné.
 *
 * Usage : php database/seed_module5_exercices.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

// safeLoad() (et non load()) : ces scripts sont aussi lancés dans le conteneur
// backend, où il n'y a pas de fichier .env — la configuration arrive par
// l'environnement (env_file + clear_env=no). load() lèverait une exception avant
// la première requête. Même choix que migrate.php et public/index.php.
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$pdo = Database::getConnection();

echo "=== Ajout des exercices du Module 5 ===\n\n";

// ================================================================
// Garde-fou : ne pas rejouer le script si les exercices existent déjà
// ================================================================
$check = $pdo->prepare("
    SELECT COUNT(*) FROM exercices
    WHERE chapitre_id IN (
        SELECT id FROM chapitres WHERE module_id = (
            SELECT id FROM modules WHERE title = 'Le DOM et les événements'
        )
    )
");
$check->execute();
if ((int) $check->fetchColumn() > 0) {
    echo "Des exercices existent déjà pour le module 'Le DOM et les événements', arrêt du script.\n";
    exit(0);
}

// ================================================================
// Retrouver les chapitres du module 5 (déjà en base)
// ================================================================
$stmt = $pdo->prepare("
    SELECT chapitres.order_index, chapitres.id FROM chapitres
    INNER JOIN modules ON modules.id = chapitres.module_id
    WHERE modules.title = 'Le DOM et les événements'
    ORDER BY chapitres.order_index ASC
");
$stmt->execute();
$chapitreRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // order_index => id

if (count($chapitreRows) !== 3) {
    echo "Attendu 3 chapitres pour le module 5, trouvé " . count($chapitreRows) . ". Arrêt.\n";
    exit(1);
}

$chap1 = (int) $chapitreRows[1]; // 5.1 Sélectionner et manipuler le DOM
$chap2 = (int) $chapitreRows[2]; // 5.2 Événements et interactivité
$chap3 = (int) $chapitreRows[3]; // 5.3 Formulaires et validation côté client

echo "✓ Chapitres retrouvés (ids: $chap1, $chap2, $chap3)\n";

// ================================================================
// EXERCICES
// ================================================================
$insertExercice = $pdo->prepare("
    INSERT INTO exercices
    (chapitre_id, title, description, instructions, starter_code, html_fixture, solution_code, expected_output, difficulty, points, order_index)
    VALUES
    (:chapitre_id, :title, :description, :instructions, :starter_code, :html_fixture, :solution_code, :expected_output, :difficulty, :points, :order_index)
");

// --- Chapitre 5.1 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Modifier un titre',
    'description' => "Modifie le texte et la couleur d'un titre existant dans la page.",
    'instructions' => "Sélectionne l'élément #titre, remplace son texte par \"Bienvenue !\" et sa couleur par \"green\", puis affiche son texte et sa couleur. Le programme doit afficher exactement :\nBienvenue !\ngreen",
    'starter_code' => <<<'JS'
// TODO : sélectionne #titre, remplace son texte par "Bienvenue !" et sa
// couleur (style.color) par "green", puis affiche titre.textContent et
// titre.style.color
JS,
    'html_fixture' => '<h1 id="titre">Titre par défaut</h1>',
    'solution_code' => <<<'JS'
const titre = document.querySelector("#titre");
titre.textContent = "Bienvenue !";
titre.style.color = "green";
console.log(titre.textContent);
console.log(titre.style.color);
JS,
    'expected_output' => "Bienvenue !\ngreen",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 5.1 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Générer une liste depuis un tableau',
    'description' => "Génère dynamiquement une liste HTML à partir d'un tableau de données JavaScript.",
    'instructions' => "À partir du tableau produits = [\"Clavier\", \"Souris\", \"Écran\"], crée un élément <li> par produit (createElement + textContent) et ajoute-le à #liste-produits (appendChild). Affiche ensuite le nombre d'éléments de la liste, puis leurs textes joints par \", \". Le programme doit afficher exactement :\n3\nClavier, Souris, Écran",
    'starter_code' => <<<'JS'
const produits = ["Clavier", "Souris", "Écran"];
const liste = document.querySelector("#liste-produits");

// TODO : pour chaque produit, crée un <li> (createElement) avec son nom
// (textContent) et ajoute-le à liste (appendChild)

console.log(liste.children.length);
console.log(Array.from(liste.children).map(li => li.textContent).join(", "));
JS,
    'html_fixture' => '<ul id="liste-produits"></ul>',
    'solution_code' => <<<'JS'
const produits = ["Clavier", "Souris", "Écran"];
const liste = document.querySelector("#liste-produits");

for (const nom of produits) {
  const item = document.createElement("li");
  item.textContent = nom;
  liste.appendChild(item);
}

console.log(liste.children.length);
console.log(Array.from(liste.children).map(li => li.textContent).join(", "));
JS,
    'expected_output' => "3\nClavier, Souris, Écran",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 5.2 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Compteur au clic',
    'description' => "Un bouton qui incrémente un compteur affiché à l'écran à chaque clic.",
    'instructions' => "Ajoute un écouteur de clic sur #bouton qui incrémente un compteur interne et met à jour le texte de #compteur à chaque clic. Le bouton est cliqué 3 fois automatiquement pour vérifier ton code (déjà écrit dans starter_code). Le programme doit afficher exactement :\n3",
    'starter_code' => <<<'JS'
const bouton = document.querySelector("#bouton");
const affichage = document.querySelector("#compteur");

// TODO : ajoute un écouteur de clic sur bouton qui incrémente un compteur
// et met à jour affichage.textContent à chaque clic

bouton.click();
bouton.click();
bouton.click();
console.log(affichage.textContent);
JS,
    'html_fixture' => '<button id="bouton">+1</button><span id="compteur">0</span>',
    'solution_code' => <<<'JS'
const bouton = document.querySelector("#bouton");
const affichage = document.querySelector("#compteur");

let compte = 0;
bouton.addEventListener("click", () => {
  compte++;
  affichage.textContent = compte;
});

bouton.click();
bouton.click();
bouton.click();
console.log(affichage.textContent);
JS,
    'expected_output' => '3',
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 5.2 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Délégation sur une liste de tâches',
    'description' => "Un seul écouteur sur le conteneur gère le clic sur n'importe quel élément enfant.",
    'instructions' => "Ajoute UN SEUL écouteur de clic sur #taches (jamais sur chaque <li> individuellement) qui affiche le texte de la tâche cliquée via event.target.textContent. Chaque <li> est cliqué automatiquement pour vérifier ton code (déjà écrit dans starter_code). Le programme doit afficher exactement :\nAcheter du pain\nRépondre à l'email\nSortir les poubelles",
    'starter_code' => <<<'JS'
const conteneur = document.querySelector("#taches");

// TODO : ajoute UN SEUL écouteur de clic sur conteneur (pas sur chaque
// <li>) qui affiche event.target.textContent

document.querySelectorAll("#taches li").forEach((li) => li.click());
JS,
    'html_fixture' => '<ul id="taches"><li>Acheter du pain</li><li>Répondre à l\'email</li><li>Sortir les poubelles</li></ul>',
    'solution_code' => <<<'JS'
const conteneur = document.querySelector("#taches");

conteneur.addEventListener("click", (event) => {
  console.log(event.target.textContent);
});

document.querySelectorAll("#taches li").forEach((li) => li.click());
JS,
    'expected_output' => "Acheter du pain\nRépondre à l'email\nSortir les poubelles",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 5.3 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => "Empêcher l'envoi si l'email est vide",
    'description' => "Un formulaire d'inscription simple qui empêche l'envoi si le champ email est vide.",
    'instructions' => "Ajoute un écouteur sur l'événement submit de #inscription qui empêche l'envoi (preventDefault) et affiche \"Formulaire bloqué : email requis\" si #email est vide, sinon affiche \"Formulaire envoyé\". Le formulaire est soumis deux fois automatiquement (vide, puis rempli) pour vérifier ton code. Le programme doit afficher exactement :\nFormulaire bloqué : email requis\nFormulaire envoyé",
    'starter_code' => <<<'JS'
const form = document.querySelector("#inscription");
const email = document.querySelector("#email");

// TODO : à l'envoi (submit), empêche l'envoi (preventDefault) et affiche
// "Formulaire bloqué : email requis" si email est vide (trim), sinon
// affiche "Formulaire envoyé"

form.requestSubmit();
email.value = "ana@exemple.com";
form.requestSubmit();
JS,
    'html_fixture' => '<form id="inscription"><input id="email" value=""></form>',
    'solution_code' => <<<'JS'
const form = document.querySelector("#inscription");
const email = document.querySelector("#email");

form.addEventListener("submit", (event) => {
  event.preventDefault();
  if (email.value.trim() === "") {
    console.log("Formulaire bloqué : email requis");
  } else {
    console.log("Formulaire envoyé");
  }
});

form.requestSubmit();
email.value = "ana@exemple.com";
form.requestSubmit();
JS,
    'expected_output' => "Formulaire bloqué : email requis\nFormulaire envoyé",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 5.3 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Validation multi-règles',
    'description' => "Un formulaire avec plusieurs règles de validation et des messages d'erreur ciblés.",
    'instructions' => "Ajoute un écouteur submit sur #creation qui empêche l'envoi (preventDefault) et affiche : \"Mot de passe trop court (8 caractères minimum)\" si #motdepasse fait moins de 8 caractères, sinon \"Les mots de passe ne correspondent pas\" si #motdepasse et #confirmation diffèrent, sinon \"Formulaire valide, envoi en cours...\". Le formulaire est soumis 3 fois automatiquement avec des valeurs différentes pour vérifier ton code. Le programme doit afficher exactement :\nMot de passe trop court (8 caractères minimum)\nLes mots de passe ne correspondent pas\nFormulaire valide, envoi en cours...",
    'starter_code' => <<<'JS'
const form = document.querySelector("#creation");
const motdepasse = document.querySelector("#motdepasse");
const confirmation = document.querySelector("#confirmation");

// TODO : à l'envoi (submit), empêche l'envoi (preventDefault) et affiche le
// bon message selon les règles (voir instructions)

motdepasse.value = "abc";
confirmation.value = "abc";
form.requestSubmit();

motdepasse.value = "motdepasse123";
confirmation.value = "autrechose";
form.requestSubmit();

motdepasse.value = "motdepasse123";
confirmation.value = "motdepasse123";
form.requestSubmit();
JS,
    'html_fixture' => '<form id="creation"><input id="motdepasse" value=""><input id="confirmation" value=""></form>',
    'solution_code' => <<<'JS'
const form = document.querySelector("#creation");
const motdepasse = document.querySelector("#motdepasse");
const confirmation = document.querySelector("#confirmation");

form.addEventListener("submit", (event) => {
  event.preventDefault();
  if (motdepasse.value.length < 8) {
    console.log("Mot de passe trop court (8 caractères minimum)");
  } else if (motdepasse.value !== confirmation.value) {
    console.log("Les mots de passe ne correspondent pas");
  } else {
    console.log("Formulaire valide, envoi en cours...");
  }
});

motdepasse.value = "abc";
confirmation.value = "abc";
form.requestSubmit();

motdepasse.value = "motdepasse123";
confirmation.value = "autrechose";
form.requestSubmit();

motdepasse.value = "motdepasse123";
confirmation.value = "motdepasse123";
form.requestSubmit();
JS,
    'expected_output' => "Mot de passe trop court (8 caractères minimum)\nLes mots de passe ne correspondent pas\nFormulaire valide, envoi en cours...",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

echo "✓ 6 exercices créés\n";

echo "\n=== Exercices du Module 5 ajoutés avec succès ===\n";
