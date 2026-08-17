<?php

/**
 * Crée le Module 4 ("Chaînes, dates et données textuelles") du curriculum
 * JavaScript — voir ROADMAP.md à la racine du dépôt. Suppose que les
 * Modules 1 à 3 ont déjà été créés et occupent order_index=1 à 3.
 *
 * expected_output de chaque exercice a été calculé hors-ligne en rejouant
 * solution_code à travers une réplique exacte de la logique de capture de
 * frontend/src/utils/jsSandbox.js (stringifyArg + console.log + join('\n')).
 *
 * Les deux exercices du chapitre 4.3 utilisent des dates fixes en UTC
 * (Date.UTC + Intl.DateTimeFormat avec timeZone: "UTC") plutôt que la date
 * du jour ou l'heure locale : le Worker du sandbox tourne dans le fuseau
 * horaire du navigateur de chaque apprenant, donc "aujourd'hui" ou un
 * formatage sans fuseau explicite produirait une sortie différente d'un
 * apprenant à l'autre — incompatible avec une comparaison stricte à
 * expected_output.
 *
 * Usage : php database/seed_module4.php
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

echo "=== Création du Module 4 : Chaînes, dates et données textuelles ===\n\n";

// ================================================================
// Garde-fou : ne pas rejouer le script si le module existe déjà
// ================================================================
$check = $pdo->prepare("SELECT id FROM modules WHERE title = :title");
$check->execute(['title' => 'Chaînes, dates et données textuelles']);
if ($check->fetch()) {
    echo "Le module 'Chaînes, dates et données textuelles' existe déjà, arrêt du script.\n";
    exit(0);
}

// ================================================================
// MODULE 4
// ================================================================
$insertModule = $pdo->prepare(
    "INSERT INTO modules (title, description, order_index, is_published)
    VALUES (:title, :description, 4, 1)"
);
$insertModule->execute([
    'title' => 'Chaînes, dates et données textuelles',
    'description' => "Quatrième module du curriculum JavaScript : méthodes de chaînes de caractères, expressions régulières de base, et manipulation des dates et de leur formatage (voir ROADMAP.md).",
]);
$moduleId = (int) $pdo->lastInsertId();
echo "✓ Module créé (id=$moduleId, order_index=4)\n";

// ================================================================
// CHAPITRES
// ================================================================
$insertChapitre = $pdo->prepare(
    "INSERT INTO chapitres (module_id, title, description, order_index, is_published)
    VALUES (:module_id, :title, :description, :order_index, 1)"
);

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Méthodes de chaînes de caractères',
    'description' => 'Template literals, slice/substring, split/join, trim, includes/startsWith/endsWith, et transformation de casse.',
    'order_index' => 1,
]);
$chap1 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Expressions régulières de base',
    'description' => "Syntaxe minimale (littéraux, classes de caractères, quantificateurs), test()/match(), et cas d'usage réalistes (validation, extraction).",
    'order_index' => 2,
]);
$chap2 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Dates et formats',
    'description' => "L'objet Date, création et lecture, calculs de durée, et formatage avec Intl.DateTimeFormat.",
    'order_index' => 3,
]);
$chap3 = (int) $pdo->lastInsertId();

echo "✓ 3 chapitres créés (ids: $chap1, $chap2, $chap3)\n";

// ================================================================
// THEORIES
// ================================================================
$insertTheory = $pdo->prepare(
    "INSERT INTO theories (chapitre_id, title, content, order_index, estimated_time)
    VALUES (:chapitre_id, :title, :content, :order_index, :estimated_time)"
);

// --- Chapitre 4.1 ---
$theory1 = <<<'HTML'
<p>Le texte (les chaînes de caractères, <code>string</code>) est omniprésent : noms, messages, titres, URLs... JavaScript fournit un ensemble de méthodes pour le construire, l'inspecter et le transformer sans réinventer la roue.</p>

<h2>Template literals</h2>

<p>Les <strong>template literals</strong> (guillemets inversés <code>`...`</code>) permettent d'insérer des expressions directement dans une chaîne avec <code>${...}</code>, et de répartir du texte sur plusieurs lignes sans caractère d'échappement :</p>

<pre><code>const prenom = "Ana";
const visites = 3;

const message = `Bienvenue ${prenom} !
Vous avez effectué ${visites} visite${visites > 1 ? "s" : ""}.`;</code></pre>

<p>C'est la façon la plus lisible de construire une chaîne à partir de plusieurs valeurs — préférable à la concaténation avec <code>+</code>.</p>

<h2>slice et substring : extraire une portion</h2>

<pre><code>const mot = "JavaScript";

mot.slice(0, 4);   // "Java"  — de l'indice 0 (inclus) à 4 (exclu)
mot.slice(4);       // "Script" — du 4 jusqu'à la fin
mot.slice(-6);      // "Script" — les 6 derniers caractères</code></pre>

<figure class="theory-image size-medium align-center">
  <img src="/images/module4-chap1/regle-graduee-slice.svg" alt="Règle graduée au-dessus de la chaîne JavaScript montrant les indices de chaque caractère, utilisés par slice() pour extraire une portion" />
  <figcaption>Figure 1 : slice() se repère aux indices des caractères, comme une règle graduée</figcaption>
</figure>

<h2>split et join : chaîne ↔ tableau</h2>

<pre><code>"pomme,banane,kiwi".split(",");     // ["pomme", "banane", "kiwi"]
["pomme", "banane", "kiwi"].join(" - "); // "pomme - banane - kiwi"</code></pre>

<h2>trim : nettoyer les espaces</h2>

<pre><code>"   bonjour   ".trim(); // "bonjour" — retire les espaces au début et à la fin</code></pre>

<h2>includes, startsWith, endsWith</h2>

<pre><code>const url = "https://exemple.fr/produits";

url.includes("produits");   // true — contient cette sous-chaîne, n'importe où
url.startsWith("https://"); // true — commence par
url.endsWith(".fr/produits"); // true — se termine par</code></pre>

<h2>Transformation de casse</h2>

<pre><code>"Bonjour".toUpperCase(); // "BONJOUR"
"Bonjour".toLowerCase(); // "bonjour"</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module4-chap1/avant-apres-slugify.svg" alt="Schéma avant/après montrant un titre transformé étape par étape en slug : accents retirés, minuscules, espaces remplacés par des tirets" />
  <figcaption>Figure 2 : combiner plusieurs méthodes de chaîne pour transformer un titre en slug d'URL</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li>Les template literals (<code>`...${...}`</code>) sont la façon la plus lisible de construire une chaîne</li>
  <li><code>slice</code> extrait une portion par indices, <code>split</code>/<code>join</code> convertissent entre chaîne et tableau</li>
  <li><code>trim</code> nettoie les espaces superflus en début/fin de chaîne</li>
  <li><code>includes</code>/<code>startsWith</code>/<code>endsWith</code> testent la présence d'une sous-chaîne sans regex</li>
  <li>Ces méthodes se combinent naturellement pour des transformations plus complexes (ex. un slug d'URL)</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap1,
    'title' => 'Méthodes de chaînes de caractères',
    'content' => $theory1,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 4.2 ---
$theory2 = <<<'HTML'
<p>Une <strong>expression régulière</strong> (regex) décrit un <strong>motif</strong> à rechercher dans du texte — un format d'email, un numéro de téléphone, un mot répété... Sa syntaxe est dense, mais un petit nombre de briques couvre déjà la majorité des besoins réels.</p>

<figure class="theory-image size-medium align-center">
  <img src="/images/module4-chap2/loupe-motif.svg" alt="Loupe posée sur un motif répété (des hashtags) dans une chaîne de texte, mettant en évidence ce qui correspond à l'expression régulière" />
  <figcaption>Figure 1 : une regex cherche un motif, pas un texte exact</figcaption>
</figure>

<h2>Écrire une regex</h2>

<p>Une regex s'écrit entre deux barres obliques, avec des indicateurs (<em>flags</em>) optionnels après la seconde :</p>

<pre><code>const regex = /motif/g;
//              ^^^^^ ^
//              motif  flag "g" = global (toutes les occurrences, pas juste la première)</code></pre>

<h2>Classes de caractères</h2>

<ul>
  <li><code>[abc]</code> — un des caractères a, b ou c</li>
  <li><code>[a-z]</code> — une lettre minuscule (plage)</li>
  <li><code>[0-9]</code> ou <code>\d</code> — un chiffre</li>
  <li><code>\s</code> — un espace (space, tabulation...)</li>
  <li><code>\w</code> — un caractère "mot" : lettre, chiffre ou <code>_</code></li>
  <li><code>.</code> — n'importe quel caractère (sauf retour à la ligne)</li>
</ul>

<h2>Quantificateurs</h2>

<ul>
  <li><code>+</code> — une fois ou plus</li>
  <li><code>*</code> — zéro fois ou plus</li>
  <li><code>?</code> — zéro ou une fois (optionnel)</li>
  <li><code>{n}</code> — exactement n fois</li>
</ul>

<pre><code>/\d+/    // un ou plusieurs chiffres consécutifs
/[a-z]*/  // zéro ou plusieurs lettres minuscules</code></pre>

<h2>test() : la regex correspond-elle ?</h2>

<p><code>test()</code> retourne simplement <code>true</code> ou <code>false</code> — utile pour valider un format :</p>

<pre><code>const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

regexEmail.test("ana@exemple.com"); // true
regexEmail.test("pas-un-email");     // false</code></pre>

<p><code>^</code> et <code>[^\s@]</code> ancrent le motif au début de la chaîne, <code>$</code> à la fin — sans eux, la regex accepterait aussi un texte qui contient juste un fragment ressemblant à un email quelque part au milieu.</p>

<h2>match() : extraire les correspondances</h2>

<p>Avec le flag <code>g</code>, <code>match()</code> retourne un tableau de <strong>toutes</strong> les correspondances trouvées dans le texte :</p>

<pre><code>const texte = "Superbe journée #soleil au #parc !";
const regexHashtag = /#[a-zA-Z0-9_]+/g;

texte.match(regexHashtag); // ["#soleil", "#parc"]</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module4-chap2/decomposition-regex.svg" alt="Décomposition annotée de la regex /^[^\s@]+@[^\s@]+\.[^\s@]+$/ symbole par symbole, chaque partie expliquée" />
  <figcaption>Figure 2 : chaque symbole d'une regex a un rôle précis, même dans un motif court</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li>Une regex décrit un motif ; <code>[...]</code> définit un ensemble de caractères acceptés, <code>+</code>/<code>*</code>/<code>?</code> combien de fois</li>
  <li><code>^</code> et <code>$</code> ancrent le motif au début/à la fin de la chaîne</li>
  <li><code>test()</code> répond vrai/faux — idéal pour valider un format</li>
  <li><code>match()</code> avec le flag <code>g</code> retourne toutes les correspondances trouvées</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap2,
    'title' => 'Expressions régulières de base',
    'content' => $theory2,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 4.3 ---
$theory3 = <<<'HTML'
<p>L'objet <code>Date</code> représente un instant précis dans le temps. Il sert aussi bien à afficher une date lisible qu'à calculer une durée entre deux moments.</p>

<h2>Créer et lire une date</h2>

<pre><code>const date = new Date(2026, 6, 19); // 19 juillet 2026 — le mois est indexé à partir de 0 (6 = juillet)

date.getFullYear(); // 2026
date.getMonth();     // 6  (juillet)
date.getDate();       // 19</code></pre>

<p>Attention au mois : <code>0</code> représente janvier, <code>11</code> représente décembre — une source d'erreur fréquente.</p>

<h2>Calculer une durée</h2>

<p>Deux dates soustraites l'une de l'autre donnent une différence en <strong>millisecondes</strong>. Diviser par le nombre de millisecondes dans un jour donne un nombre de jours :</p>

<pre><code>const MS_PAR_JOUR = 1000 * 60 * 60 * 24;

const aujourdHui = new Date(2026, 6, 19);
const echeance = new Date(2026, 6, 25);

const jours = Math.ceil((echeance - aujourdHui) / MS_PAR_JOUR);
console.log(jours); // 6</code></pre>

<figure class="theory-image size-medium align-center">
  <img src="/images/module4-chap3/frise-chronologique.svg" alt="Frise chronologique simple avec un point aujourd'hui et un point échéance plus loin sur la ligne, la distance entre les deux représentant le nombre de jours restants" />
  <figcaption>Figure 1 : une durée est une distance entre deux points sur la frise du temps</figcaption>
</figure>

<h2>Formater avec Intl.DateTimeFormat</h2>

<p><code>Intl.DateTimeFormat</code> transforme une date en texte lisible, dans la langue et le format souhaités — bien plus fiable que d'assembler soi-même jour/mois/année :</p>

<pre><code>const date = new Date(Date.UTC(2026, 6, 19));

const court = new Intl.DateTimeFormat("fr-FR", {
  day: "2-digit", month: "2-digit", year: "numeric", timeZone: "UTC",
});
court.format(date); // "19/07/2026"

const long = new Intl.DateTimeFormat("fr-FR", {
  day: "numeric", month: "long", year: "numeric", timeZone: "UTC",
});
long.format(date); // "19 juillet 2026"</code></pre>

<p>Préciser <code>timeZone</code> évite qu'un même instant s'affiche différemment selon le fuseau horaire de l'appareil qui exécute le code — important dès qu'on veut un résultat reproductible.</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module4-chap3/trois-formats-date.svg" alt="Une même date affichée dans trois formats différents : ISO (2026-07-19), français (19/07/2026), et relatif (dans 6 jours)" />
  <figcaption>Figure 2 : la même date, trois formats selon le contexte d'affichage</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li><code>new Date(annee, mois, jour)</code> : le mois commence à 0 (janvier)</li>
  <li>Soustraire deux dates donne une différence en millisecondes — diviser pour obtenir des jours</li>
  <li><code>Intl.DateTimeFormat</code> formate une date de façon lisible, dans la langue voulue</li>
  <li>Préciser <code>timeZone</code> rend le formatage reproductible, indépendamment de l'appareil</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap3,
    'title' => 'Dates et formats',
    'content' => $theory3,
    'order_index' => 1,
    'estimated_time' => 15,
]);

echo "✓ 3 théories créées\n";

// ================================================================
// EXERCICES
// ================================================================
$insertExercice = $pdo->prepare(
    "INSERT INTO exercices
    (chapitre_id, title, description, instructions, starter_code, solution_code, expected_output, difficulty, points, order_index)
    VALUES
    (:chapitre_id, :title, :description, :instructions, :starter_code, :solution_code, :expected_output, :difficulty, :points, :order_index)"
);

// --- Chapitre 4.1 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Message de bienvenue',
    'description' => "Construis un message de bienvenue personnalisé avec un template literal.",
    'instructions' => "À partir du tableau utilisateurs fourni, affiche pour chacun un message au format \"Bienvenue <prenom> ! Ravi de vous voir depuis <ville>.\" en utilisant un template literal. Le programme doit afficher exactement :\nBienvenue Ana ! Ravi de vous voir depuis Lyon.\nBienvenue Léo ! Ravi de vous voir depuis Nantes.",
    'starter_code' => <<<'JS'
const utilisateurs = [
  { prenom: "Ana", ville: "Lyon" },
  { prenom: "Léo", ville: "Nantes" },
];

// TODO : pour chaque utilisateur, affiche "Bienvenue <prenom> ! Ravi de
// vous voir depuis <ville>." avec un template literal
JS,
    'solution_code' => <<<'JS'
const utilisateurs = [
  { prenom: "Ana", ville: "Lyon" },
  { prenom: "Léo", ville: "Nantes" },
];

for (const u of utilisateurs) {
  console.log(`Bienvenue ${u.prenom} ! Ravi de vous voir depuis ${u.ville}.`);
}
JS,
    'expected_output' => "Bienvenue Ana ! Ravi de vous voir depuis Lyon.\nBienvenue Léo ! Ravi de vous voir depuis Nantes.",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 4.1 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Slugify',
    'description' => "Transforme un titre en slug d'URL propre : minuscules, tirets, sans accents.",
    'instructions' => "Écris une fonction slugify(texte) qui retourne une version \"slug\" du texte : sans accents, en minuscules, espaces/ponctuation remplacés par un seul tiret, sans tiret en début ou fin de résultat. Applique-la à \"Écrire du JavaScript facilement !\" puis à \"  Guide  Débutant : Les Bases  \", et affiche chaque résultat sur sa propre ligne. Le programme doit afficher exactement :\necrire-du-javascript-facilement\nguide-debutant-les-bases",
    'starter_code' => <<<'JS'
// TODO : fonction slugify(texte) -> minuscules, sans accents, tirets à la
// place des espaces/ponctuation, sans tiret en début/fin

console.log(slugify("Écrire du JavaScript facilement !"));
console.log(slugify("  Guide  Débutant : Les Bases  "));
JS,
    'solution_code' => <<<'JS'
function slugify(texte) {
  return texte
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

console.log(slugify("Écrire du JavaScript facilement !"));
console.log(slugify("  Guide  Débutant : Les Bases  "));
JS,
    'expected_output' => "ecrire-du-javascript-facilement\nguide-debutant-les-bases",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 4.2 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Valider un email',
    'description' => "Valide le format d'adresses email avec une expression régulière simple.",
    'instructions' => "Écris une fonction estEmailValide(email) qui retourne true si email a la forme \"quelquechose@quelquechose.quelquechose\" (aucun espace, un seul @, un point après le @), false sinon. Pour chaque email du tableau fourni, affiche \"<email> : <resultat>\". Le programme doit afficher exactement :\nana@example.com : true\npas-un-email : false\nleo@site : false",
    'starter_code' => <<<'JS'
const emails = ["ana@example.com", "pas-un-email", "leo@site"];

// TODO : fonction estEmailValide(email) avec une regex, puis affiche
// "<email> : <resultat>" pour chaque email du tableau
JS,
    'solution_code' => <<<'JS'
function estEmailValide(email) {
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return regex.test(email);
}

const emails = ["ana@example.com", "pas-un-email", "leo@site"];

for (const email of emails) {
  console.log(`${email} : ${estEmailValide(email)}`);
}
JS,
    'expected_output' => "ana@example.com : true\npas-un-email : false\nleo@site : false",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 4.2 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Extraire les hashtags',
    'description' => "Extrait tous les hashtags d'un texte libre avec match() et une regex globale.",
    'instructions' => "Écris une fonction extraireHashtags(texte) qui retourne un tableau de tous les hashtags trouvés dans texte (un hashtag commence par # suivi de lettres, chiffres ou _). Applique-la au texte fourni, puis affiche le résultat joint par \", \". Le programme doit afficher exactement :\n#soleil, #parc, #weekend",
    'starter_code' => <<<'JS'
const texte = "Superbe journée #soleil au #parc avec des amis #weekend !";

// TODO : fonction extraireHashtags(texte) avec une regex globale (/g),
// puis affiche le résultat joint par ", "
JS,
    'solution_code' => <<<'JS'
function extraireHashtags(texte) {
  const regex = /#[a-zA-Z0-9_]+/g;
  return texte.match(regex) || [];
}

const texte = "Superbe journée #soleil au #parc avec des amis #weekend !";
console.log(extraireHashtags(texte).join(", "));
JS,
    'expected_output' => '#soleil, #parc, #weekend',
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 4.3 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Formater une date en français',
    'description' => "Affiche une date donnée, formatée en jour/mois/année avec Intl.DateTimeFormat.",
    'instructions' => "Une date fixe est fournie (19 juillet 2026, en UTC). Formate-la en \"JJ/MM/AAAA\" avec Intl.DateTimeFormat (locale \"fr-FR\", timeZone \"UTC\"), puis affiche le résultat. Le programme doit afficher exactement :\n19/07/2026",
    'starter_code' => <<<'JS'
const date = new Date(Date.UTC(2026, 6, 19));

// TODO : formate date en "JJ/MM/AAAA" avec Intl.DateTimeFormat
// (locale "fr-FR", timeZone "UTC"), puis affiche le résultat
JS,
    'solution_code' => <<<'JS'
const date = new Date(Date.UTC(2026, 6, 19));

const formateur = new Intl.DateTimeFormat("fr-FR", {
  day: "2-digit",
  month: "2-digit",
  year: "numeric",
  timeZone: "UTC",
});

console.log(formateur.format(date));
JS,
    'expected_output' => '19/07/2026',
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 4.3 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Jours restants avant échéance',
    'description' => "Calcule le nombre de jours restants entre deux dates fixes.",
    'instructions' => "Écris une fonction joursRestants(dateActuelle, dateEcheance) qui retourne le nombre de jours entre les deux dates. Avec aujourdHui = 19 juillet 2026 (UTC) et echeance = 25 juillet 2026 (UTC), affiche \"Il reste <n> jour(s) avant l'échéance.\". Le programme doit afficher exactement :\nIl reste 6 jour(s) avant l'échéance.",
    'starter_code' => <<<'JS'
const aujourdHui = new Date(Date.UTC(2026, 6, 19));
const echeance = new Date(Date.UTC(2026, 6, 25));

// TODO : fonction joursRestants(dateActuelle, dateEcheance) qui retourne
// un nombre de jours, puis affiche "Il reste <n> jour(s) avant l'échéance."
JS,
    'solution_code' => <<<'JS'
function joursRestants(dateActuelle, dateEcheance) {
  const MS_PAR_JOUR = 1000 * 60 * 60 * 24;
  const diff = dateEcheance - dateActuelle;
  return Math.ceil(diff / MS_PAR_JOUR);
}

const aujourdHui = new Date(Date.UTC(2026, 6, 19));
const echeance = new Date(Date.UTC(2026, 6, 25));

console.log(`Il reste ${joursRestants(aujourdHui, echeance)} jour(s) avant l'échéance.`);
JS,
    'expected_output' => "Il reste 6 jour(s) avant l'échéance.",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

echo "✓ 6 exercices créés\n";

echo "\n=== Module 4 créé avec succès (module id=$moduleId) ===\n";
