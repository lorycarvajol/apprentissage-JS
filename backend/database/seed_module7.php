<?php

/**
 * Crée le Module 7 ("Programmation orientée objet") du curriculum
 * JavaScript — voir ROADMAP.md à la racine du dépôt. Suppose que les
 * Modules 1 à 6 ont déjà été créés et occupent order_index=1 à 6.
 *
 * Contrairement à M5/M6, ce module n'a nécessité aucun changement du
 * sandbox : les classes, champs privés (#), héritage et polymorphisme sont
 * du JS synchrone standard, sans DOM ni asynchrone.
 *
 * expected_output de chaque exercice a été calculé hors-ligne en rejouant
 * solution_code à travers une réplique fidèle du Worker (Node vm, mêmes
 * globals setTimeout/console/Promise que le vrai sandbox).
 *
 * Usage : php database/seed_module7.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$pdo = Database::getConnection();

echo "=== Création du Module 7 : Programmation orientée objet ===\n\n";

// ================================================================
// Garde-fou : ne pas rejouer le script si le module existe déjà
// ================================================================
$check = $pdo->prepare("SELECT id FROM modules WHERE title = :title");
$check->execute(['title' => 'Programmation orientée objet']);
if ($check->fetch()) {
    echo "Le module 'Programmation orientée objet' existe déjà, arrêt du script.\n";
    exit(0);
}

// ================================================================
// MODULE 7
// ================================================================
$insertModule = $pdo->prepare(
    "INSERT INTO modules (title, description, order_index, is_published)
    VALUES (:title, :description, 7, 1)"
);
$insertModule->execute([
    'title' => 'Programmation orientée objet',
    'description' => "Septième module du curriculum JavaScript : objets, classes et constructeurs, encapsulation et héritage, polymorphisme et prototypes (voir ROADMAP.md). Un module parmi neuf, pas l'organisation centrale du curriculum.",
]);
$moduleId = (int) $pdo->lastInsertId();
echo "✓ Module créé (id=$moduleId, order_index=7)\n";

// ================================================================
// CHAPITRES
// ================================================================
$insertChapitre = $pdo->prepare(
    "INSERT INTO chapitres (module_id, title, description, order_index, is_published)
    VALUES (:module_id, :title, :description, :order_index, 1)"
);

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Objets, classes et constructeurs',
    'description' => "Objets littéraux vs classes, class/constructor, new, this, et plusieurs instances d'une même classe.",
    'order_index' => 1,
]);
$chap1 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Encapsulation et héritage',
    'description' => 'Champs privés (#), get/set, extends, et super().',
    'order_index' => 2,
]);
$chap2 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Polymorphisme et prototypes',
    'description' => "Duck typing, tableau polymorphe, et la chaîne de prototypes en bref.",
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

// --- Chapitre 7.1 ---
$theory1 = <<<'HTML'
<p>Jusqu'ici, les objets de ce cours étaient des objets <strong>littéraux</strong> (<code>{ nom: "Ana" }</code>), écrits un par un. Dès qu'on veut créer plusieurs objets qui partagent la même structure et le même comportement, il devient plus efficace de définir un <strong>moule</strong> — une <strong>classe</strong> — plutôt que de répéter chaque objet à la main.</p>

<h2>Objet littéral vs classe</h2>

<pre><code>// objet littéral : une seule "personne", écrite à la main
const ana = { nom: "Ana", age: 28 };

// classe : un moule réutilisable pour fabriquer des personnes
class Personne {
  constructor(nom, age) {
    this.nom = nom;
    this.age = age;
  }
}

const leo = new Personne("Léo", 34); // une instance du moule Personne</code></pre>

<h2>class, constructor et new</h2>

<p>Le <code>constructor</code> est une méthode spéciale, appelée automatiquement à chaque <code>new NomDeClasse(...)</code> : c'est là que les propriétés de l'instance sont initialisées.</p>

<pre><code>class Personne {
  constructor(nom, age) {
    this.nom = nom;
    this.age = age;
  }

  seSaluer() {
    return `Bonjour, je m'appelle ${this.nom} et j'ai ${this.age} ans.`;
  }
}

const ana = new Personne("Ana", 28);
ana.seSaluer(); // "Bonjour, je m'appelle Ana et j'ai 28 ans."</code></pre>

<figure class="theory-image size-medium align-center">
  <img src="/images/module7-chap1/moule-instances.svg" alt="Un moule Personne relié à plusieurs instances (Ana, Léo) ayant chacune leurs propres valeurs de nom et d'âge" />
  <figcaption>Figure 1 : une classe est un moule ; chaque new produit une instance indépendante</figcaption>
</figure>

<h2>this</h2>

<p><code>this</code>, à l'intérieur d'une méthode, désigne l'instance sur laquelle la méthode a été appelée — <strong>pas</strong> la classe elle-même. C'est ce qui permet à la même méthode <code>seSaluer()</code> de produire un résultat différent pour chaque instance :</p>

<pre><code>const ana = new Personne("Ana", 28);
const leo = new Personne("Léo", 34);

ana.seSaluer(); // this = ana à l'intérieur de cet appel
leo.seSaluer(); // this = leo à l'intérieur de cet appel</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module7-chap1/this-vers-instance.svg" alt="Flèche partant d'un appel de méthode seSaluer() vers sa boîte-instance correspondante en mémoire, illustrant que this désigne l'instance sur laquelle la méthode a été appelée" />
  <figcaption>Figure 2 : this pointe toujours vers l'instance qui a appelé la méthode, jamais vers la classe</figcaption>
</figure>

<h2>Plusieurs instances, une seule classe</h2>

<p>Chaque <code>new</code> crée une instance totalement indépendante : modifier les propriétés de l'une n'affecte jamais les autres, même si elles partagent la même classe et les mêmes méthodes.</p>

<h2>En résumé</h2>

<ul>
  <li>Un objet littéral décrit une seule valeur ; une classe est un moule réutilisable pour en fabriquer plusieurs</li>
  <li><code>constructor</code> s'exécute automatiquement à chaque <code>new</code>, pour initialiser l'instance</li>
  <li><code>this</code> désigne l'instance courante à l'intérieur d'une méthode</li>
  <li>Deux instances de la même classe restent indépendantes l'une de l'autre</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap1,
    'title' => 'Objets, classes et constructeurs',
    'content' => $theory1,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 7.2 ---
$theory2 = <<<'HTML'
<p>Deux idées structurent la plupart des classes un peu élaborées : l'<strong>encapsulation</strong> (protéger l'état interne d'un objet) et l'<strong>héritage</strong> (réutiliser le comportement d'une classe pour en construire une plus spécifique).</p>

<h2>Champs privés (#)</h2>

<p>Un champ préfixé par <code>#</code> n'est accessible <strong>que depuis l'intérieur de la classe</strong> — inaccessible et invisible depuis l'extérieur, y compris par erreur :</p>

<pre><code>class CompteBancaire {
  #solde;

  constructor(soldeInitial) {
    this.#solde = soldeInitial;
  }

  deposer(montant) {
    this.#solde += montant;
    return this.#solde;
  }

  consulterSolde() {
    return this.#solde;
  }
}

const compte = new CompteBancaire(100);
compte.consulterSolde(); // 100 — accès via une méthode publique
compte.#solde;            // SyntaxError — inaccessible depuis l'extérieur</code></pre>

<figure class="theory-image size-medium align-center">
  <img src="/images/module7-chap2/cadenas-champ-prive.svg" alt="Boîte fermée par un cadenas autour du champ privé #solde, avec une seule porte représentant les méthodes publiques permettant d'y accéder indirectement" />
  <figcaption>Figure 1 : un champ privé ne s'atteint que par les méthodes publiques que la classe expose elle-même</figcaption>
</figure>

<h2>get et set</h2>

<p><code>get</code>/<code>set</code> définissent des méthodes qui se comportent comme de simples propriétés à l'usage, tout en gardant un contrôle sur la lecture ou l'écriture :</p>

<pre><code>class CompteBancaire {
  #solde;
  constructor(soldeInitial) { this.#solde = soldeInitial; }

  get solde() {
    return this.#solde; // lu comme une propriété : compte.solde
  }
}

const compte = new CompteBancaire(100);
compte.solde; // 100 — appelle get solde() en coulisses, sans parenthèses</code></pre>

<h2>extends et super()</h2>

<p><code>extends</code> fait hériter une classe des propriétés et méthodes d'une autre. <code>super(...)</code>, dans le constructeur de la classe fille, appelle le constructeur de la classe parente :</p>

<pre><code>class Animal {
  constructor(nom) {
    this.nom = nom;
  }

  seDeplacer() {
    return `${this.nom} se déplace.`;
  }
}

class Chien extends Animal {
  constructor(nom, race) {
    super(nom); // appelle le constructeur d'Animal, initialise this.nom
    this.race = race;
  }

  seDeplacer() {
    return `${this.nom} (${this.race}) court sur ses quatre pattes.`; // redéfinit la méthode héritée
  }
}</code></pre>

<p>Redéfinir une méthode héritée dans la classe fille (comme <code>seDeplacer()</code> ci-dessus) s'appelle l'<strong>override</strong> : la version de la classe fille remplace celle du parent pour ses instances.</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module7-chap2/arbre-heritage.svg" alt="Arbre avec Animal à la racine et deux branches Chien et Chat, chacune reliée par une flèche « hérite de »" />
  <figcaption>Figure 2 : plusieurs classes filles peuvent hériter de la même classe parente, chacune redéfinissant ce qui lui est propre</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li>Un champ <code>#privé</code> n'est accessible que depuis l'intérieur de sa propre classe</li>
  <li><code>get</code>/<code>set</code> exposent un accès contrôlé, avec la syntaxe d'une simple propriété</li>
  <li><code>extends</code> fait hériter d'une classe parente ; <code>super(...)</code> appelle son constructeur</li>
  <li>Une classe fille peut redéfinir (override) une méthode héritée pour se comporter différemment</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap2,
    'title' => 'Encapsulation et héritage',
    'content' => $theory2,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 7.3 ---
$theory3 = <<<'HTML'
<p>Le <strong>polymorphisme</strong>, c'est la capacité à traiter des objets de classes différentes de façon uniforme, du moment qu'ils exposent la même méthode — sans avoir besoin de savoir, au moment de l'appel, à quelle classe exacte ils appartiennent.</p>

<h2>Tableau polymorphe</h2>

<pre><code>class Cercle {
  constructor(rayon) { this.rayon = rayon; }
  aire() { return Math.PI * this.rayon ** 2; }
}

class Carre {
  constructor(cote) { this.cote = cote; }
  aire() { return this.cote ** 2; }
}

const formes = [new Cercle(3), new Carre(4)];

for (const forme of formes) {
  console.log(forme.aire()); // fonctionne pour chaque forme, sans savoir laquelle c'est
}</code></pre>

<p>La boucle ne teste jamais "est-ce un Cercle ou un Carré ?" — elle appelle simplement <code>.aire()</code>, et chaque objet sait exécuter <strong>sa propre</strong> version de cette méthode.</p>

<figure class="theory-image size-medium align-center">
  <img src="/images/module7-chap3/boucle-formes-heterogenes.svg" alt="Boucle circulaire au-dessus d'une rangée de formes différentes (cercle, carré, triangle), toutes reliées à la même étiquette .aire()" />
  <figcaption>Figure 1 : la boucle appelle la même méthode sur des formes différentes, sans distinction de classe</figcaption>
</figure>

<h2>Duck typing</h2>

<p>JavaScript ne vérifie jamais la classe exacte d'un objet avant d'appeler une méthode dessus — seulement que la méthode <strong>existe</strong>. C'est le <strong>duck typing</strong> : "si ça marche comme un canard et que ça cancane comme un canard, c'est un canard" — peu importe sa classe déclarée :</p>

<pre><code>function afficher(objet) {
  console.log(objet.versTexte()); // fonctionne pour N'IMPORTE QUEL objet qui a versTexte()
}

afficher(new Produit("Clavier", 50));   // fonctionne
afficher(new Utilisateur("ana92"));     // fonctionne aussi, classe totalement différente</code></pre>

<h2>La chaîne de prototypes, en bref</h2>

<p>Derrière chaque <code>class</code>, JavaScript utilise en réalité un mécanisme plus ancien : le <strong>prototype</strong>. Chaque instance est reliée au prototype de sa classe (qui contient ses méthodes), lui-même relié au prototype de la classe parente en cas d'héritage, et ainsi de suite jusqu'à <code>Object.prototype</code> :</p>

<pre><code>const rex = new Chien("Rex", "labrador");

rex.seDeplacer();          // trouvée sur Chien.prototype
Object.getPrototypeOf(rex) === Chien.prototype;            // true
Object.getPrototypeOf(Chien.prototype) === Animal.prototype; // true (grâce à extends)</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module7-chap3/chaine-prototypes.svg" alt="Chaîne instance vers Chien.prototype vers Animal.prototype vers Object.prototype vers null, illustrant la recherche d'une méthode le long de la chaîne de prototypes" />
  <figcaption>Figure 2 : quand une méthode n'est pas trouvée sur l'instance, JavaScript la cherche en remontant la chaîne de prototypes</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li>Le polymorphisme permet de traiter des objets de classes différentes de façon uniforme, via une méthode commune</li>
  <li>Le duck typing : JavaScript vérifie qu'une méthode existe, jamais la classe exacte de l'objet</li>
  <li><code>class</code>/<code>extends</code> reposent sur un mécanisme de prototypes : chaque instance est reliée au prototype de sa classe</li>
  <li>Une méthode absente de l'instance est cherchée en remontant la chaîne de prototypes, jusqu'à <code>Object.prototype</code></li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap3,
    'title' => 'Polymorphisme et prototypes',
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

// --- Chapitre 7.1 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Classe Personne',
    'description' => "Crée une classe Personne avec un constructeur et une méthode seSaluer().",
    'instructions' => "Écris une classe Personne(nom, age) avec une méthode seSaluer() qui retourne \"Bonjour, je m'appelle <nom> et j'ai <age> ans.\". Crée deux instances (Ana, 28 ans et Léo, 34 ans) et affiche le résultat de seSaluer() pour chacune. Le programme doit afficher exactement :\nBonjour, je m'appelle Ana et j'ai 28 ans.\nBonjour, je m'appelle Léo et j'ai 34 ans.",
    'starter_code' => <<<'JS'
// TODO : classe Personne(nom, age) avec une méthode seSaluer() qui retourne
// "Bonjour, je m'appelle <nom> et j'ai <age> ans.". Crée deux instances et
// affiche le résultat de seSaluer() pour chacune.
JS,
    'solution_code' => <<<'JS'
class Personne {
  constructor(nom, age) {
    this.nom = nom;
    this.age = age;
  }

  seSaluer() {
    return `Bonjour, je m'appelle ${this.nom} et j'ai ${this.age} ans.`;
  }
}

const ana = new Personne("Ana", 28);
const leo = new Personne("Léo", 34);

console.log(ana.seSaluer());
console.log(leo.seSaluer());
JS,
    'expected_output' => "Bonjour, je m'appelle Ana et j'ai 28 ans.\nBonjour, je m'appelle Léo et j'ai 34 ans.",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 7.1 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Classe Rectangle',
    'description' => "Crée une classe Rectangle avec des méthodes perimetre() et aire().",
    'instructions' => "Écris une classe Rectangle(largeur, hauteur) avec deux méthodes : perimetre() (2 * (largeur + hauteur)) et aire() (largeur * hauteur). Crée deux instances (4x3 et 10x2), et pour chacune affiche \"<largeur>x<hauteur> : périmètre <perimetre>, aire <aire>\". Le programme doit afficher exactement :\n4x3 : périmètre 14, aire 12\n10x2 : périmètre 24, aire 20",
    'starter_code' => <<<'JS'
// TODO : classe Rectangle(largeur, hauteur) avec perimetre() et aire().
// Crée deux instances (4x3 et 10x2) et affiche pour chacune
// "<largeur>x<hauteur> : périmètre <perimetre>, aire <aire>"
JS,
    'solution_code' => <<<'JS'
class Rectangle {
  constructor(largeur, hauteur) {
    this.largeur = largeur;
    this.hauteur = hauteur;
  }

  perimetre() {
    return 2 * (this.largeur + this.hauteur);
  }

  aire() {
    return this.largeur * this.hauteur;
  }
}

const rectangles = [new Rectangle(4, 3), new Rectangle(10, 2)];

for (const r of rectangles) {
  console.log(`${r.largeur}x${r.hauteur} : périmètre ${r.perimetre()}, aire ${r.aire()}`);
}
JS,
    'expected_output' => "4x3 : périmètre 14, aire 12\n10x2 : périmètre 24, aire 20",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 7.2 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Compte bancaire avec solde privé',
    'description' => "Encapsule le solde d'un compte bancaire dans un champ privé.",
    'instructions' => "Écris une classe CompteBancaire(soldeInitial) avec un champ privé #solde, et deux méthodes : deposer(montant) (ajoute au solde) et retirer(montant) (retire du solde). Ajoute une méthode consulterSolde() qui retourne le solde actuel. Affiche le solde initial, dépose 50, affiche le solde, retire 30, affiche le solde. Le programme doit afficher exactement :\nSolde initial : 100 €\nAprès dépôt : 150 €\nAprès retrait : 120 €",
    'starter_code' => <<<'JS'
// TODO : classe CompteBancaire(soldeInitial) avec #solde privé,
// deposer(montant), retirer(montant), consulterSolde(). Affiche le solde
// avant/après un dépôt de 50 et un retrait de 30.
JS,
    'solution_code' => <<<'JS'
class CompteBancaire {
  #solde;

  constructor(soldeInitial) {
    this.#solde = soldeInitial;
  }

  deposer(montant) {
    this.#solde += montant;
    return this.#solde;
  }

  retirer(montant) {
    if (montant > this.#solde) {
      throw new Error("Solde insuffisant");
    }
    this.#solde -= montant;
    return this.#solde;
  }

  consulterSolde() {
    return this.#solde;
  }
}

const compte = new CompteBancaire(100);
console.log(`Solde initial : ${compte.consulterSolde()} €`);
compte.deposer(50);
console.log(`Après dépôt : ${compte.consulterSolde()} €`);
compte.retirer(30);
console.log(`Après retrait : ${compte.consulterSolde()} €`);
JS,
    'expected_output' => "Solde initial : 100 €\nAprès dépôt : 150 €\nAprès retrait : 120 €",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 7.2 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Héritage Animal / Chien / Poisson',
    'description' => "Utilise extends et super() pour redéfinir le comportement de classes filles.",
    'instructions' => "Écris une classe Animal(nom) avec une méthode seDeplacer() qui retourne \"<nom> se déplace.\". Écris Chien(nom, race), qui hérite d'Animal (super(nom)), avec sa propre propriété race et une méthode seDeplacer() redéfinie retournant \"<nom> (<race>) court sur ses quatre pattes.\". Écris Poisson(nom), qui hérite aussi d'Animal, avec seDeplacer() redéfinie retournant \"<nom> nage.\". Crée un Animal(\"Créature\"), un Chien(\"Rex\", \"labrador\") et un Poisson(\"Nemo\"), place-les dans un tableau, et affiche le résultat de seDeplacer() pour chacun. Le programme doit afficher exactement :\nCréature se déplace.\nRex (labrador) court sur ses quatre pattes.\nNemo nage.",
    'starter_code' => <<<'JS'
// TODO : classe Animal(nom) avec seDeplacer(). Chien(nom, race) extends
// Animal (super(nom)) avec seDeplacer() redéfinie. Poisson(nom) extends
// Animal avec seDeplacer() redéfinie. Tableau des trois, boucle qui affiche
// seDeplacer() pour chacun.
JS,
    'solution_code' => <<<'JS'
class Animal {
  constructor(nom) {
    this.nom = nom;
  }

  seDeplacer() {
    return `${this.nom} se déplace.`;
  }
}

class Chien extends Animal {
  constructor(nom, race) {
    super(nom);
    this.race = race;
  }

  seDeplacer() {
    return `${this.nom} (${this.race}) court sur ses quatre pattes.`;
  }
}

class Poisson extends Animal {
  seDeplacer() {
    return `${this.nom} nage.`;
  }
}

const animaux = [new Animal("Créature"), new Chien("Rex", "labrador"), new Poisson("Nemo")];

for (const animal of animaux) {
  console.log(animal.seDeplacer());
}
JS,
    'expected_output' => "Créature se déplace.\nRex (labrador) court sur ses quatre pattes.\nNemo nage.",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 7.3 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Formes hétérogènes',
    'description' => "Parcourt un tableau de formes de classes différentes en appelant .aire() sur chacune.",
    'instructions' => "Écris une classe Cercle(rayon) avec une méthode aire() (Math.PI * rayon ** 2) et une classe Carre(cote) avec une méthode aire() (cote ** 2). Crée un tableau contenant un Cercle(3) et un Carre(4), puis pour chaque forme affiche \"Aire : <aire arrondie à 2 décimales>\" (toFixed(2)). Le programme doit afficher exactement :\nAire : 28.27\nAire : 16.00",
    'starter_code' => <<<'JS'
// TODO : classe Cercle(rayon) et Carre(cote), chacune avec une méthode
// aire(). Tableau [Cercle(3), Carre(4)], boucle qui affiche
// "Aire : <aire.toFixed(2)>" pour chaque forme.
JS,
    'solution_code' => <<<'JS'
class Cercle {
  constructor(rayon) {
    this.rayon = rayon;
  }

  aire() {
    return Math.PI * this.rayon ** 2;
  }
}

class Carre {
  constructor(cote) {
    this.cote = cote;
  }

  aire() {
    return this.cote ** 2;
  }
}

const formes = [new Cercle(3), new Carre(4)];

for (const forme of formes) {
  console.log(`Aire : ${forme.aire().toFixed(2)}`);
}
JS,
    'expected_output' => "Aire : 28.27\nAire : 16.00",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 7.3 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Fonction générique par duck typing',
    'description' => "Écris une fonction générique qui accepte tout objet ayant une méthode .versTexte(), quelle que soit sa classe.",
    'instructions' => "Écris une classe Produit(nom, prix) avec une méthode versTexte() retournant \"<nom> - <prix> €\", et une classe Utilisateur(pseudo) avec une méthode versTexte() retournant \"Utilisateur : <pseudo>\" — deux classes totalement indépendantes. Écris une fonction afficher(objet) qui affiche objet.versTexte(), sans connaître sa classe. Applique-la à un Produit(\"Clavier\", 50) et à un Utilisateur(\"ana92\"). Le programme doit afficher exactement :\nClavier - 50 €\nUtilisateur : ana92",
    'starter_code' => <<<'JS'
// TODO : classe Produit(nom, prix) et Utilisateur(pseudo), chacune avec sa
// propre méthode versTexte(). Fonction afficher(objet) qui affiche
// objet.versTexte(), applicable aux deux classes sans distinction.
JS,
    'solution_code' => <<<'JS'
class Produit {
  constructor(nom, prix) {
    this.nom = nom;
    this.prix = prix;
  }

  versTexte() {
    return `${this.nom} - ${this.prix} €`;
  }
}

class Utilisateur {
  constructor(pseudo) {
    this.pseudo = pseudo;
  }

  versTexte() {
    return `Utilisateur : ${this.pseudo}`;
  }
}

function afficher(objet) {
  console.log(objet.versTexte());
}

const elements = [new Produit("Clavier", 50), new Utilisateur("ana92")];

for (const element of elements) {
  afficher(element);
}
JS,
    'expected_output' => "Clavier - 50 €\nUtilisateur : ana92",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

echo "✓ 6 exercices créés\n";

echo "\n=== Module 7 créé avec succès (module id=$moduleId) ===\n";
