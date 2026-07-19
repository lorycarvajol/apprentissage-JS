<?php

/**
 * Crée le Module 3 ("Fonctions") du curriculum JavaScript — voir ROADMAP.md
 * à la racine du dépôt. Suppose que les Modules 1 et 2 (seed_module1.php,
 * seed_module2.php) ont déjà été créés et occupent order_index=1 et 2.
 *
 * expected_output de chaque exercice a été calculé hors-ligne en rejouant
 * solution_code à travers une réplique exacte de la logique de capture de
 * frontend/src/utils/jsSandbox.js (stringifyArg + console.log + join('\n')).
 *
 * Usage : php database/seed_module3.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$pdo = Database::getConnection();

echo "=== Création du Module 3 : Fonctions ===\n\n";

// ================================================================
// Garde-fou : ne pas rejouer le script si le module existe déjà
// ================================================================
$check = $pdo->prepare("SELECT id FROM modules WHERE title = :title");
$check->execute(['title' => 'Fonctions']);
if ($check->fetch()) {
    echo "Le module 'Fonctions' existe déjà, arrêt du script.\n";
    exit(0);
}

// ================================================================
// MODULE 3
// ================================================================
$insertModule = $pdo->prepare(
    "INSERT INTO modules (title, description, order_index, is_published)
    VALUES (:title, :description, 3, 1)"
);
$insertModule->execute([
    'title' => 'Fonctions',
    'description' => "Troisième module du curriculum JavaScript : déclarer et appeler des fonctions (déclaration, expression, arrow), portée et closures, et fonctions d'ordre supérieur (voir ROADMAP.md).",
]);
$moduleId = (int) $pdo->lastInsertId();
echo "✓ Module créé (id=$moduleId, order_index=3)\n";

// ================================================================
// CHAPITRES
// ================================================================
$insertChapitre = $pdo->prepare(
    "INSERT INTO chapitres (module_id, title, description, order_index, is_published)
    VALUES (:module_id, :title, :description, :order_index, 1)"
);

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Déclarer et utiliser des fonctions',
    'description' => 'Déclaration de fonction, expression de fonction, arrow function, paramètres par défaut et valeur de retour.',
    'order_index' => 1,
]);
$chap1 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Portée et closures',
    'description' => "Portée de bloc vs de fonction, portée lexicale, closures et leurs cas d'usage (compteur privé, fabrique de fonctions).",
    'order_index' => 2,
]);
$chap2 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => "Fonctions d'ordre supérieur",
    'description' => "Fonctions acceptant ou retournant une fonction, callbacks, map/filter/reduce en détail, et quand préférer une boucle.",
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

// --- Chapitre 3.1 ---
$theory1 = <<<'HTML'
<p>Une fonction regroupe un bloc d'instructions sous un nom, pour pouvoir l'exécuter plusieurs fois sans dupliquer le code — et pour donner une intention claire à ce bloc, juste par son nom.</p>

<figure class="theory-image size-medium align-center">
  <img src="/images/module3-chap1/boite-noire.svg" alt="Schéma boîte noire : des entrées à gauche, un traitement représenté par une boîte au centre, une sortie à droite" />
  <figcaption>Figure 1 : une fonction prend des entrées (paramètres) et produit une sortie (valeur de retour)</figcaption>
</figure>

<h2>Déclaration de fonction</h2>

<p>La forme la plus classique : le mot-clé <code>function</code>, suivi d'un nom, de paramètres entre parenthèses, et d'un bloc :</p>

<pre><code>function saluer(prenom) {
  return `Bonjour ${prenom} !`;
}

console.log(saluer("Ana")); // Bonjour Ana !</code></pre>

<p>Une déclaration de fonction est utilisable même <strong>avant</strong> sa ligne d'écriture dans le fichier (elle est "hissée" par le moteur JavaScript) — un comportement propre à cette syntaxe.</p>

<h2>Expression de fonction</h2>

<p>Ici, la fonction est stockée dans une variable, comme n'importe quelle autre valeur :</p>

<pre><code>const saluer = function (prenom) {
  return `Bonjour ${prenom} !`;
};

console.log(saluer("Léo")); // Bonjour Léo !</code></pre>

<p>Contrairement à une déclaration, une expression de fonction n'est utilisable qu'<strong>à partir</strong> de sa ligne d'écriture.</p>

<h2>Arrow function</h2>

<p>Syntaxe plus courte, introduite par ES6, particulièrement pratique pour de petites fonctions :</p>

<pre><code>const saluer = (prenom) => {
  return `Bonjour ${prenom} !`;
};

// avec une seule expression, le retour est implicite (pas de { }, pas de return)
const saluerCourt = (prenom) => `Bonjour ${prenom} !`;</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module3-chap1/comparatif-syntaxes.svg" alt="Comparatif ligne à ligne des trois syntaxes de fonction (déclaration, expression, arrow) pour la même fonction saluer, avec les parties communes surlignées" />
  <figcaption>Figure 2 : trois syntaxes différentes, le même comportement</figcaption>
</figure>

<h2>Paramètres par défaut</h2>

<p>Un paramètre peut recevoir une valeur par défaut, utilisée seulement si l'appelant ne fournit rien (ou <code>undefined</code>) pour ce paramètre :</p>

<pre><code>function calculerRemise(prix, pourcentage = 10) {
  return prix - (prix * pourcentage) / 100;
}

calculerRemise(200);     // 180 — pourcentage vaut 10 par défaut
calculerRemise(200, 25); // 150 — la valeur fournie remplace le défaut</code></pre>

<h2>Valeur de retour</h2>

<p><code>return</code> arrête immédiatement la fonction et renvoie une valeur à l'endroit où elle a été appelée. Sans <code>return</code> explicite, une fonction renvoie <code>undefined</code>.</p>

<h2>En résumé</h2>

<ul>
  <li>Déclaration de fonction : utilisable avant sa ligne d'écriture (hissée)</li>
  <li>Expression de fonction : stockée dans une variable, utilisable seulement après</li>
  <li>Arrow function : syntaxe courte, retour implicite si une seule expression</li>
  <li>Un paramètre par défaut ne s'applique que si rien n'est fourni (ou <code>undefined</code>)</li>
  <li><code>return</code> arrête la fonction et renvoie sa valeur ; sans lui, la fonction renvoie <code>undefined</code></li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap1,
    'title' => 'Déclarer et utiliser des fonctions',
    'content' => $theory1,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 3.2 ---
$theory2 = <<<'HTML'
<p>La <strong>portée</strong> (scope) d'une variable, c'est l'endroit du code où elle est visible et utilisable. Comprendre la portée évite des bugs difficiles à repérer, et ouvre la porte à un outil puissant : la <strong>closure</strong>.</p>

<h2>Portée de bloc vs portée de fonction</h2>

<p><code>let</code> et <code>const</code> ont une portée de <strong>bloc</strong> : une variable déclarée dans <code>{ }</code> n'existe que dans ce bloc. <code>var</code>, plus ancien, a une portée de <strong>fonction</strong> : il ignore les blocs <code>{ }</code> et "fuit" jusqu'à la fonction englobante.</p>

<pre><code>function tester() {
  if (true) {
    let a = 1;
    var b = 2;
  }
  console.log(b); // 2 — var a fuité hors du bloc if
  console.log(a); // ReferenceError : a n'existe pas ici
}</code></pre>

<p>C'est une des raisons pour lesquelles <code>let</code>/<code>const</code> sont aujourd'hui préférés à <code>var</code> : une portée plus prévisible, limitée au bloc où la variable est réellement utilisée.</p>

<h2>Portée lexicale</h2>

<p>Une fonction a accès aux variables de l'endroit où elle a été <strong>écrite</strong> (pas de l'endroit où elle est appelée) — c'est la portée lexicale. Une fonction imbriquée voit toujours les variables de ses fonctions englobantes :</p>

<pre><code>function exterieure() {
  const message = "salut";

  function interieure() {
    console.log(message); // accessible : interieure est écrite dans exterieure
  }

  interieure();
}</code></pre>

<h2>Qu'est-ce qu'une closure</h2>

<p>Une <strong>closure</strong>, c'est une fonction qui "emporte avec elle" les variables de son environnement de création, même après que cet environnement a normalement fini de s'exécuter :</p>

<pre><code>function creerSaluer(prenom) {
  return function () {
    console.log(`Bonjour ${prenom} !`);
  };
}

const saluerAna = creerSaluer("Ana");
saluerAna(); // Bonjour Ana ! — prenom est toujours accessible</code></pre>

<figure class="theory-image size-medium align-center">
  <img src="/images/module3-chap2/sac-a-dos-closure.svg" alt="Une fonction interne représentée avec un sac à dos contenant les variables de son environnement de création, qu'elle emporte avec elle même après la fin de la fonction externe" />
  <figcaption>Figure 1 : une closure emporte avec elle les variables de son environnement de création</figcaption>
</figure>

<h2>Cas d'usage : compteur privé</h2>

<p>Une closure permet de garder un état "privé", inaccessible de l'extérieur autrement que par la fonction qu'elle retourne :</p>

<pre><code>function creerCompteur() {
  let compte = 0;
  return function incrementer() {
    compte++;
    return compte;
  };
}

const compteur = creerCompteur();
compteur(); // 1
compteur(); // 2
compteur(); // 3
// impossible d'accéder directement à "compte" depuis l'extérieur</code></pre>

<p>Chaque appel à <code>creerCompteur()</code> crée un <strong>nouvel</strong> environnement, donc un nouveau <code>compte</code> indépendant :</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module3-chap2/chronologie-compteurs.svg" alt="Chronologie de trois appels successifs à creerCompteur(), chacun produisant un compteur indépendant avec son propre état interne" />
  <figcaption>Figure 2 : trois appels à creerCompteur() produisent trois compteurs indépendants</figcaption>
</figure>

<h2>Piège classique : var dans une boucle</h2>

<p><code>var</code> ayant une portée de fonction (pas de bloc), toutes les fonctions créées dans une boucle <code>for (var i ...)</code> partagent la <strong>même</strong> variable <code>i</code> — et voient sa valeur finale, pas la valeur qu'elle avait à leur création :</p>

<pre><code>const fonctions = [];

for (var i = 0; i < 3; i++) {
  fonctions.push(function () {
    console.log(i);
  });
}

fonctions.forEach(f => f());
// 3
// 3
// 3  — bug : les trois affichent la valeur finale de i</code></pre>

<p>Remplacer <code>var</code> par <code>let</code> corrige le problème : <code>let</code> crée une <strong>nouvelle</strong> variable <code>i</code> à chaque tour de boucle, donc chaque fonction capture sa propre valeur :</p>

<pre><code>for (let i = 0; i < 3; i++) {
  fonctions.push(function () {
    console.log(i);
  });
}
// 0
// 1
// 2  — chaque fonction a bien capturé "son" i</code></pre>

<h2>En résumé</h2>

<ul>
  <li><code>let</code>/<code>const</code> : portée de bloc — <code>var</code> : portée de fonction (peut "fuir" d'un bloc)</li>
  <li>La portée lexicale : une fonction voit les variables de l'endroit où elle est <strong>écrite</strong></li>
  <li>Une closure emporte avec elle les variables de son environnement de création</li>
  <li>Chaque appel à une fonction fabrique un nouvel environnement, donc une nouvelle closure indépendante</li>
  <li><code>var</code> dans une boucle piège souvent les closures : préférer <code>let</code> pour capturer une valeur par tour</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap2,
    'title' => 'Portée et closures',
    'content' => $theory2,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 3.3 ---
$theory3 = <<<'HTML'
<p>Une <strong>fonction d'ordre supérieur</strong> est une fonction qui accepte une autre fonction en paramètre, en retourne une, ou les deux. C'est une notion abstraite en apparence, mais déjà familière : <code>map</code>, <code>filter</code> et <code>reduce</code>, vus au module précédent, en sont des exemples.</p>

<figure class="theory-image size-medium align-center">
  <img src="/images/module3-chap3/rouage-callback.svg" alt="Une flèche entre dans une fonction représentée comme un rouage, une fonction callback plus petite glissée à l'intérieur du rouage" />
  <figcaption>Figure 1 : une fonction d'ordre supérieur reçoit une autre fonction (callback) et l'utilise dans son propre traitement</figcaption>
</figure>

<h2>Callbacks</h2>

<p>Un <strong>callback</strong> est simplement une fonction passée en argument à une autre fonction, pour être appelée à un moment précis de son traitement :</p>

<pre><code>function repeter(n, callback) {
  for (let i = 1; i <= n; i++) {
    callback(i);
  }
}

repeter(3, (i) => console.log(`Tour ${i}`));
// Tour 1
// Tour 2
// Tour 3</code></pre>

<p><code>repeter</code> ne sait rien du contenu de <code>callback</code> — elle sait seulement quand l'appeler. C'est ce découplage qui rend les fonctions d'ordre supérieur réutilisables.</p>

<h2>map/filter/reduce en détail</h2>

<p>Ces trois méthodes de tableau sont des fonctions d'ordre supérieur : elles acceptent une fonction qui décrit <strong>quoi</strong> faire, et se chargent elles-mêmes du <strong>comment</strong> (parcourir le tableau) :</p>

<pre><code>const notes = [12, 8, 15, 5, 18];

notes.map(n => n + 1);            // ajoute 1 à chaque note
notes.filter(n => n >= 10);        // garde les notes suffisantes
notes.reduce((total, n) => total + n, 0); // additionne toutes les notes</code></pre>

<p>Une boucle <code>for</code>/<code>for...of</code> classique reste préférable quand le traitement est complexe, mélange plusieurs opérations différentes, ou a besoin de sortir en cours de route avec <code>break</code> — ce que <code>map</code>/<code>filter</code>/<code>reduce</code> ne permettent pas nativement.</p>

<h2>Composer des fonctions</h2>

<p>Une fonction d'ordre supérieur peut aussi <strong>retourner</strong> une fonction — c'est la base de la composition : enchaîner plusieurs petites fonctions pour en former une seule pipeline de traitement.</p>

<pre><code>function normaliser(tableau) {
  return tableau.map(mot => mot.trim().toLowerCase());
}

function trier(tableau) {
  return [...tableau].sort();
}

function composer(...fonctions) {
  return function (valeurInitiale) {
    return fonctions.reduce((valeur, fn) => fn(valeur), valeurInitiale);
  };
}

const normaliserEtTrier = composer(normaliser, trier);
normaliserEtTrier(["  Bruno", "alice "]); // ["alice", "bruno"]</code></pre>

<p><code>composer</code> ne connaît ni <code>normaliser</code> ni <code>trier</code> à l'avance : elle accepte n'importe quelle liste de fonctions et les enchaîne, chacune recevant le résultat de la précédente.</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module3-chap3/chaine-rouages-pipeline.svg" alt="Chaîne de rouages engrenés représentant une pipeline de transformations successives : une liste brute traverse normaliser() puis trier() pour produire une liste propre et ordonnée" />
  <figcaption>Figure 2 : composer, c'est engrener plusieurs petites fonctions en une seule pipeline</figcaption>
</figure>

<h2>Boucle vs méthode : comment choisir</h2>

<ul>
  <li><code>map</code>/<code>filter</code>/<code>reduce</code> : traitement simple et déclaratif, une transformation claire par étape</li>
  <li><code>for</code>/<code>for...of</code> : traitement complexe, plusieurs actions différentes par élément, ou besoin de <code>break</code>/<code>continue</code></li>
</ul>

<h2>En résumé</h2>

<ul>
  <li>Une fonction d'ordre supérieur accepte une fonction, en retourne une, ou les deux</li>
  <li>Un callback est une fonction passée en argument, appelée au bon moment par la fonction qui la reçoit</li>
  <li><code>map</code>/<code>filter</code>/<code>reduce</code> sont des fonctions d'ordre supérieur déjà rencontrées au module précédent</li>
  <li>Composer des fonctions, c'est enchaîner leurs résultats pour former une pipeline de traitement</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap3,
    'title' => "Fonctions d'ordre supérieur",
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

// --- Chapitre 3.1 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Calculer une remise',
    'description' => "Écris une fonction calculerRemise avec un paramètre par défaut.",
    'instructions' => "Écris une fonction calculerRemise(prix, pourcentage = 10) qui retourne le prix après remise. Affiche le résultat pour calculerRemise(200) (pourcentage par défaut), puis pour calculerRemise(150, 20). Le programme doit afficher exactement :\nRemise par défaut : 180 €\nRemise personnalisée : 120 €",
    'starter_code' => <<<'JS'
// TODO : fonction calculerRemise(prix, pourcentage = 10) qui retourne
// le prix après remise, puis affiche les deux résultats demandés
JS,
    'solution_code' => <<<'JS'
function calculerRemise(prix, pourcentage = 10) {
  return prix - (prix * pourcentage) / 100;
}

console.log(`Remise par défaut : ${calculerRemise(200)} €`);
console.log(`Remise personnalisée : ${calculerRemise(150, 20)} €`);
JS,
    'expected_output' => "Remise par défaut : 180 €\nRemise personnalisée : 120 €",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 3.1 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Trois syntaxes, un seul comportement',
    'description' => "Réécris une même fonction dans les trois syntaxes et vérifie qu'elles donnent le même résultat.",
    'instructions' => "Écris une fonction estMajeur(age) (retourne true si age >= 18) sous ses trois formes : déclaration (estMajeurDeclaration), expression (estMajeurExpression), et arrow (estMajeurArrow). Pour chaque âge du tableau [15, 18, 25], affiche les trois résultats séparés par \", \" au format \"<age> ans : <r1>, <r2>, <r3>\". Le programme doit afficher exactement :\n15 ans : false, false, false\n18 ans : true, true, true\n25 ans : true, true, true",
    'starter_code' => <<<'JS'
const ages = [15, 18, 25];

// TODO : écris estMajeur sous ses trois formes (déclaration, expression,
// arrow), puis pour chaque âge affiche "<age> ans : <r1>, <r2>, <r3>"
JS,
    'solution_code' => <<<'JS'
function estMajeurDeclaration(age) {
  return age >= 18;
}

const estMajeurExpression = function (age) {
  return age >= 18;
};

const estMajeurArrow = (age) => age >= 18;

const ages = [15, 18, 25];

for (const age of ages) {
  const resultats = [estMajeurDeclaration(age), estMajeurExpression(age), estMajeurArrow(age)];
  console.log(`${age} ans : ${resultats.join(', ')}`);
}
JS,
    'expected_output' => "15 ans : false, false, false\n18 ans : true, true, true\n25 ans : true, true, true",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 3.2 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Fabrique de compteur',
    'description' => "Crée une fabrique creerCompteur() qui retourne une fonction gardant son propre compte.",
    'instructions' => "Écris une fonction creerCompteur() qui retourne une fonction incrementer() : chaque appel à incrementer() augmente un compte interne de 1 et retourne sa nouvelle valeur. Crée un compteur, puis affiche le résultat de trois appels successifs au format \"Compteur : <valeur>\". Le programme doit afficher exactement :\nCompteur : 1\nCompteur : 2\nCompteur : 3",
    'starter_code' => <<<'JS'
// TODO : fonction creerCompteur() qui retourne une fonction incrementer()
// gardant un compte interne, puis affiche 3 appels successifs
JS,
    'solution_code' => <<<'JS'
function creerCompteur() {
  let compte = 0;
  return function incrementer() {
    compte++;
    return compte;
  };
}

const compteur = creerCompteur();
console.log(`Compteur : ${compteur()}`);
console.log(`Compteur : ${compteur()}`);
console.log(`Compteur : ${compteur()}`);
JS,
    'expected_output' => "Compteur : 1\nCompteur : 2\nCompteur : 3",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 3.2 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Corriger le bug de closure',
    'description' => "Corrige un bug classique où toutes les fonctions créées dans une boucle capturent la même variable.",
    'instructions' => "Le code fourni construit un tableau de fonctions dans une boucle for (var i ...), puis les appelle toutes : à cause de var, elles affichent toutes la même valeur finale de i au lieu de la valeur qu'elles avaient à leur création. Corrige le bug (un seul mot-clé à changer) pour que chaque fonction affiche la valeur de i qu'elle avait lors de sa création. Le programme corrigé doit afficher exactement :\nValeur capturée : 0\nValeur capturée : 1\nValeur capturée : 2",
    'starter_code' => <<<'JS'
const fonctions = [];

for (var i = 0; i < 3; i++) {
  fonctions.push(function () {
    console.log(`Valeur capturée : ${i}`);
  });
}

fonctions.forEach(f => f());
// Bug : affiche trois fois "Valeur capturée : 3"
// TODO : corrige le bug pour que chaque fonction affiche 0, 1, puis 2
JS,
    'solution_code' => <<<'JS'
const fonctions = [];

for (let i = 0; i < 3; i++) {
  fonctions.push(function () {
    console.log(`Valeur capturée : ${i}`);
  });
}

fonctions.forEach(f => f());
JS,
    'expected_output' => "Valeur capturée : 0\nValeur capturée : 1\nValeur capturée : 2",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 3.3 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Total du panier avec reduce',
    'description' => "Utilise reduce() pour calculer le total des prix d'un panier.",
    'instructions' => "À partir du tableau panier fourni, calcule le total des prix avec reduce(), puis affiche \"Total du panier : <total> €\". Le programme doit afficher exactement :\nTotal du panier : 7.5 €",
    'starter_code' => <<<'JS'
const panier = [
  { nom: "Pain", prix: 2.5 },
  { nom: "Lait", prix: 1.2 },
  { nom: "Beurre", prix: 3.8 },
];

// TODO : calcule le total des prix avec reduce(), puis affiche
// "Total du panier : <total> €"
JS,
    'solution_code' => <<<'JS'
const panier = [
  { nom: "Pain", prix: 2.5 },
  { nom: "Lait", prix: 1.2 },
  { nom: "Beurre", prix: 3.8 },
];

const total = panier.reduce((somme, article) => somme + article.prix, 0);
console.log(`Total du panier : ${total} €`);
JS,
    'expected_output' => 'Total du panier : 7.5 €',
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 3.3 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Pipeline normaliser + trier',
    'description' => "Compose deux fonctions utilitaires en une seule pipeline réutilisable.",
    'instructions' => "Écris deux fonctions normaliser(tableau) (retire les espaces et met en minuscules chaque élément) et trier(tableau) (retourne une copie triée). Écris une fonction composer(...fonctions) qui retourne une nouvelle fonction enchaînant toutes les fonctions données, chacune recevant le résultat de la précédente. Utilise composer(normaliser, trier) pour construire normaliserEtTrier, applique-la au tableau noms fourni, puis affiche le résultat joint par \", \". Le programme doit afficher exactement :\nalice, bruno, chloé, diego",
    'starter_code' => <<<'JS'
const noms = ["  bruno", "ALICE ", "Chloé  ", " diego"];

// TODO : normaliser(tableau) -> trim + toLowerCase sur chaque élément
// TODO : trier(tableau) -> copie triée du tableau
// TODO : composer(...fonctions) -> fonction qui enchaîne toutes les
// fonctions données, chacune recevant le résultat de la précédente
// TODO : construis normaliserEtTrier avec composer, applique-la à noms,
// puis affiche le résultat joint par ", "
JS,
    'solution_code' => <<<'JS'
const noms = ["  bruno", "ALICE ", "Chloé  ", " diego"];

function normaliser(tableau) {
  return tableau.map(nom => nom.trim().toLowerCase());
}

function trier(tableau) {
  return [...tableau].sort();
}

function composer(...fonctions) {
  return function (valeurInitiale) {
    return fonctions.reduce((valeur, fn) => fn(valeur), valeurInitiale);
  };
}

const normaliserEtTrier = composer(normaliser, trier);

console.log(normaliserEtTrier(noms).join(", "));
JS,
    'expected_output' => 'alice, bruno, chloé, diego',
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

echo "✓ 6 exercices créés\n";

echo "\n=== Module 3 créé avec succès (module id=$moduleId) ===\n";
