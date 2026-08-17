<?php

/**
 * Crée le Module 5 ("Le DOM et les événements") du curriculum JavaScript —
 * voir ROADMAP.md à la racine du dépôt. Suppose que les Modules 1 à 4 ont
 * déjà été créés et occupent order_index=1 à 4.
 *
 * Contrairement aux modules précédents, ce module n'a AUCUN exercice noté
 * pour l'instant : le sandbox d'exécution (frontend/src/utils/jsSandbox.js)
 * tourne dans un Web Worker sans `document`/`window` (isolation délibérée,
 * voir CLAUDE.md), donc du code utilisant document.querySelector,
 * addEventListener, etc. y lève systématiquement une ReferenceError — un
 * exercice de ce type ne pourrait jamais être corrigé correctement avec le
 * mécanisme actuel (comparaison de console.log à expected_output).
 *
 * Décision prise avec l'utilisateur : livrer la théorie + les illustrations
 * maintenant (valeur pédagogique indépendante de l'exécution), et traiter le
 * support d'un mode d'exécution DOM (probablement une iframe avec fixture
 * HTML par exercice + comparaison d'un innerHTML normalisé) comme un chantier
 * d'architecture séparé, à concevoir posément plutôt qu'en pleine session de
 * contenu — notamment parce que le choix initial du Worker plutôt qu'une
 * iframe était justifié par la fiabilité de worker.terminate() pour couper
 * une boucle infinie, garantie qu'une iframe n'offre pas nativement.
 *
 * Usage : php database/seed_module5.php
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

echo "=== Création du Module 5 : Le DOM et les événements ===\n\n";

// ================================================================
// Garde-fou : ne pas rejouer le script si le module existe déjà
// ================================================================
$check = $pdo->prepare("SELECT id FROM modules WHERE title = :title");
$check->execute(['title' => 'Le DOM et les événements']);
if ($check->fetch()) {
    echo "Le module 'Le DOM et les événements' existe déjà, arrêt du script.\n";
    exit(0);
}

// ================================================================
// MODULE 5
// ================================================================
$insertModule = $pdo->prepare(
    "INSERT INTO modules (title, description, order_index, is_published)
    VALUES (:title, :description, 5, 1)"
);
$insertModule->execute([
    'title' => 'Le DOM et les événements',
    'description' => "Cinquième module du curriculum JavaScript : sélectionner et manipuler le DOM, événements et interactivité, formulaires et validation côté client (voir ROADMAP.md). Premier module vraiment spécifique au navigateur, sans équivalent PHP.",
]);
$moduleId = (int) $pdo->lastInsertId();
echo "✓ Module créé (id=$moduleId, order_index=5)\n";

// ================================================================
// CHAPITRES
// ================================================================
$insertChapitre = $pdo->prepare(
    "INSERT INTO chapitres (module_id, title, description, order_index, is_published)
    VALUES (:module_id, :title, :description, :order_index, 1)"
);

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Sélectionner et manipuler le DOM',
    'description' => 'querySelector/querySelectorAll, lecture et écriture de contenu et d\'attributs, création et insertion d\'éléments.',
    'order_index' => 1,
]);
$chap1 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Événements et interactivité',
    'description' => "addEventListener, l'objet event, propagation (bubbling), et délégation d'événements.",
    'order_index' => 2,
]);
$chap2 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Formulaires et validation côté client',
    'description' => "L'événement submit, preventDefault, lecture des champs, validation avant envoi et retour visuel d'erreur.",
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

// --- Chapitre 5.1 ---
$theory1 = <<<'HTML'
<p>Le <strong>DOM</strong> (Document Object Model) est la représentation de la page HTML sous forme d'un arbre d'objets, manipulable depuis JavaScript. C'est le premier module de ce cours qui touche vraiment au navigateur : jusqu'ici, le code produisait du texte (<code>console.log</code>) — à partir de maintenant, il modifie la page elle-même.</p>

<h2>Sélectionner un élément</h2>

<p><code>document.querySelector(selecteur)</code> retourne le <strong>premier</strong> élément qui correspond au sélecteur CSS donné, ou <code>null</code> si aucun ne correspond. <code>querySelectorAll</code> retourne <strong>tous</strong> les éléments correspondants :</p>

<pre><code>document.querySelector("h1");        // le premier &lt;h1&gt; de la page
document.querySelector(".carte");     // le premier élément de classe "carte"
document.querySelector("#titre");     // l'élément d'id "titre"
document.querySelectorAll("li");      // tous les &lt;li&gt;, sous forme de NodeList</code></pre>

<figure class="theory-image size-medium align-center">
  <img src="/images/module5-chap1/arbre-dom-querySelector.svg" alt="Arbre du DOM avec un nœud h1 surligné, correspondant à l'appel document.querySelector('h1')" />
  <figcaption>Figure 1 : querySelector descend dans l'arbre du DOM jusqu'au premier nœud qui correspond</figcaption>
</figure>

<h2>Lire et écrire du contenu</h2>

<pre><code>const titre = document.querySelector("h1");

titre.textContent;              // lit le texte actuel
titre.textContent = "Bonjour";  // remplace le texte
titre.style.color = "blue";      // modifie un style directement</code></pre>

<p><code>textContent</code> traite tout comme du texte brut (sûr, pas d'interprétation HTML). <code>innerHTML</code> interprète le contenu comme du HTML — pratique pour insérer une structure, mais à ne jamais utiliser avec du texte non maîtrisé (risque d'injection).</p>

<h2>Lire et écrire des attributs</h2>

<pre><code>const lien = document.querySelector("a");

lien.getAttribute("href");           // lit un attribut
lien.setAttribute("href", "/accueil"); // écrit un attribut
lien.classList.add("actif");           // ajoute une classe CSS
lien.classList.remove("actif");        // retire une classe CSS
lien.classList.toggle("actif");        // bascule la classe (ajoute si absente, retire si présente)</code></pre>

<h2>Créer et insérer des éléments</h2>

<p>Pour ajouter un nouvel élément, on le crée en mémoire, on le configure, puis on l'insère dans l'arbre :</p>

<pre><code>const item = document.createElement("li");
item.textContent = "Nouvel article";

const liste = document.querySelector("ul");
liste.appendChild(item); // insère "item" comme dernier enfant de "liste"</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module5-chap1/avant-apres-insertion.svg" alt="Schéma avant/après de la page montrant l'insertion d'un nouvel élément li dans une liste ul existante" />
  <figcaption>Figure 2 : createElement crée un nœud isolé, appendChild l'insère dans l'arbre existant</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li><code>querySelector</code>/<code>querySelectorAll</code> sélectionnent un ou plusieurs éléments avec un sélecteur CSS</li>
  <li><code>textContent</code> pour du texte brut sûr, <code>innerHTML</code> pour insérer du HTML (avec prudence)</li>
  <li><code>getAttribute</code>/<code>setAttribute</code>/<code>classList</code> lisent et modifient les attributs et classes</li>
  <li><code>createElement</code> crée un élément isolé ; <code>appendChild</code> l'insère réellement dans la page</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap1,
    'title' => 'Sélectionner et manipuler le DOM',
    'content' => $theory1,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 5.2 ---
$theory2 = <<<'HTML'
<p>Un <strong>événement</strong> est une action qui se produit dans la page — un clic, une frappe clavier, un chargement d'image... Écouter des événements, c'est ce qui rend une page réellement <strong>interactive</strong>, plutôt que simplement affichée.</p>

<h2>addEventListener</h2>

<p><code>addEventListener(type, fonction)</code> attache une fonction (le <em>handler</em>) à un élément, exécutée à chaque fois que l'événement du type donné se produit sur cet élément :</p>

<pre><code>const bouton = document.querySelector("button");

bouton.addEventListener("click", function () {
  console.log("Bouton cliqué !");
});</code></pre>

<p>On peut attacher plusieurs écouteurs au même événement sur le même élément — ils s'exécutent tous, dans l'ordre où ils ont été ajoutés.</p>

<h2>L'objet event</h2>

<p>Le handler reçoit automatiquement un objet <code>event</code> décrivant ce qui s'est passé :</p>

<pre><code>bouton.addEventListener("click", function (event) {
  console.log(event.type);    // "click"
  console.log(event.target);  // l'élément exact qui a déclenché l'événement
});</code></pre>

<p><code>event.target</code> est particulièrement utile quand l'écouteur est posé sur un élément parent (voir délégation ci-dessous) : il indique précisément quel enfant a été cliqué.</p>

<h2>La propagation (bubbling)</h2>

<p>Par défaut, un événement ne s'arrête pas à l'élément cliqué : il <strong>remonte</strong> ensuite vers chacun de ses parents, jusqu'à la racine du document. C'est la <strong>propagation</strong> (ou <em>bubbling</em>).</p>

<figure class="theory-image size-medium align-center">
  <img src="/images/module5-chap2/bulles-propagation.svg" alt="Onde partant d'un clic sur un élément enfant et remontant l'arbre du DOM sous forme de bulles concentriques jusqu'à la racine" />
  <figcaption>Figure 1 : un clic sur un enfant remonte ensuite vers chacun de ses parents</figcaption>
</figure>

<h2>Délégation d'événements</h2>

<p>Grâce à la propagation, il n'est pas nécessaire de poser un écouteur sur <strong>chaque</strong> élément enfant : un seul écouteur sur le <strong>parent</strong> suffit, combiné à <code>event.target</code> pour identifier quel enfant a réellement été cliqué :</p>

<pre><code>const liste = document.querySelector("ul");

liste.addEventListener("click", function (event) {
  console.log(`Élément cliqué : ${event.target.textContent}`);
});
// fonctionne pour tous les &lt;li&gt; actuels ET pour ceux ajoutés plus tard</code></pre>

<p>Cette technique, la <strong>délégation d'événements</strong>, a deux avantages : moins d'écouteurs à gérer, et elle fonctionne automatiquement pour des éléments ajoutés <strong>après</strong> la mise en place de l'écouteur — utile pour des listes dynamiques.</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module5-chap2/ecouteur-unique-vs-multiple.svg" alt="Comparaison entre un écouteur posé sur chaque élément enfant individuellement et un seul écouteur délégué posé sur le parent" />
  <figcaption>Figure 2 : un seul écouteur délégué remplace un écouteur par élément</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li><code>addEventListener(type, fonction)</code> attache un comportement à un événement sur un élément</li>
  <li>Le handler reçoit un objet <code>event</code> ; <code>event.target</code> désigne l'élément exact concerné</li>
  <li>Un événement se propage (bubbling) de l'élément cliqué vers ses parents</li>
  <li>La délégation exploite cette propagation : un seul écouteur sur un parent gère tous ses enfants, présents et futurs</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap2,
    'title' => 'Événements et interactivité',
    'content' => $theory2,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 5.3 ---
$theory3 = <<<'HTML'
<p>Un formulaire est le point d'entrée le plus courant pour les données saisies par l'utilisateur — et donc l'endroit où une vérification côté client (avant même d'envoyer quoi que ce soit) évite les erreurs les plus fréquentes.</p>

<h2>L'événement submit et preventDefault</h2>

<p>Soumettre un formulaire déclenche un événement <code>submit</code> sur l'élément <code>&lt;form&gt;</code>. Par défaut, le navigateur recharge alors la page — un comportement presque toujours indésirable dès qu'on veut valider ou traiter les données en JavaScript :</p>

<pre><code>const formulaire = document.querySelector("form");

formulaire.addEventListener("submit", function (event) {
  event.preventDefault(); // empêche le rechargement de la page
  // ... traitement du formulaire ici
});</code></pre>

<h2>Lire les valeurs des champs</h2>

<pre><code>const champEmail = document.querySelector("#email");

champEmail.value; // la valeur actuellement saisie, sous forme de texte</code></pre>

<h2>Valider avant l'envoi</h2>

<p>La validation consiste à vérifier chaque champ avant de considérer le formulaire comme "prêt" — et à interrompre l'envoi si une règle n'est pas respectée :</p>

<pre><code>formulaire.addEventListener("submit", function (event) {
  event.preventDefault();

  const email = document.querySelector("#email").value.trim();

  if (email === "") {
    console.log("L'email est obligatoire.");
    return; // on s'arrête là, le formulaire n'est pas envoyé
  }

  console.log("Formulaire valide, envoi en cours...");
});</code></pre>

<figure class="theory-image size-medium align-center">
  <img src="/images/module5-chap3/garde-barriere-formulaire.svg" alt="Formulaire annoté avec un garde-barrière représentant la validation, placé avant la case envoyé" />
  <figcaption>Figure 1 : la validation agit comme un garde-barrière avant l'envoi effectif</figcaption>
</figure>

<h2>Retour visuel d'erreur</h2>

<p>Un message dans la console ne suffit pas pour un vrai formulaire : l'utilisateur a besoin d'un retour visible, idéalement ciblé sur le champ concerné (classe CSS d'erreur, message à côté du champ) :</p>

<pre><code>const champEmail = document.querySelector("#email");

if (champEmail.value.trim() === "") {
  champEmail.classList.add("erreur");
} else {
  champEmail.classList.remove("erreur");
  champEmail.classList.add("valide");
}</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module5-chap3/trois-etats-champ.svg" alt="Un champ de formulaire représenté dans ses trois états : neutre, erreur et valide, avec les styles associés à chacun" />
  <figcaption>Figure 2 : un champ de formulaire a généralement trois états visuels distincts</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li><code>event.preventDefault()</code> sur l'événement <code>submit</code> empêche le rechargement automatique de la page</li>
  <li><code>.value</code> lit le contenu actuel d'un champ de formulaire</li>
  <li>Valider, c'est vérifier chaque règle avant de considérer l'envoi comme autorisé</li>
  <li>Un retour visuel ciblé par champ (classe CSS, message) est préférable à une simple erreur en console</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap3,
    'title' => 'Formulaires et validation côté client',
    'content' => $theory3,
    'order_index' => 1,
    'estimated_time' => 15,
]);

echo "✓ 3 théories créées\n";

echo "\n=== Module 5 créé avec succès (module id=$moduleId) — sans exercices, voir commentaire en tête de fichier ===\n";
