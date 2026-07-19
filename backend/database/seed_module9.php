<?php

/**
 * Crée le Module 9 ("Vers les frameworks modernes") du curriculum
 * JavaScript — voir ROADMAP.md à la racine du dépôt. Suppose que les
 * Modules 1 à 8 ont déjà été créés et occupent order_index=1 à 8.
 *
 * Ce module remplace le "Projet intégrateur" initialement prévu par le plan
 * (rédaction de cahier des charges, app de tâches avec rendu DOM réel et
 * localStorage) : ce plan cumulait trois problèmes structurels (exercices de
 * texte libre non notables, rendu DOM réel indisponible dans le Worker comme
 * pour M5, localStorage absent du scope d'un Worker dédié — spécification
 * navigateur, pas une restriction de ce projet). Sur demande explicite de
 * l'utilisateur, le module a été entièrement redéfini en conclusion de
 * cursus orientée sur le paysage des frameworks front-end actuels
 * (React/Vue/Angular/Svelte, méta-frameworks), avec un chapitre 9.2 qui fait
 * le pont pratique entre le vanilla JS déjà appris (fonctions, closures —
 * module 3) et le modèle par composants commun à tous ces frameworks.
 *
 * 9.1 et 9.3 sont théorie + illustrations seulement (contenu de panorama,
 * pas d'exercice de code à y rattacher naturellement). 9.2 a 2 exercices
 * notés, du JS pur sans DOM ni API navigateur, donc sans aucune limitation
 * de sandbox.
 *
 * expected_output des exercices 9.2 a été calculé hors-ligne en rejouant
 * solution_code à travers une réplique fidèle du Worker (Node vm, mêmes
 * globals setTimeout/console/Promise que le vrai sandbox).
 *
 * Usage : php database/seed_module9.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$pdo = Database::getConnection();

echo "=== Création du Module 9 : Vers les frameworks modernes ===\n\n";

// ================================================================
// Garde-fou : ne pas rejouer le script si le module existe déjà
// ================================================================
$check = $pdo->prepare("SELECT id FROM modules WHERE title = :title");
$check->execute(['title' => 'Vers les frameworks modernes']);
if ($check->fetch()) {
    echo "Le module 'Vers les frameworks modernes' existe déjà, arrêt du script.\n";
    exit(0);
}

// ================================================================
// MODULE 9
// ================================================================
$insertModule = $pdo->prepare(
    "INSERT INTO modules (title, description, order_index, is_published)
    VALUES (:title, :description, 9, 1)"
);
$insertModule->execute([
    'title' => 'Vers les frameworks modernes',
    'description' => "Neuvième et dernier module du curriculum JavaScript : pourquoi des frameworks, le modèle par composants (pont pratique avec le vanilla JS déjà appris), et panorama des frameworks front-end actuels (voir ROADMAP.md). Conclusion du cursus, pas un projet intégrateur.",
]);
$moduleId = (int) $pdo->lastInsertId();
echo "✓ Module créé (id=$moduleId, order_index=9)\n";

// ================================================================
// CHAPITRES
// ================================================================
$insertChapitre = $pdo->prepare(
    "INSERT INTO chapitres (module_id, title, description, order_index, is_published)
    VALUES (:module_id, :title, :description, :order_index, 1)"
);

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Pourquoi des frameworks ?',
    'description' => "Les limites du vanilla JS à grande échelle, la notion de composant et de réactivité, déclaratif vs impératif.",
    'order_index' => 1,
]);
$chap1 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Le modèle par composants',
    'description' => "Un composant comme fonction(props) -> rendu, état local via closure, recomposition, composition de composants.",
    'order_index' => 2,
]);
$chap2 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Panorama et choisir sa techno',
    'description' => "React, Vue, Angular, Svelte, les méta-frameworks (Next.js, Nuxt, SvelteKit, Astro), et comment orienter son choix.",
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

// --- Chapitre 9.1 ---
$theory1 = <<<'HTML'
<p>Après huit modules de JavaScript "vanilla" (sans outil externe), ce dernier module fait le pont vers l'écosystème professionnel actuel : les <strong>frameworks</strong> front-end. Avant de les survoler, il faut comprendre <strong>quel problème concret</strong> ils résolvent.</p>

<h2>Les limites du vanilla JS à grande échelle</h2>

<p>Le module 5 a montré comment sélectionner un élément et le modifier : <code>querySelector</code>, <code>textContent</code>, <code>classList</code>. Ça fonctionne très bien pour une poignée d'éléments. Le problème apparaît à l'échelle d'une vraie application : un panier d'achat avec une liste d'articles, un total, un compteur dans l'en-tête, un badge de disponibilité...</p>

<pre><code>function ajouterArticle(article) {
  panier.push(article);
  // et maintenant, il faut mettre à jour "à la main" CHAQUE endroit
  // de la page concerné par ce changement d'état :
  document.querySelector("#liste-panier").appendChild(creerLigneArticle(article));
  document.querySelector("#total").textContent = calculerTotal(panier);
  document.querySelector("#badge-compteur").textContent = panier.length;
  // ... et il ne faut en oublier aucun, à chaque endroit où l'état change
}</code></pre>

<p>Chaque nouvelle fonctionnalité ajoute des endroits du DOM à synchroniser manuellement avec l'état — une source d'erreurs qui grandit plus vite que l'application elle-même (un endroit oublié, et l'affichage se désynchronise silencieusement de l'état réel).</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module9-chap1/synchronisation-manuelle.svg" alt="Un état applicatif relié par de nombreux fils enchevêtrés à plusieurs endroits différents du DOM, chacun devant être mis à jour manuellement et séparément à chaque changement" />
  <figcaption>Figure 1 : plus une interface grandit, plus la synchronisation manuelle état → DOM devient difficile à maintenir sans oubli</figcaption>
</figure>

<h2>Composant et réactivité</h2>

<p>Tous les frameworks front-end modernes (React, Vue, Angular, Svelte...) reposent sur deux idées communes, justement pour éviter ce problème :</p>

<ul>
  <li><strong>Composant</strong> — une brique d'interface autonome et réutilisable, qui prend des données en entrée et produit un rendu</li>
  <li><strong>Réactivité</strong> — le framework observe l'état lui-même, et régénère automatiquement l'affichage concerné dès qu'il change, sans que le développeur ait à cibler manuellement chaque élément du DOM à mettre à jour</li>
</ul>

<h2>Déclaratif vs impératif</h2>

<p>Le vanilla JS du module 5 est <strong>impératif</strong> : le code décrit chaque étape nécessaire pour transformer le DOM (<em>comment</em> faire). Un framework est <strong>déclaratif</strong> : le code décrit à quoi l'interface doit ressembler <em>pour un état donné</em> (<em>quoi</em> afficher), et laisse le framework se charger du comment :</p>

<pre><code>// impératif (vanilla JS) : décrire les étapes de transformation
document.querySelector("#badge-compteur").textContent = panier.length;

// déclaratif (framework, pseudo-code) : décrire le résultat voulu
&lt;span&gt;{panier.length}&lt;/span&gt;
// le framework se charge lui-même de mettre à jour le DOM quand panier.length change</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module9-chap1/manuel-vs-declaratif.svg" alt="Comparaison avant/après : plusieurs lignes de mise à jour manuelle ciblée du DOM d'un côté, une seule expression déclarative de l'autre qui se met à jour automatiquement" />
  <figcaption>Figure 2 : le même résultat, en décrivant l'état voulu plutôt que les étapes pour y arriver</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li>Synchroniser manuellement l'état et le DOM devient une source d'erreurs à mesure qu'une application grandit</li>
  <li>Un composant est une brique d'interface autonome et réutilisable ; la réactivité automatise la mise à jour de l'affichage</li>
  <li>Le vanilla JS est impératif (décrire les étapes) ; les frameworks sont déclaratifs (décrire le résultat voulu)</li>
  <li>Ces deux idées — composant et réactivité — sont communes à tous les frameworks front-end modernes, seule la syntaxe change</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap1,
    'title' => 'Pourquoi des frameworks ?',
    'content' => $theory1,
    'order_index' => 1,
    'estimated_time' => 12,
]);

// --- Chapitre 9.2 ---
$theory2 = <<<'HTML'
<p>Le modèle par composants, au cœur de React, Vue, Angular et Svelte, peut se comprendre entièrement avec les outils déjà vus dans ce cours — fonctions, closures (module 3), objets. Ce chapitre construit ce modèle "à la main", en JavaScript pur, pour rendre concret ce qu'un framework automatise ensuite.</p>

<h2>Un composant, dans sa forme la plus simple</h2>

<p>Un composant peut se voir comme une fonction qui prend des données en entrée (des <strong>props</strong>) et retourne un rendu :</p>

<pre><code>function composantBouton({ texte, couleur }) {
  return `<button style="color: ${couleur}">${texte}</button>`;
}

composantBouton({ texte: "Valider", couleur: "vert" });
// '<button style="color: vert">Valider</button>'</code></pre>

<p>C'est, en plus simple (sans JSX ni vrai DOM), exactement le modèle utilisé par les composants fonction de React : une fonction, des props en entrée, un rendu en sortie.</p>

<figure class="theory-image size-medium align-center">
  <img src="/images/module9-chap2/composant-fonction-props-rendu.svg" alt="Un composant représenté comme une fonction : des props en entrée à gauche, un rendu produit en sortie à droite" />
  <figcaption>Figure 1 : un composant est une fonction — props en entrée, rendu en sortie</figcaption>
</figure>

<h2>Props : des données en entrée, jamais modifiées</h2>

<p>Comme des paramètres de fonction classiques, les props ne sont jamais modifiées <strong>à l'intérieur</strong> du composant qui les reçoit : elles décrivent ce qu'il doit afficher, pas ce qu'il peut changer.</p>

<h2>État local et recomposition</h2>

<p>Un composant peut aussi avoir un <strong>état interne</strong>, qui lui est propre — exactement le rôle que jouait une closure au module 3.2 (fabrique de compteur) :</p>

<pre><code>function creerCompteur(valeurInitiale = 0) {
  let valeur = valeurInitiale; // état privé, gardé par la closure

  function render() {
    return `<div>Compteur : ${valeur}</div>`;
  }

  function incrementer() {
    valeur++;
    return render(); // "recomposition" : nouveau rendu après changement d'état
  }

  return { render, incrementer };
}

const compteur = creerCompteur(0);
compteur.render();       // "<div>Compteur : 0</div>"
compteur.incrementer();
compteur.render();       // "<div>Compteur : 1</div>"</code></pre>

<p>Changer l'état puis rappeler <code>render()</code> pour obtenir le nouveau rendu, c'est exactement le principe de la <strong>recomposition</strong> qu'un vrai framework automatise : il détecte lui-même le changement d'état et déclenche le nouveau rendu, sans que <code>render()</code> ait besoin d'être rappelée manuellement.</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module9-chap2/recomposition-avant-apres.svg" alt="Un compteur affichant 0, puis après un changement d'état affichant 2 dans un nouveau rendu, illustrant la recomposition" />
  <figcaption>Figure 2 : changer l'état puis rappeler render() produit un nouveau rendu à jour</figcaption>
</figure>

<h2>Composition : des composants dans des composants</h2>

<p>Un composant peut utiliser un autre composant dans son propre rendu — c'est ainsi que des interfaces complexes se construisent à partir de petites briques simples et réutilisables :</p>

<pre><code>function composantCarte({ titre, compteur }) {
  return `<section><h2>${titre}</h2>${compteur.render()}</section>`;
}

composantCarte({ titre: "Panier", compteur });
// '<section><h2>Panier</h2><div>Compteur : 1</div></section>'</code></pre>

<h2>En résumé</h2>

<ul>
  <li>Un composant est une fonction qui prend des props et retourne un rendu</li>
  <li>Les props sont des données en entrée, jamais modifiées par le composant qui les reçoit</li>
  <li>Un état local (via closure) permet à un composant de retenir des données entre plusieurs rendus</li>
  <li>La recomposition, c'est changer l'état puis régénérer le rendu — un vrai framework le fait automatiquement</li>
  <li>Des composants peuvent en utiliser d'autres dans leur propre rendu, pour composer des interfaces complexes</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap2,
    'title' => 'Le modèle par composants',
    'content' => $theory2,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 9.3 ---
$theory3 = <<<'HTML'
<p>Voici un aperçu des frameworks et librairies front-end les plus utilisés aujourd'hui. Tous partagent les deux idées du chapitre 9.1 (composant, réactivité) — ce qui change d'un framework à l'autre, c'est surtout la <strong>syntaxe</strong> et la <strong>façon de détecter les changements d'état</strong>.</p>

<h2>React</h2>

<p>Une <strong>librairie</strong> (pas un framework complet), maintenue par Meta, la plus utilisée à ce jour — c'est ce que le frontend de ce projet utilise. Composants fonction, <strong>JSX</strong> (un mélange de HTML et de JavaScript dans le même fichier), et un <strong>Virtual DOM</strong> : React calcule d'abord les changements en mémoire, puis ne touche le vrai DOM que là où c'est nécessaire. Écosystème considérable (routing, gestion d'état, outils...).</p>

<h2>Vue</h2>

<p>Un framework <strong>progressif</strong> : sa syntaxe à base de templates reste proche du HTML classique, ce qui le rend facile à adopter petit à petit sur un projet existant. Sa réactivité est intégrée nativement — modifier une donnée met à jour l'affichage sans étape supplémentaire à écrire.</p>

<h2>Angular</h2>

<p>Un framework <strong>complet</strong> (routing, requêtes HTTP, formulaires, injection de dépendances déjà inclus), <strong>TypeScript</strong> dès le départ. Plus structuré et plus verbeux que React ou Vue, souvent choisi dans des contextes d'entreprise pour son cadre rigide et ses conventions imposées.</p>

<h2>Svelte</h2>

<p>Une approche différente : un <strong>compilateur</strong> qui transforme le code Svelte en JavaScript optimisé <strong>au moment du build</strong>, plutôt qu'un Virtual DOM exécuté dans le navigateur. Résultat : moins de code envoyé au navigateur, une syntaxe très proche du JS/HTML natif.</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module9-chap3/comparatif-frameworks.svg" alt="Comparatif visuel de React, Vue, Angular et Svelte selon deux axes : librairie ou framework complet, et Virtual DOM ou compilé" />
  <figcaption>Figure 1 : quatre approches différentes, deux idées communes (composant, réactivité)</figcaption>
</figure>

<h2>Les méta-frameworks : au-delà du composant</h2>

<p>Un <strong>méta-framework</strong> ajoute, par-dessus un framework de composants, ce dont une vraie application a aussi besoin : rendu serveur (SSR), génération de pages statiques (SSG), routing basé sur les fichiers, routes d'API...</p>

<ul>
  <li><strong>Next.js</strong> (React) — le plus utilisé, SSR/SSG, routing par fichiers, routes d'API intégrées</li>
  <li><strong>Nuxt</strong> (Vue) — l'équivalent pour l'écosystème Vue</li>
  <li><strong>SvelteKit</strong> (Svelte) — l'équivalent pour l'écosystème Svelte</li>
  <li><strong>Astro</strong> — orienté contenu (blogs, sites vitrine), agnostique du framework UI (peut mélanger React, Vue et Svelte dans le même projet), optimisé pour envoyer un minimum de JavaScript au navigateur</li>
</ul>

<figure class="theory-image size-large align-center">
  <img src="/images/module9-chap3/piles-meta-frameworks.svg" alt="Trois piles verticales : React surmonté de Next.js, Vue surmonté de Nuxt, Svelte surmonté de SvelteKit, chaque méta-framework ajoutant SSR, routing et API routes à son framework de base" />
  <figcaption>Figure 2 : chaque méta-framework ajoute SSR, routing et API routes à son framework de composants</figcaption>
</figure>

<h2>Comment choisir ?</h2>

<p>Il n'y a pas de "meilleur" framework dans l'absolu — le choix dépend du contexte : l'écosystème déjà en place dans une équipe, le besoin ou non de SEO/SSR, la taille prévue de l'application, la courbe d'apprentissage acceptable. Le socle commun appris dans ce module — composant, props, état, réactivité, déclaratif plutôt qu'impératif — se retrouve dans tous les frameworks : passer de l'un à l'autre est surtout un effort de syntaxe, pas un nouvel apprentissage conceptuel complet.</p>

<h2>En résumé</h2>

<ul>
  <li>React (librairie, JSX, Virtual DOM) est la plus utilisée et sert de base à ce projet</li>
  <li>Vue est progressif et proche du HTML ; Angular est complet et structuré (TypeScript) ; Svelte compile au build plutôt que d'utiliser un Virtual DOM</li>
  <li>Les méta-frameworks (Next.js, Nuxt, SvelteKit, Astro) ajoutent SSR/SSG, routing et API routes par-dessus un framework de composants</li>
  <li>Le socle conceptuel (composant, props, état, réactivité) est commun à tous — changer de framework est surtout syntaxique</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap3,
    'title' => 'Panorama et choisir sa techno',
    'content' => $theory3,
    'order_index' => 1,
    'estimated_time' => 15,
]);

echo "✓ 3 théories créées\n";

// ================================================================
// EXERCICES (chapitre 9.2 uniquement -- 9.1 et 9.3 sont théorie seule,
// voir commentaire en tête de fichier)
// ================================================================
$insertExercice = $pdo->prepare(
    "INSERT INTO exercices
    (chapitre_id, title, description, instructions, starter_code, solution_code, expected_output, difficulty, points, order_index)
    VALUES
    (:chapitre_id, :title, :description, :instructions, :starter_code, :solution_code, :expected_output, :difficulty, :points, :order_index)"
);

// --- Chapitre 9.2 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Un premier composant',
    'description' => "Écris un composant sous la forme d'une fonction qui prend des props et retourne un rendu.",
    'instructions' => "Écris une fonction composantBouton({ texte, couleur }) qui retourne la chaîne \"<button style=\\\"color: <couleur>\\\"><texte></button>\". Appelle-la avec { texte: \"Valider\", couleur: \"vert\" } puis avec { texte: \"Annuler\", couleur: \"rouge\" }, et affiche chaque résultat. Le programme doit afficher exactement :\n<button style=\"color: vert\">Valider</button>\n<button style=\"color: rouge\">Annuler</button>",
    'starter_code' => <<<'JS'
// TODO : fonction composantBouton({ texte, couleur }) qui retourne
// '<button style="color: <couleur>"><texte></button>'. Appelle-la deux fois
// (voir instructions) et affiche chaque résultat.
JS,
    'solution_code' => <<<'JS'
function composantBouton({ texte, couleur }) {
  return `<button style="color: ${couleur}">${texte}</button>`;
}

console.log(composantBouton({ texte: "Valider", couleur: "vert" }));
console.log(composantBouton({ texte: "Annuler", couleur: "rouge" }));
JS,
    'expected_output' => "<button style=\"color: vert\">Valider</button>\n<button style=\"color: rouge\">Annuler</button>",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 9.2 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'État local et composition',
    'description' => "Combine un état local (closure) et la composition de composants pour simuler une recomposition.",
    'instructions' => "Écris une fabrique creerCompteur(valeurInitiale = 0) qui retourne un objet { render, incrementer } : render() retourne \"<div>Compteur : <valeur></div>\", incrementer() augmente la valeur interne de 1 puis retourne render(). Écris un composant composantCarte({ titre, compteur }) qui retourne \"<section><h2><titre></h2><rendu du compteur></section>\". Crée un compteur à 0, affiche composantCarte({ titre: \"Panier\", compteur }), incrémente-le deux fois, puis affiche à nouveau composantCarte({ titre: \"Panier\", compteur }). Le programme doit afficher exactement :\n<section><h2>Panier</h2><div>Compteur : 0</div></section>\n<section><h2>Panier</h2><div>Compteur : 2</div></section>",
    'starter_code' => <<<'JS'
// TODO : creerCompteur(valeurInitiale = 0) -> { render, incrementer } avec
// un état privé (closure). composantCarte({ titre, compteur }) qui compose
// le rendu du compteur. Affiche la carte, incrémente 2 fois, affiche à
// nouveau (voir instructions pour le format exact).
JS,
    'solution_code' => <<<'JS'
function creerCompteur(valeurInitiale = 0) {
  let valeur = valeurInitiale;

  function render() {
    return `<div>Compteur : ${valeur}</div>`;
  }

  function incrementer() {
    valeur++;
    return render();
  }

  return { render, incrementer };
}

function composantCarte({ titre, compteur }) {
  return `<section><h2>${titre}</h2>${compteur.render()}</section>`;
}

const compteur = creerCompteur(0);
console.log(composantCarte({ titre: "Panier", compteur }));
compteur.incrementer();
compteur.incrementer();
console.log(composantCarte({ titre: "Panier", compteur }));
JS,
    'expected_output' => "<section><h2>Panier</h2><div>Compteur : 0</div></section>\n<section><h2>Panier</h2><div>Compteur : 2</div></section>",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

echo "✓ 2 exercices créés (chapitre 9.2 uniquement -- 9.1 et 9.3 sont théorie seule)\n";

echo "\n=== Module 9 créé avec succès (module id=$moduleId) ===\n";
