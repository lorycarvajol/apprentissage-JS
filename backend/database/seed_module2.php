<?php

/**
 * Crée le Module 2 ("Boucles et collections") du curriculum JavaScript —
 * voir ROADMAP.md à la racine du dépôt. Suppose que le Module 1 ("Bases du
 * langage", seed_module1.php) a déjà été créé et occupe order_index=1.
 *
 * expected_output de chaque exercice a été calculé hors-ligne en rejouant
 * solution_code à travers une réplique exacte de la logique de capture de
 * frontend/src/utils/jsSandbox.js (stringifyArg + console.log + join('\n')).
 *
 * Usage : php database/seed_module2.php
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

echo "=== Création du Module 2 : Boucles et collections ===\n\n";

// ================================================================
// Garde-fou : ne pas rejouer le script si le module existe déjà
// ================================================================
$check = $pdo->prepare("SELECT id FROM modules WHERE title = :title");
$check->execute(['title' => 'Boucles et collections']);
if ($check->fetch()) {
    echo "Le module 'Boucles et collections' existe déjà, arrêt du script.\n";
    exit(0);
}

// ================================================================
// MODULE 2
// ================================================================
$insertModule = $pdo->prepare(
    "INSERT INTO modules (title, description, order_index, is_published)
    VALUES (:title, :description, 2, 1)"
);
$insertModule->execute([
    'title' => 'Boucles et collections',
    'description' => "Deuxième module du curriculum JavaScript : boucles (for, while, for...of, for...in), tableaux et leurs méthodes courantes, objets et structures imbriquées (voir ROADMAP.md).",
]);
$moduleId = (int) $pdo->lastInsertId();
echo "✓ Module créé (id=$moduleId, order_index=2)\n";

// ================================================================
// CHAPITRES
// ================================================================
$insertChapitre = $pdo->prepare(
    "INSERT INTO chapitres (module_id, title, description, order_index, is_published)
    VALUES (:module_id, :title, :description, :order_index, 1)"
);

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Boucles',
    'description' => 'for, while, do...while, for...of, for...in, et le contrôle de flux avec break/continue.',
    'order_index' => 1,
]);
$chap1 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Tableaux indexés et méthodes courantes',
    'description' => "push/pop/splice, map/filter/find/slice, et reduce pour condenser un tableau en une valeur.",
    'order_index' => 2,
]);
$chap2 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Objets et structures imbriquées',
    'description' => "Accès par point/crochets, Object.keys/values/entries, et l'imbrication d'objets et de tableaux.",
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

// --- Chapitre 2.1 ---
$theory1 = <<<'HTML'
<p>Une boucle répète un bloc d'instructions tant qu'une condition reste vraie, sans avoir à dupliquer le code à la main. C'est l'outil de base pour traiter une série de valeurs — un tableau de produits, les lignes d'un fichier, les jours d'un mois...</p>

<figure class="theory-image size-medium align-center">
  <img src="/images/module2-chap1/cycle-boucle.svg" alt="Piste circulaire représentant le cycle d'une boucle : vérifier la condition, exécuter le bloc, avancer, puis revenir vérifier la condition ; une sortie quand la condition devient fausse" />
  <figcaption>Figure 1 : une boucle revient toujours vérifier sa condition avant de continuer</figcaption>
</figure>

<h2>for</h2>

<p>La boucle <code>for</code> regroupe en une seule ligne l'initialisation, la condition d'arrêt et le pas d'incrémentation :</p>

<pre><code>for (let i = 1; i <= 3; i++) {
  console.log(`Tour ${i}`);
}
// Tour 1
// Tour 2
// Tour 3</code></pre>

<p><code>let i = 1</code> (initialisation, une seule fois) · <code>i &lt;= 3</code> (condition vérifiée avant chaque tour) · <code>i++</code> (exécuté après chaque tour). Dès que la condition devient fausse, la boucle s'arrête.</p>

<h2>while et do...while</h2>

<p><code>while</code> évalue sa condition <strong>avant</strong> chaque tour — si elle est fausse dès le départ, le bloc ne s'exécute jamais :</p>

<pre><code>let stock = 3;

while (stock > 0) {
  console.log(`Il reste ${stock} article(s)`);
  stock--;
}</code></pre>

<p><code>do...while</code> évalue sa condition <strong>après</strong> le bloc : celui-ci s'exécute donc toujours au moins une fois, même si la condition est fausse dès le départ.</p>

<pre><code>let tentative = 0;

do {
  tentative++;
} while (tentative < 0); // jamais vrai, pourtant le bloc s'exécute une fois

console.log(tentative); // 1</code></pre>

<h2>for...of : parcourir les valeurs</h2>

<p><code>for...of</code> parcourt directement les <strong>valeurs</strong> d'un tableau (ou de tout itérable), sans avoir à gérer un index manuellement :</p>

<pre><code>const fruits = ["pomme", "banane", "kiwi"];

for (const fruit of fruits) {
  console.log(fruit);
}
// pomme
// banane
// kiwi</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module2-chap1/for-classique-vs-for-of.svg" alt="Comparaison d'un for classique avec un index i qui va de 0 à 2 et for(item of tableau) qui donne directement chaque valeur, sur le même tableau" />
  <figcaption>Figure 2 : même tableau, deux façons de le parcourir</figcaption>
</figure>

<h2>for...in : parcourir les clés</h2>

<p><code>for...in</code> parcourt les <strong>clés</strong> d'un objet (pas ses valeurs directement) — utile pour les objets, à éviter sur les tableaux (où <code>for...of</code> ou les méthodes du chapitre suivant sont plus adaptées) :</p>

<pre><code>const stock = { pommes: 10, bananes: 5 };

for (const fruit in stock) {
  console.log(`${fruit} : ${stock[fruit]}`);
}
// pommes : 10
// bananes : 5</code></pre>

<h2>break et continue</h2>

<ul>
  <li><code>break</code> arrête complètement la boucle</li>
  <li><code>continue</code> passe directement au tour suivant, sans exécuter le reste du bloc courant</li>
</ul>

<pre><code>for (const n of [1, 2, 3, 4, 5]) {
  if (n === 4) break;      // arrête tout dès qu'on atteint 4
  if (n % 2 === 0) continue; // saute les nombres pairs
  console.log(n);
}
// 1
// 3</code></pre>

<h2>En résumé</h2>

<ul>
  <li><code>for</code> : quand on connaît le nombre de tours ou qu'on a besoin d'un index</li>
  <li><code>while</code> / <code>do...while</code> : quand le nombre de tours dépend d'une condition qui évolue</li>
  <li><code>for...of</code> : la façon la plus simple de parcourir les valeurs d'un tableau</li>
  <li><code>for...in</code> : pour les clés d'un objet, pas pour les tableaux</li>
  <li><code>break</code> sort de la boucle, <code>continue</code> saute au tour suivant</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap1,
    'title' => 'Boucles',
    'content' => $theory1,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 2.2 ---
$theory2 = <<<'HTML'
<p>Un <strong>tableau</strong> (<code>Array</code>) range une série de valeurs dans l'ordre, chacune accessible par sa position — son <strong>indice</strong>, qui commence toujours à <code>0</code>.</p>

<pre><code>const fruits = ["pomme", "banane", "kiwi"];

fruits[0];        // "pomme"
fruits[2];        // "kiwi"
fruits.length;     // 3</code></pre>

<figure class="theory-image size-medium align-center">
  <img src="/images/module2-chap2/indices-tableau.svg" alt="Rangée de trois cases numérotées 0, 1, 2 représentant un tableau, chacune contenant une valeur" />
  <figcaption>Figure 1 : un tableau est une rangée de cases numérotées à partir de 0</figcaption>
</figure>

<h2>Méthodes qui modifient le tableau</h2>

<p>Ces méthodes changent le tableau <strong>en place</strong>, sans en créer un nouveau :</p>

<pre><code>const panier = ["pain"];

panier.push("lait");     // ajoute à la fin       → ["pain", "lait"]
panier.pop();              // retire le dernier      → ["pain"]
panier.unshift("oeufs");  // ajoute au début        → ["oeufs", "pain"]
panier.splice(1, 0, "beurre"); // insère à l'indice 1 → ["oeufs", "beurre", "pain"]</code></pre>

<h2>Méthodes qui créent un nouveau tableau</h2>

<p>Ces méthodes, elles, ne touchent <strong>jamais</strong> le tableau d'origine — elles retournent un nouveau tableau (ou une valeur) à partir de l'ancien :</p>

<ul>
  <li><code>map(fn)</code> — transforme chaque élément, retourne un tableau de même longueur</li>
  <li><code>filter(fn)</code> — ne garde que les éléments qui remplissent une condition</li>
  <li><code>find(fn)</code> — retourne le <strong>premier</strong> élément qui remplit une condition (ou <code>undefined</code>)</li>
  <li><code>slice(debut, fin)</code> — extrait une portion du tableau</li>
</ul>

<pre><code>const nombres = [1, 2, 3, 4, 5];

nombres.map(n => n * 2);       // [2, 4, 6, 8, 10]
nombres.filter(n => n % 2 === 0); // [2, 4]
nombres.find(n => n > 3);       // 4
nombres.slice(1, 3);             // [2, 3]

nombres; // toujours [1, 2, 3, 4, 5] — inchangé</code></pre>

<h2>reduce : tout condenser en une seule valeur</h2>

<p><code>reduce</code> parcourt le tableau en accumulant un résultat au fil des éléments — utile pour un total, une moyenne, ou construire n'importe quelle valeur unique à partir d'une liste :</p>

<pre><code>const prix = [12, 8, 25];

const total = prix.reduce((accumulateur, valeur) => accumulateur + valeur, 0);
console.log(total); // 45</code></pre>

<p><code>0</code> est la valeur de départ de l'accumulateur. À chaque tour, la fonction reçoit l'accumulateur courant et la valeur suivante, et retourne le nouvel accumulateur.</p>

<h2>Enchaîner les méthodes</h2>

<p>Comme <code>filter</code>, <code>map</code> et <code>reduce</code> retournent chacune quelque chose de nouveau, on peut les <strong>enchaîner</strong> pour construire un traitement en plusieurs étapes claires :</p>

<pre><code>const commandes = [
  { produit: "clavier", prix: 50, payee: true },
  { produit: "souris", prix: 20, payee: false },
  { produit: "ecran", prix: 150, payee: true },
];

const totalPaye = commandes
  .filter(c => c.payee)
  .map(c => c.prix)
  .reduce((total, prix) => total + prix, 0);

console.log(totalPaye); // 200</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module2-chap2/pipeline-filter-map-reduce.svg" alt="Schéma en tuyau : un tableau de commandes traverse filter(), puis map(), puis reduce(), la forme des données changeant à chaque étape jusqu'à un total unique" />
  <figcaption>Figure 2 : chaque méthode transforme la forme des données avant de la transmettre à la suivante</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li><code>push</code>/<code>pop</code>/<code>splice</code> modifient le tableau d'origine</li>
  <li><code>map</code>/<code>filter</code>/<code>find</code>/<code>slice</code> retournent quelque chose de nouveau, sans y toucher</li>
  <li><code>reduce</code> condense un tableau en une seule valeur, via un accumulateur</li>
  <li>Ces méthodes s'enchaînent naturellement pour exprimer un traitement en plusieurs étapes lisibles</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap2,
    'title' => 'Tableaux indexés et méthodes courantes',
    'content' => $theory2,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 2.3 ---
$theory3 = <<<'HTML'
<p>Un <strong>objet</strong> range des valeurs sous des <strong>clés</strong> nommées, plutôt que sous des indices numériques comme un tableau. C'est la structure à utiliser dès qu'on décrit "une chose avec plusieurs caractéristiques" — une personne, un produit, une commande...</p>

<pre><code>const livre = {
  titre: "Dune",
  auteur: "Frank Herbert",
  annee: 1965,
};

livre.titre;         // "Dune"    — accès par point
livre["auteur"];      // "Frank Herbert" — accès par crochets</code></pre>

<p>Les deux accès font la même chose. Les crochets deviennent nécessaires quand la clé est stockée dans une <strong>variable</strong>, ou contient un caractère que la notation par point n'accepte pas (un espace, un tiret...) :</p>

<pre><code>const cle = "auteur";
livre[cle]; // "Frank Herbert" — impossible d'écrire livre.cle (ça chercherait une clé "cle")</code></pre>

<h2>Object.keys, values, entries</h2>

<p>Trois méthodes pour transformer un objet en tableau, souvent utilisées juste avant une boucle <code>for...of</code> ou une méthode comme <code>map</code> :</p>

<pre><code>const stock = { pommes: 10, bananes: 5 };

Object.keys(stock);    // ["pommes", "bananes"]
Object.values(stock);   // [10, 5]
Object.entries(stock);  // [["pommes", 10], ["bananes", 5]]</code></pre>

<h2>Imbriquer objets et tableaux</h2>

<p>Un objet peut contenir un tableau, et un tableau peut contenir des objets — c'est même la structure la plus courante dès que les données se complexifient un peu, comme un carnet d'adresses qui contient une liste de contacts, chacun étant lui-même un objet.</p>

<pre><code>const carnet = {
  proprietaire: "Ana",
  contacts: [
    { nom: "Leo", telephone: "0601020304" },
    { nom: "Mia", telephone: "0605060708" },
  ],
};

carnet.contacts[0].nom; // "Leo"</code></pre>

<figure class="theory-image size-medium align-center">
  <img src="/images/module2-chap3/carnet-adresses.svg" alt="Un objet carnet d'adresses contenant un tableau de contacts, chaque contact étant lui-même un objet avec un nom et un téléphone" />
  <figcaption>Figure 1 : un objet peut contenir un tableau d'objets</figcaption>
</figure>

<h2>Accéder à une valeur profondément imbriquée</h2>

<p>Pour lire une valeur enfouie dans plusieurs niveaux, on enchaîne simplement les accès, de gauche à droite — chaque étape "ouvre" le niveau suivant :</p>

<pre><code>const commande = {
  id: 42,
  client: {
    nom: "Ana",
    adresse: {
      ville: "Lyon",
      codePostal: "69000",
    },
  },
};

commande.client.adresse.ville; // "Lyon"</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module2-chap3/chemin-acces-imbrique.svg" alt="Diagramme montrant le chemin commande puis client puis adresse puis ville, chaque flèche ouvrant un niveau d'objet supplémentaire jusqu'à la valeur finale" />
  <figcaption>Figure 2 : chaque point du chemin descend d'un niveau dans la structure</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li>Accès par point pour une clé fixe, par crochets pour une clé dynamique (variable)</li>
  <li><code>Object.keys</code>/<code>values</code>/<code>entries</code> transforment un objet en tableau exploitable</li>
  <li>Objets et tableaux s'imbriquent librement — un tableau d'objets, un objet contenant un tableau...</li>
  <li>Un accès profond n'est qu'une chaîne d'accès simples mis bout à bout</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap3,
    'title' => 'Objets et structures imbriquées',
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

// --- Chapitre 2.1 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Table de multiplication',
    'description' => "Affiche une table de multiplication avec une boucle for.",
    'instructions' => "Avec nombre = 4, affiche une ligne par résultat de 1 à 5, au format \"4 x 1 = 4\". Le programme doit afficher exactement :\n4 x 1 = 4\n4 x 2 = 8\n4 x 3 = 12\n4 x 4 = 16\n4 x 5 = 20",
    'starter_code' => <<<'JS'
const nombre = 4;

// TODO : boucle for de 1 à 5 qui affiche "4 x i = resultat"
JS,
    'solution_code' => <<<'JS'
const nombre = 4;

for (let i = 1; i <= 5; i++) {
  console.log(`${nombre} x ${i} = ${nombre * i}`);
}
JS,
    'expected_output' => "4 x 1 = 4\n4 x 2 = 8\n4 x 3 = 12\n4 x 4 = 16\n4 x 5 = 20",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 2.1 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Commandes sous seuil',
    'description' => "Parcours un tableau de commandes et arrête-toi dès qu'un montant dépasse un seuil.",
    'instructions' => "Avec commandes = [45, 60, 30, 120, 20] et seuil = 100, affiche \"Commande validée : <montant> €\" pour chaque commande tant qu'elle ne dépasse pas le seuil. Dès qu'une commande dépasse le seuil, affiche \"Commande de <montant> € dépasse le seuil, arrêt.\" et arrête la boucle (break) sans traiter les commandes suivantes. Le programme doit afficher exactement :\nCommande validée : 45 €\nCommande validée : 60 €\nCommande validée : 30 €\nCommande de 120 € dépasse le seuil, arrêt.",
    'starter_code' => <<<'JS'
const commandes = [45, 60, 30, 120, 20];
const seuil = 100;

// TODO : boucle for...of qui affiche "Commande validée : <montant> €"
// pour chaque commande, et s'arrête (break) dès qu'une commande dépasse
// le seuil — en affichant à la place "Commande de <montant> € dépasse
// le seuil, arrêt."
JS,
    'solution_code' => <<<'JS'
const commandes = [45, 60, 30, 120, 20];
const seuil = 100;

for (const montant of commandes) {
  if (montant > seuil) {
    console.log(`Commande de ${montant} € dépasse le seuil, arrêt.`);
    break;
  }
  console.log(`Commande validée : ${montant} €`);
}
JS,
    'expected_output' => "Commande validée : 45 €\nCommande validée : 60 €\nCommande validée : 30 €\nCommande de 120 € dépasse le seuil, arrêt.",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 2.2 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Produits en stock',
    'description' => "Filtre un tableau de produits pour ne garder que ceux disponibles.",
    'instructions' => "À partir du tableau produits fourni, garde uniquement les produits dont enStock est true, extrait leur nom, puis affiche-les séparés par \", \". Le programme doit afficher exactement :\nClavier, Ecran",
    'starter_code' => <<<'JS'
const produits = [
  { nom: "Clavier", enStock: true },
  { nom: "Souris", enStock: false },
  { nom: "Ecran", enStock: true },
];

// TODO : filtre les produits en stock, garde seulement leur nom,
// puis affiche-les séparés par ", " (ex: "Clavier, Ecran")
JS,
    'solution_code' => <<<'JS'
const produits = [
  { nom: "Clavier", enStock: true },
  { nom: "Souris", enStock: false },
  { nom: "Ecran", enStock: true },
];

const disponibles = produits.filter(p => p.enStock).map(p => p.nom);
console.log(disponibles.join(", "));
JS,
    'expected_output' => 'Clavier, Ecran',
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 2.2 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Résumés de commandes et total',
    'description' => "Combine map, un for...of et reduce sur le même tableau de commandes.",
    'instructions' => "À partir du tableau commandes fourni : construis un tableau de résumés au format \"<client> : <montant> €\" avec map, affiche chaque résumé avec un for...of, puis calcule le total des montants avec reduce et affiche \"Total : <total> €\". Le programme doit afficher exactement :\nAna : 45 €\nLeo : 30 €\nMia : 60 €\nTotal : 135 €",
    'starter_code' => <<<'JS'
const commandes = [
  { client: "Ana", montant: 45 },
  { client: "Leo", montant: 30 },
  { client: "Mia", montant: 60 },
];

// TODO : construis un tableau de résumés "<client> : <montant> €" via map,
// affiche chaque résumé avec un for...of, puis calcule et affiche
// "Total : <total> €" via reduce
JS,
    'solution_code' => <<<'JS'
const commandes = [
  { client: "Ana", montant: 45 },
  { client: "Leo", montant: 30 },
  { client: "Mia", montant: 60 },
];

const resumes = commandes.map(c => `${c.client} : ${c.montant} €`);

for (const resume of resumes) {
  console.log(resume);
}

const total = commandes.reduce((somme, c) => somme + c.montant, 0);
console.log(`Total : ${total} €`);
JS,
    'expected_output' => "Ana : 45 €\nLeo : 30 €\nMia : 60 €\nTotal : 135 €",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 2.3 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Extraire les auteurs',
    'description' => "Extrait une seule propriété de chaque objet d'un tableau.",
    'instructions' => "À partir du tableau livres fourni, extrait uniquement le champ auteur de chaque livre avec map, puis affiche-les séparés par \", \". Le programme doit afficher exactement :\nFrank Herbert, George Orwell, Isaac Asimov",
    'starter_code' => <<<'JS'
const livres = [
  { titre: "Dune", auteur: "Frank Herbert" },
  { titre: "1984", auteur: "George Orwell" },
  { titre: "Fondation", auteur: "Isaac Asimov" },
];

// TODO : extrait uniquement les auteurs, affiche-les séparés par ", "
JS,
    'solution_code' => <<<'JS'
const livres = [
  { titre: "Dune", auteur: "Frank Herbert" },
  { titre: "1984", auteur: "George Orwell" },
  { titre: "Fondation", auteur: "Isaac Asimov" },
];

const auteurs = livres.map(livre => livre.auteur);
console.log(auteurs.join(", "));
JS,
    'expected_output' => 'Frank Herbert, George Orwell, Isaac Asimov',
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 2.3 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Statistiques par catégorie',
    'description' => "Construis un objet de comptage à partir d'un tableau d'objets hétérogènes.",
    'instructions' => "À partir du tableau produits fourni, construis un objet statistiques qui compte le nombre de produits par catégorie, puis affiche chaque entrée au format \"<categorie> : <total>\" (une ligne par catégorie, dans l'ordre de première apparition). Le programme doit afficher exactement :\ninformatique : 3\nmobilier : 2\neclairage : 1",
    'starter_code' => <<<'JS'
const produits = [
  { nom: "Clavier", categorie: "informatique" },
  { nom: "Souris", categorie: "informatique" },
  { nom: "Chaise", categorie: "mobilier" },
  { nom: "Bureau", categorie: "mobilier" },
  { nom: "Lampe", categorie: "eclairage" },
  { nom: "Ecran", categorie: "informatique" },
];

// TODO : construis un objet "statistiques" qui compte le nombre de
// produits par catégorie, puis affiche chaque entrée au format
// "<categorie> : <total>" (une ligne par catégorie)
JS,
    'solution_code' => <<<'JS'
const produits = [
  { nom: "Clavier", categorie: "informatique" },
  { nom: "Souris", categorie: "informatique" },
  { nom: "Chaise", categorie: "mobilier" },
  { nom: "Bureau", categorie: "mobilier" },
  { nom: "Lampe", categorie: "eclairage" },
  { nom: "Ecran", categorie: "informatique" },
];

const statistiques = {};

for (const produit of produits) {
  const cat = produit.categorie;
  statistiques[cat] = (statistiques[cat] || 0) + 1;
}

for (const entree of Object.entries(statistiques)) {
  console.log(`${entree[0]} : ${entree[1]}`);
}
JS,
    'expected_output' => "informatique : 3\nmobilier : 2\neclairage : 1",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

echo "✓ 6 exercices créés\n";

echo "\n=== Module 2 créé avec succès (module id=$moduleId) ===\n";
