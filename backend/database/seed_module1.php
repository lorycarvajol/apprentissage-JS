<?php

/**
 * Crée le Module 1 ("Bases du langage") du curriculum JavaScript — voir
 * ROADMAP.md à la racine du dépôt. Premier module du tronc commun JS
 * (variables, opérateurs, structures conditionnelles), avant boucles,
 * fonctions, DOM, asynchrone, POO, etc.
 *
 * expected_output de chaque exercice a été calculé hors-ligne en rejouant
 * solution_code à travers une réplique exacte de la logique de capture de
 * frontend/src/utils/jsSandbox.js (stringifyArg + console.log + join('\n')),
 * pas juste "lu à l'oeil" — voir la conversation d'origine pour le détail.
 *
 * Usage : php database/seed_module1.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$pdo = Database::getConnection();

echo "=== Création du Module 1 : Bases du langage ===\n\n";

// ================================================================
// Garde-fou : ne pas rejouer le script si le module existe déjà
// ================================================================
$check = $pdo->prepare("SELECT id FROM modules WHERE title = :title");
$check->execute(['title' => 'Bases du langage']);
if ($check->fetch()) {
    echo "Le module 'Bases du langage' existe déjà, arrêt du script.\n";
    exit(0);
}

// ================================================================
// MODULE 1
// ================================================================
$insertModule = $pdo->prepare(
    "INSERT INTO modules (title, description, order_index, is_published)
    VALUES (:title, :description, 1, 1)"
);
$insertModule->execute([
    'title' => 'Bases du langage',
    'description' => "Premier module du curriculum JavaScript : variables, types, opérateurs et structures conditionnelles — les fondations avant tout le reste (voir ROADMAP.md).",
]);
$moduleId = (int) $pdo->lastInsertId();
echo "✓ Module créé (id=$moduleId, order_index=1)\n";

// ================================================================
// CHAPITRES
// ================================================================
$insertChapitre = $pdo->prepare(
    "INSERT INTO chapitres (module_id, title, description, order_index, is_published)
    VALUES (:module_id, :title, :description, :order_index, 1)"
);

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Variables, types et syntaxe',
    'description' => 'Déclarer des variables avec let/const/var, connaître les types primitifs et comprendre la portée de bloc.',
    'order_index' => 1,
]);
$chap1 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Opérateurs et expressions',
    'description' => "Opérateurs arithmétiques, comparaison stricte, opérateurs logiques et priorité d'évaluation.",
    'order_index' => 2,
]);
$chap2 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Structures conditionnelles',
    'description' => 'if / else if / else et switch : faire réagir un programme différemment selon les données.',
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

// --- Chapitre 1.1 ---
$theory1 = <<<'HTML'
<p>Une <strong>variable</strong> est un espace nommé dans lequel on range une valeur, pour pouvoir la réutiliser plus loin dans le programme sans la réécrire. En JavaScript, on la déclare avec l'un de ces trois mots-clés : <code>let</code>, <code>const</code> ou <code>var</code>.</p>

<pre><code>const prenom = "Ana";   // ne pourra plus être réassignée
let age = 27;            // pourra être réassignée
age = 28;                 // OK

console.log(prenom, age); // Ana 28</code></pre>

<h2>const, let, var : lequel choisir ?</h2>

<ul>
  <li><code>const</code> : la valeur ne sera <strong>jamais réassignée</strong>. C'est le choix par défaut — la grande majorité de vos variables devraient être des <code>const</code>.</li>
  <li><code>let</code> : la valeur va <strong>changer</strong> au cours du programme (un compteur, un total qui s'accumule, un statut qui évolue...).</li>
  <li><code>var</code> : l'ancienne syntaxe, héritée des débuts de JavaScript. Elle fonctionne toujours mais son comportement de portée est une source de bugs (voir plus bas) — on ne l'utilise plus dans du code neuf.</li>
</ul>

<p>Réassigner une <code>const</code> provoque une erreur immédiate :</p>

<pre><code>const pi = 3.14;
pi = 3.15; // TypeError: Assignment to constant variable.</code></pre>

<h2>Les types primitifs</h2>

<p>Chaque valeur en JavaScript possède un <strong>type</strong>, qui détermine ce qu'on peut en faire. On peut interroger le type d'une valeur avec l'opérateur <code>typeof</code>. Les types primitifs les plus courants sont :</p>

<ul>
  <li><code>string</code> — une chaîne de caractères, entre guillemets ou apostrophes : <code>"Ana"</code></li>
  <li><code>number</code> — un nombre, entier ou décimal, JavaScript ne fait pas la distinction : <code>42</code>, <code>3.14</code></li>
  <li><code>boolean</code> — une valeur logique : <code>true</code> ou <code>false</code></li>
  <li><code>undefined</code> — une variable déclarée mais à laquelle aucune valeur n'a encore été assignée</li>
  <li><code>null</code> — l'absence volontaire de valeur, assignée explicitement</li>
</ul>

<pre><code>typeof "Ana";   // "string"
typeof 42;       // "number"
typeof true;     // "boolean"

let x;
typeof x;         // "undefined" — déclarée mais jamais initialisée</code></pre>

<figure class="theory-image size-medium align-center">
  <img src="/images/module1-chap1/types-primitifs.svg" alt="Trois boîtes représentant les types string, number et boolean, chacune avec un exemple de valeur et le résultat de typeof" />
  <figcaption>Figure 1 : chaque type primitif est une boîte avec sa propre étiquette</figcaption>
</figure>

<h2>La portée des variables</h2>

<p>La <strong>portée</strong> (<em>scope</em>) d'une variable, c'est l'endroit du code où elle est accessible. <code>let</code> et <code>const</code> ont une <strong>portée de bloc</strong> : une variable déclarée entre deux accolades <code>{ }</code> n'existe qu'à l'intérieur de ces accolades.</p>

<pre><code>if (true) {
  let x = 1;
}

console.log(x); // ReferenceError: x is not defined</code></pre>

<p><code>var</code>, elle, a une <strong>portée de fonction</strong> : elle ignore les blocs <code>{ }</code> et reste accessible dans toute la fonction qui l'entoure, même en dehors du bloc où elle a été déclarée. C'est exactement ce genre de fuite silencieuse qui rend <code>var</code> risquée dans du code un peu long.</p>

<pre><code>function essai() {
  if (true) {
    var x = 1;
  }

  console.log(x); // 1 — aucune erreur, alors que x semblait "enfermée" dans le if
}</code></pre>

<figure class="theory-image size-medium align-center">
  <img src="/images/module1-chap1/portee-let-vs-var.svg" alt="Comparaison : une variable let déclarée dans un bloc if reste inaccessible en dehors, alors qu'une variable var déclarée au même endroit fuit hors du bloc" />
  <figcaption>Figure 2 : <code>let</code> reste enfermée dans son bloc, <code>var</code> s'en échappe</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li>Préférez toujours <code>const</code>, passez à <code>let</code> uniquement si la variable doit changer</li>
  <li>N'utilisez pas <code>var</code> dans du code neuf</li>
  <li><code>typeof</code> renvoie le type d'une valeur sous forme de chaîne</li>
  <li>Une variable <code>let</code>/<code>const</code> n'existe que dans le bloc <code>{ }</code> où elle est déclarée</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap1,
    'title' => 'Variables, types et syntaxe',
    'content' => $theory1,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 1.2 ---
$theory2 = <<<'HTML'
<p>Une <strong>expression</strong> est un morceau de code qui produit une valeur : <code>2 + 3</code>, <code>age &gt;= 18</code>, ou même simplement <code>"Ana"</code> sont des expressions. Les <strong>opérateurs</strong> (<code>+</code>, <code>&gt;=</code>, <code>&amp;&amp;</code>...) sont les symboles qui combinent des valeurs pour produire ces expressions.</p>

<h2>Opérateurs arithmétiques</h2>

<p>Rien de très dépaysant si vous avez déjà manipulé des nombres en programmation :</p>

<pre><code>5 + 2;   // 7   addition
5 - 2;   // 3   soustraction
5 * 2;   // 10  multiplication
5 / 2;   // 2.5 division
5 % 2;   // 1   modulo (reste de la division entière)
5 ** 2;  // 25  puissance</code></pre>

<p>Un piège classique : l'opérateur <code>+</code> sert aussi à concaténer des chaînes. Dès qu'un des deux côtés est une chaîne, JavaScript convertit l'autre en chaîne et <strong>additionne du texte</strong> au lieu de faire un calcul :</p>

<pre><code>"5" + 2;   // "52"  (concaténation, pas addition !)
5 + 2 + "";  // "7"  (le résultat, lui, devient bien une chaîne)</code></pre>

<h2>Comparaisons : === vs ==</h2>

<p>JavaScript propose deux familles d'opérateurs de comparaison :</p>

<ul>
  <li><code>===</code> / <code>!==</code> — <strong>comparaison stricte</strong> : compare aussi le type, sans convertir quoi que ce soit</li>
  <li><code>==</code> / <code>!=</code> — <strong>comparaison non stricte</strong> : convertit les deux valeurs vers un type commun avant de comparer, ce qui donne parfois des résultats surprenants</li>
</ul>

<pre><code>"5" === 5;   // false — types différents (string vs number)
"5" == 5;    // true  — "5" est converti en 5 avant de comparer

0 == false;  // true  — encore une conversion surprenante
0 === false; // false</code></pre>

<p>En pratique : utilisez <strong>toujours</strong> <code>===</code> et <code>!==</code>. La version non stricte existe pour des raisons historiques et ses conversions implicites sont une source classique de bugs difficiles à repérer.</p>

<h2>Opérateurs logiques</h2>

<ul>
  <li><code>&amp;&amp;</code> (ET) — vrai seulement si les deux côtés sont vrais</li>
  <li><code>||</code> (OU) — vrai dès qu'un des deux côtés est vrai</li>
  <li><code>!</code> (NON) — inverse une valeur booléenne</li>
</ul>

<figure class="theory-image size-medium align-center">
  <img src="/images/module1-chap2/table-verite-et-ou.svg" alt="Table de vérité des opérateurs && et || pour les quatre combinaisons possibles de A et B" />
  <figcaption>Figure 1 : table de vérité de &amp;&amp; et ||</figcaption>
</figure>

<pre><code>const age = 20;
const estAbonne = true;

const peutAcceder = age >= 18 && estAbonne;
console.log(peutAcceder); // true — il faut les DEUX conditions</code></pre>

<h2>L'opérateur ternaire</h2>

<p>Une écriture compacte pour un <code>if</code>/<code>else</code> qui ne fait que choisir entre deux valeurs :</p>

<pre><code>const age = 16;
const statut = age >= 18 ? "majeur" : "mineur";
console.log(statut); // "mineur"</code></pre>

<p><code>condition ? valeurSiVrai : valeurSiFaux</code>. Pratique pour une valeur simple, mais à éviter dès que la condition ou les valeurs deviennent complexes — un <code>if</code>/<code>else</code> classique reste alors plus lisible (voir le chapitre suivant).</p>

<h2>Priorité des opérateurs</h2>

<p>Comme en mathématiques, les opérateurs n'ont pas tous la même priorité : la multiplication et la division s'exécutent avant l'addition, les comparaisons avant les opérateurs logiques, etc. En cas de doute, des parenthèses explicites clarifient toujours l'intention — pour vous comme pour qui relira le code.</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module1-chap2/ordre-evaluation.svg" alt="Cascade montrant l'évaluation étape par étape de l'expression 2 + 3 * 4 > 10 && 5 !== 4, jusqu'au résultat final true" />
  <figcaption>Figure 2 : une expression complexe s'évalue étape par étape, dans un ordre précis</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li><code>+</code> entre une chaîne et un nombre concatène au lieu d'additionner</li>
  <li>Préférez toujours <code>===</code>/<code>!==</code> à <code>==</code>/<code>!=</code></li>
  <li><code>&amp;&amp;</code> exige les deux conditions, <code>||</code> se contente d'une seule</li>
  <li>Dans le doute sur l'ordre d'évaluation, ajoutez des parenthèses</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap2,
    'title' => 'Opérateurs et expressions',
    'content' => $theory2,
    'order_index' => 1,
    'estimated_time' => 12,
]);

// --- Chapitre 1.3 ---
$theory3 = <<<'HTML'
<p>Une structure conditionnelle exécute un bloc de code <strong>seulement si</strong> une condition est vraie — c'est ce qui permet à un programme de réagir différemment selon les données qu'il reçoit.</p>

<figure class="theory-image size-small align-center">
  <img src="/images/module1-chap3/organigramme-decision.svg" alt="Organigramme simple : une condition évaluée, une branche oui menant à l'action A, une branche non menant à l'action B" />
  <figcaption>Figure 1 : toute condition mène à exactement une branche</figcaption>
</figure>

<h2>if / else if / else</h2>

<pre><code>const note = 14;

if (note >= 16) {
  console.log("Très bien");
} else if (note >= 12) {
  console.log("Bien");
} else if (note >= 10) {
  console.log("Passable");
} else {
  console.log("Insuffisant");
}
// affiche : "Bien"</code></pre>

<p>Les conditions sont testées <strong>dans l'ordre</strong>, et JavaScript s'arrête à la première qui est vraie — même si une condition plus bas serait, elle aussi, techniquement vraie. C'est pour ça que l'ordre des <code>else if</code> compte : ici, si on avait testé <code>note &gt;= 10</code> en premier, une note de 14 serait tombée dans "Passable" sans jamais atteindre "Bien".</p>

<h2>switch</h2>

<p><code>switch</code> compare une seule valeur à plusieurs cas possibles. Il devient plus lisible qu'une longue cascade de <code>if</code>/<code>else if</code> quand on teste toujours la <strong>même variable</strong> contre des valeurs précises (pas des plages comme <code>&gt;= 16</code>) :</p>

<pre><code>const jour = "mercredi";

switch (jour) {
  case "samedi":
  case "dimanche":
    console.log("Week-end");
    break;
  case "mercredi":
    console.log("Milieu de semaine");
    break;
  default:
    console.log("Jour de semaine");
}
// affiche : "Milieu de semaine"</code></pre>

<p>Deux détails qui piègent souvent en débutant :</p>

<ul>
  <li><code>break</code> est nécessaire pour arrêter le <code>switch</code> à la fin d'un <code>case</code> — sans lui, l'exécution continue dans le <code>case</code> suivant ("fall-through")</li>
  <li>plusieurs <code>case</code> l'un au-dessus de l'autre sans <code>break</code> entre eux (comme <code>"samedi"</code>/<code>"dimanche"</code> ci-dessus) partagent volontairement le même bloc de code — c'est la seule situation où on omet le <code>break</code> délibérément</li>
</ul>

<figure class="theory-image size-large align-center">
  <img src="/images/module1-chap3/if-else-vs-switch.svg" alt="Comparaison de la même logique de classement de note écrite en cascade if/else if imbriquée à gauche, et en branches à plat façon switch à droite" />
  <figcaption>Figure 2 : une cascade de décisions (if/else) vs des branches à plat (switch)</figcaption>
</figure>

<h2>Quand choisir quoi ?</h2>

<ul>
  <li><strong>if / else if</strong> : dès que les conditions portent sur des <strong>plages de valeurs</strong> ou combinent plusieurs variables (<code>age &gt;= 18 &amp;&amp; abonnementActif</code>)</li>
  <li><strong>switch</strong> : quand on compare une <strong>seule variable</strong> à une liste de valeurs exactes — le code exprime alors plus clairement "à chaque cas correspond une action", sans répéter le nom de la variable à chaque ligne</li>
</ul>

<h2>En résumé</h2>

<ul>
  <li>Les conditions <code>if</code>/<code>else if</code> sont testées dans l'ordre, la première vraie l'emporte</li>
  <li><code>switch</code> compare une valeur unique à plusieurs cas — n'oubliez pas les <code>break</code></li>
  <li>Choisissez la structure qui rend l'intention la plus évidente à la lecture, pas seulement celle qui "marche"</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap3,
    'title' => 'Structures conditionnelles',
    'content' => $theory3,
    'order_index' => 1,
    'estimated_time' => 12,
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

// --- Chapitre 1.1 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Fiche produit',
    'description' => 'Déclare trois variables typées correctement et affiche une fiche produit formatée.',
    'instructions' => "Déclare trois constantes : nom (chaîne \"Clavier mécanique\"), prix (nombre 59.90) et enStock (booléen true). Affiche ensuite, via un template literal, exactement :\nProduit : Clavier mécanique — 59.9 € (en stock : true)",
    'starter_code' => <<<'JS'
// Complète les trois variables ci-dessous avec les bonnes valeurs,
// puis affiche la phrase au format :
// "Produit : <nom> — <prix> € (en stock : <enStock>)"

const nom = "";
const prix = 0;
const enStock = false;

console.log(`Produit : ${nom} — ${prix} € (en stock : ${enStock})`);
JS,
    'solution_code' => <<<'JS'
const nom = "Clavier mécanique";
const prix = 59.90;
const enStock = true;

console.log(`Produit : ${nom} — ${prix} € (en stock : ${enStock})`);
JS,
    'expected_output' => 'Produit : Clavier mécanique — 59.9 € (en stock : true)',
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 1.1 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Le bug de la moyenne qui disparaît',
    'description' => "La fonction bilanEleve() calcule une moyenne et une mention, mais un bug de portée fait qu'elle affiche la mauvaise moyenne.",
    'instructions' => "bilanEleve(12, 8) doit afficher exactement \"admis (moyenne : 10)\". Actuellement elle affiche \"admis (moyenne : 4)\" : deux variables différentes portent accidentellement le même nom à cause de var. Corrige le code (renomme la variable qui n'a pas besoin de sortir de son bloc, et passe var en let/const) sans changer la logique métier.",
    'starter_code' => <<<'JS'
function bilanEleve(note1, note2) {
  var moyenne = (note1 + note2) / 2;

  if (moyenne >= 10) {
    var mention = "admis";
  } else {
    var mention = "recale";
  }

  // Calcul intermédiaire : l'écart entre les deux notes.
  // Bug : cette variable réutilise le même nom que la vraie moyenne !
  if (note1 !== note2) {
    var moyenne = Math.abs(note1 - note2);
  }

  return `${mention} (moyenne : ${moyenne})`;
}

console.log(bilanEleve(12, 8));
JS,
    'solution_code' => <<<'JS'
function bilanEleve(note1, note2) {
  const moyenne = (note1 + note2) / 2;
  const mention = moyenne >= 10 ? "admis" : "recale";

  if (note1 !== note2) {
    const ecart = Math.abs(note1 - note2);
  }

  return `${mention} (moyenne : ${moyenne})`;
}

console.log(bilanEleve(12, 8));
JS,
    'expected_output' => 'admis (moyenne : 10)',
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 1.2 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Calculer un prix TTC',
    'description' => "Calcule un prix toutes taxes comprises à partir d'un prix hors taxes et d'un taux de TVA.",
    'instructions' => "Avec prixHT = 80 et tauxTVA = 0.2, calcule prixTTC (prix HT + la TVA) et affiche exactement :\nPrix TTC : 96 €",
    'starter_code' => <<<'JS'
const prixHT = 80;
const tauxTVA = 0.2; // 20 %

// TODO : calcule le prix TTC (prix HT + la TVA)
const prixTTC = 0;

console.log(`Prix TTC : ${prixTTC} €`);
JS,
    'solution_code' => <<<'JS'
const prixHT = 80;
const tauxTVA = 0.2;

const prixTTC = prixHT + prixHT * tauxTVA;
console.log(`Prix TTC : ${prixTTC} €`);
JS,
    'expected_output' => 'Prix TTC : 96 €',
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 1.2 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => "Accès au contenu premium",
    'description' => "Écris une expression logique unique qui combine deux conditions.",
    'instructions' => "Complète peutAccederAuContenu(age, abonnementActif) pour qu'elle retourne true seulement si age >= 18 ET abonnementActif est vrai, en une seule expression logique (pas de if). Les trois appels fournis doivent afficher, dans l'ordre : true, false, false.",
    'starter_code' => <<<'JS'
function peutAccederAuContenu(age, abonnementActif) {
  // TODO : retourne true seulement si age >= 18 ET abonnementActif est vrai
}

console.log(peutAccederAuContenu(20, true));
console.log(peutAccederAuContenu(20, false));
console.log(peutAccederAuContenu(15, true));
JS,
    'solution_code' => <<<'JS'
function peutAccederAuContenu(age, abonnementActif) {
  return age >= 18 && abonnementActif;
}

console.log(peutAccederAuContenu(20, true));
console.log(peutAccederAuContenu(20, false));
console.log(peutAccederAuContenu(15, true));
JS,
    'expected_output' => "true\nfalse\nfalse",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 1.3 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Classer une note',
    'description' => 'Utilise if / else if / else pour transformer une note en mention.',
    'instructions' => "Avec note = 15, complète la cascade if / else if / else pour affecter à mention : \"Tres bien\" si note >= 16, \"Bien\" si note >= 12, sinon \"Passable\". Le programme doit afficher exactement :\nBien",
    'starter_code' => <<<'JS'
const note = 15;

let mention;

// TODO : if / else if / else pour définir mention :
// >= 16 -> "Tres bien", >= 12 -> "Bien", sinon -> "Passable"

console.log(mention);
JS,
    'solution_code' => <<<'JS'
const note = 15;

let mention;

if (note >= 16) {
  mention = "Tres bien";
} else if (note >= 12) {
  mention = "Bien";
} else {
  mention = "Passable";
}

console.log(mention);
JS,
    'expected_output' => 'Bien',
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 1.3 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Le switch qui saute une case',
    'description' => "Un break manquant provoque un fall-through silencieux dans un switch.",
    'instructions' => "nomJour(1), nomJour(3) et nomJour(9) doivent afficher, dans l'ordre : Lundi, Mercredi, Jour invalide. Actuellement nomJour(1) affiche \"Mardi\" au lieu de \"Lundi\" à cause d'un break manquant. Corrige le switch.",
    'starter_code' => <<<'JS'
function nomJour(numero) {
  let resultat;

  switch (numero) {
    case 1:
      resultat = "Lundi";
    case 2:
      resultat = "Mardi";
      break;
    case 3:
      resultat = "Mercredi";
      break;
    default:
      resultat = "Jour invalide";
  }

  return resultat;
}

console.log(nomJour(1));
console.log(nomJour(3));
console.log(nomJour(9));
JS,
    'solution_code' => <<<'JS'
function nomJour(numero) {
  let resultat;

  switch (numero) {
    case 1:
      resultat = "Lundi";
      break;
    case 2:
      resultat = "Mardi";
      break;
    case 3:
      resultat = "Mercredi";
      break;
    default:
      resultat = "Jour invalide";
  }

  return resultat;
}

console.log(nomJour(1));
console.log(nomJour(3));
console.log(nomJour(9));
JS,
    'expected_output' => "Lundi\nMercredi\nJour invalide",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

echo "✓ 6 exercices créés\n";

echo "\n=== Module 1 créé avec succès (module id=$moduleId) ===\n";
