<?php

/**
 * Crée le Module 8 ("JavaScript moderne et bonnes pratiques") du curriculum
 * JavaScript — voir ROADMAP.md à la racine du dépôt. Suppose que les
 * Modules 1 à 7 ont déjà été créés et occupent order_index=1 à 7.
 *
 * Le chapitre 8.2 ("Modules ES6") n'a AUCUN exercice noté, pour la même
 * raison structurelle que M5 : le sandbox (frontend/src/utils/jsSandbox.js)
 * exécute le code soumis comme une seule chaîne via `new Function(code)`,
 * qui n'est pas un corps de module — `import`/`export` y lèvent une
 * SyntaxError immédiate (vérifié : "Unexpected token 'export'" et "Cannot
 * use import statement outside a module"). Contrairement au correctif de M6
 * (un changement borné, dans un seul fichier), bien faire du multi-fichiers
 * demanderait un vrai support de plusieurs fichiers par exercice (schéma +
 * ExerciceManager.jsx) et un chargement de modules réel (import() dynamique
 * + réécriture des spécificateurs) — un chantier plus large, volontairement
 * pas fait ici. 8.1 et 8.3 sont du JS synchrone standard, sans ce problème.
 *
 * expected_output de chaque exercice a été calculé hors-ligne en rejouant
 * solution_code à travers une réplique fidèle du Worker (Node vm, mêmes
 * globals setTimeout/console/Promise que le vrai sandbox).
 *
 * Usage : php database/seed_module8.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$pdo = Database::getConnection();

echo "=== Création du Module 8 : JavaScript moderne et bonnes pratiques ===\n\n";

// ================================================================
// Garde-fou : ne pas rejouer le script si le module existe déjà
// ================================================================
$check = $pdo->prepare("SELECT id FROM modules WHERE title = :title");
$check->execute(['title' => 'JavaScript moderne et bonnes pratiques']);
if ($check->fetch()) {
    echo "Le module 'JavaScript moderne et bonnes pratiques' existe déjà, arrêt du script.\n";
    exit(0);
}

// ================================================================
// MODULE 8
// ================================================================
$insertModule = $pdo->prepare(
    "INSERT INTO modules (title, description, order_index, is_published)
    VALUES (:title, :description, 8, 1)"
);
$insertModule->execute([
    'title' => 'JavaScript moderne et bonnes pratiques',
    'description' => "Huitième module du curriculum JavaScript : déstructuration/spread/rest, modules ES6, et gestion des erreurs (voir ROADMAP.md). Le chapitre 8.2 (modules ES6) est théorie + illustrations seulement, voir commentaire en tête de ce script.",
]);
$moduleId = (int) $pdo->lastInsertId();
echo "✓ Module créé (id=$moduleId, order_index=8)\n";

// ================================================================
// CHAPITRES
// ================================================================
$insertChapitre = $pdo->prepare(
    "INSERT INTO chapitres (module_id, title, description, order_index, is_published)
    VALUES (:module_id, :title, :description, :order_index, 1)"
);

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Destructuring, spread/rest et template literals',
    'description' => "Déstructuration d'objets et de tableaux, opérateur spread, paramètres rest, et template literals avancés.",
    'order_index' => 1,
]);
$chap1 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Modules ES6 et organisation du code',
    'description' => "export/import (nommé, par défaut), le principe d'un fichier = une responsabilité, éviter les scripts monolithiques.",
    'order_index' => 2,
]);
$chap2 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Gestion des erreurs et débogage',
    'description' => "try/catch/finally, classes d'erreur personnalisées (extends Error), et utilisation du débogueur/console.",
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

// --- Chapitre 8.1 ---
$theory1 = <<<'HTML'
<p>Trois outils syntaxiques rendent le JavaScript moderne beaucoup plus concis pour des tâches très courantes : extraire des valeurs, combiner des structures, et accepter un nombre variable d'arguments.</p>

<h2>Déstructuration d'objets</h2>

<p>Plutôt que d'accéder aux propriétés une par une, la déstructuration les extrait en une seule ligne, dans des variables du même nom :</p>

<pre><code>const utilisateur = { nom: "Ana", email: "ana@exemple.com", age: 28 };

const { nom, email } = utilisateur;
// équivalent à :
// const nom = utilisateur.nom;
// const email = utilisateur.email;</code></pre>

<figure class="theory-image size-medium align-center">
  <img src="/images/module8-chap1/objet-eclate.svg" alt="Un objet utilisateur source d'un côté, ses propriétés nom et email extraites individuellement de l'autre côté, reliées par des flèches nommées" />
  <figcaption>Figure 1 : la déstructuration extrait plusieurs propriétés en une seule instruction</figcaption>
</figure>

<h2>Déstructuration de tableaux</h2>

<pre><code>const coordonnees = [48.8566, 2.3522];
const [latitude, longitude] = coordonnees;
// latitude = 48.8566, longitude = 2.3522</code></pre>

<h2>L'opérateur spread (...)</h2>

<p><code>...</code> "éclate" un tableau ou un objet en éléments individuels — utile pour copier ou combiner des structures :</p>

<pre><code>const base = { theme: "clair", langue: "fr" };
const surcharge = { theme: "sombre" };

const config = { ...base, ...surcharge };
// { theme: "sombre", langue: "fr" } — surcharge écrase base sur les clés communes

const nombres = [1, 2, 3];
const copie = [...nombres, 4]; // [1, 2, 3, 4] — nombres reste inchangé</code></pre>

<h2>Paramètres rest (...)</h2>

<p>Le même symbole <code>...</code>, utilisé dans une liste de paramètres, fait l'inverse : il <strong>regroupe</strong> un nombre variable d'arguments dans un seul tableau :</p>

<pre><code>function fusionner(...objets) {
  // objets est un tableau, quel que soit le nombre d'arguments passés
  return objets.reduce((resultat, objet) => ({ ...resultat, ...objet }), {});
}

fusionner({ a: 1 }, { b: 2 }, { c: 3 }); // { a: 1, b: 2, c: 3 }</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module8-chap1/fusion-configs.svg" alt="Trois objets de configuration superposés, le dernier gagnant sur chaque clé en conflit, fusionnés en un seul objet final" />
  <figcaption>Figure 2 : en cas de clé en conflit, le dernier objet fusionné l'emporte</figcaption>
</figure>

<h2>Template literals avancés</h2>

<p>Les template literals (vus au module 4) acceptent aussi des expressions plus riches, y compris des appels de fonction ou des expressions conditionnelles :</p>

<pre><code>const prix = 42.5;
`Prix : ${prix.toFixed(2)} €`;                 // appel de méthode directement dans ${...}
`${prix > 50 ? "cher" : "abordable"}`;           // expression conditionnelle</code></pre>

<h2>En résumé</h2>

<ul>
  <li>La déstructuration extrait plusieurs valeurs d'un objet ou d'un tableau en une seule instruction</li>
  <li><code>...</code> en spread éclate une structure existante (copie, fusion) ; en rest, il regroupe des arguments variables en tableau</li>
  <li>Dans une fusion par spread, la dernière source l'emporte sur les clés en conflit</li>
  <li>Un template literal peut contenir n'importe quelle expression JavaScript, pas seulement une variable</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap1,
    'title' => 'Destructuring, spread/rest et template literals',
    'content' => $theory1,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 8.2 ---
$theory2 = <<<'HTML'
<p>Un projet qui grossit devient vite difficile à suivre s'il tient entièrement dans un seul fichier. Les <strong>modules ES6</strong> permettent de répartir le code entre plusieurs fichiers, chacun avec sa propre responsabilité, et de les relier explicitement par <code>import</code>/<code>export</code>.</p>

<h2>export : exposer quelque chose depuis un fichier</h2>

<pre><code>// fichier formaterPrix.js
export function formaterPrix(prix) {
  return `${prix.toFixed(2)} €`;
}

export const TAUX_TVA = 0.2; // un fichier peut exporter plusieurs éléments nommés</code></pre>

<p>Un fichier peut aussi avoir un <strong>export par défaut</strong> (un seul par fichier, pour "l'élément principal" de ce module) :</p>

<pre><code>// fichier Panier.js
export default class Panier {
  // ...
}</code></pre>

<h2>import : utiliser ce qui a été exporté ailleurs</h2>

<pre><code>// fichier app.js
import { formaterPrix, TAUX_TVA } from "./formaterPrix.js"; // export nommé : accolades
import Panier from "./Panier.js";                             // export par défaut : sans accolades

console.log(formaterPrix(19.9));</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module8-chap2/fichiers-import-export.svg" alt="Plusieurs fichiers reliés par des flèches d'import et d'export, chacun exportant une ou plusieurs fonctions ou classes utilisées par les autres" />
  <figcaption>Figure 1 : chaque fichier expose explicitement ce qu'il met à disposition des autres</figcaption>
</figure>

<h2>Un fichier = une responsabilité</h2>

<p>Le découpage en modules n'est pas qu'une question de syntaxe : c'est l'occasion de donner à chaque fichier une <strong>responsabilité unique et claire</strong> — un fichier pour le formatage des prix, un autre pour la gestion du panier, un autre pour les appels réseau... plutôt qu'un unique script monolithique où tout se mélange.</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module8-chap2/avant-apres-monolithe.svg" alt="Comparaison avant/après d'un même projet : un seul gros fichier contenant tout le code d'un côté, plusieurs modules ciblés reliés par des imports de l'autre" />
  <figcaption>Figure 2 : le même projet, réorganisé en modules ciblés plutôt qu'un seul fichier</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li><code>export</code> expose une fonction, une classe ou une valeur depuis un fichier ; <code>import</code> la récupère ailleurs</li>
  <li>Un fichier peut avoir plusieurs exports nommés (<code>{ }</code> à l'import), et au plus un export par défaut (sans <code>{ }</code>)</li>
  <li>Découper en modules donne à chaque fichier une responsabilité unique, plus facile à comprendre et à faire évoluer</li>
  <li>Un script monolithique devient vite difficile à naviguer à mesure qu'un projet grandit</li>
</ul>

<p><em>Ce chapitre est théorique : les exercices de ce cours s'exécutent dans un environnement à un seul fichier, qui ne peut pas faire tourner plusieurs fichiers reliés par <code>import</code>/<code>export</code> — la pratique de ce chapitre se fait directement dans un vrai projet multi-fichiers.</em></p>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap2,
    'title' => 'Modules ES6 et organisation du code',
    'content' => $theory2,
    'order_index' => 1,
    'estimated_time' => 10,
]);

// --- Chapitre 8.3 ---
$theory3 = <<<'HTML'
<p>Une bonne gestion des erreurs distingue un programme robuste d'un programme qui s'effondre à la première entrée inattendue. JavaScript offre <code>try</code>/<code>catch</code>/<code>finally</code>, déjà croisés en module 6, et la possibilité de créer ses <strong>propres types d'erreurs</strong>.</p>

<h2>try/catch/finally, un rappel</h2>

<pre><code>try {
  throw new Error("Quelque chose a échoué");
} catch (erreur) {
  console.log(erreur.message);
} finally {
  console.log("Toujours exécuté, succès ou échec");
}</code></pre>

<h2>Classes d'erreur personnalisées</h2>

<p>Hériter de <code>Error</code> (<code>extends Error</code>) permet de créer un type d'erreur avec son propre nom et ses propres données, tout en restant une vraie erreur JavaScript (utilisable avec <code>throw</code>, capturable par <code>catch</code>) :</p>

<pre><code>class ValidationError extends Error {
  constructor(message, champ) {
    super(message);      // initialise error.message via le constructeur parent
    this.name = "ValidationError"; // remplace le "Error" par défaut
    this.champ = champ;   // donnée supplémentaire propre à ce type d'erreur
  }
}

function validerFormulaire(email) {
  if (email.trim() === "") {
    throw new ValidationError("L'email est obligatoire", "email");
  }
}

try {
  validerFormulaire("");
} catch (erreur) {
  console.log(`Erreur sur le champ "${erreur.champ}" : ${erreur.message}`);
}</code></pre>

<h2>Distinguer plusieurs types d'erreurs</h2>

<p><code>instanceof</code> permet, dans un seul <code>catch</code>, de réagir différemment selon le <strong>type précis</strong> d'erreur reçue :</p>

<pre><code>try {
  traiterCommande(commande);
} catch (erreur) {
  if (erreur instanceof ErreurValidation) {
    console.log(`Corrigez la commande : ${erreur.message}`);
  } else if (erreur instanceof ErreurReseau) {
    console.log(`Réessayez plus tard : ${erreur.message}`);
  } else {
    console.log(`Erreur inattendue : ${erreur.message}`);
  }
}</code></pre>

<figure class="theory-image size-large align-center">
  <img src="/images/module8-chap3/organigramme-try-catch-finally.svg" alt="Organigramme : try en haut, se séparant en deux branches succès et erreur, catch pour l'erreur, puis finally exécuté dans les deux cas avant de continuer" />
  <figcaption>Figure 1 : finally s'exécute toujours, que le bloc try ait réussi ou échoué</figcaption>
</figure>

<h2>Utilisation du débogueur et de la console</h2>

<p>Au-delà de <code>console.log</code>, la console du navigateur offre <code>console.table()</code> (afficher un tableau lisible), <code>console.warn()</code>/<code>console.error()</code> (messages visuellement distincts), et l'instruction <code>debugger;</code> qui, si les outils de développement sont ouverts, met le code en pause à cet endroit précis pour inspecter chaque variable pas à pas.</p>

<figure class="theory-image size-medium align-center">
  <img src="/images/module8-chap3/session-debogage.svg" alt="Session de débogage annotée : un point d'arrêt posé sur une ligne de code, avec le panneau d'inspection de variables ouvert à côté" />
  <figcaption>Figure 2 : un point d'arrêt fige l'exécution pour inspecter l'état exact des variables à cet instant</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li><code>finally</code> s'exécute toujours, que le bloc <code>try</code> ait réussi ou levé une erreur</li>
  <li><code>extends Error</code> crée un type d'erreur personnalisé, avec ses propres données, tout en restant une vraie erreur JavaScript</li>
  <li><code>instanceof</code> distingue plusieurs types d'erreurs personnalisées dans un seul <code>catch</code></li>
  <li><code>debugger;</code> et les outils de développement permettent d'inspecter l'état du programme pas à pas, au-delà de <code>console.log</code></li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap3,
    'title' => 'Gestion des erreurs et débogage',
    'content' => $theory3,
    'order_index' => 1,
    'estimated_time' => 15,
]);

echo "✓ 3 théories créées\n";

// ================================================================
// EXERCICES (chapitres 8.1 et 8.3 uniquement -- 8.2 est théorie seule,
// voir commentaire en tête de fichier)
// ================================================================
$insertExercice = $pdo->prepare(
    "INSERT INTO exercices
    (chapitre_id, title, description, instructions, starter_code, solution_code, expected_output, difficulty, points, order_index)
    VALUES
    (:chapitre_id, :title, :description, :instructions, :starter_code, :solution_code, :expected_output, :difficulty, :points, :order_index)"
);

// --- Chapitre 8.1 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Extraire nom et email',
    'description' => "Extrait deux propriétés d'un objet utilisateur en une seule instruction, par déstructuration.",
    'instructions' => "À partir de l'objet utilisateur fourni, extrais nom et email en une seule instruction de déstructuration, puis affiche \"Nom : <nom>\" et \"Email : <email>\". Le programme doit afficher exactement :\nNom : Ana\nEmail : ana@exemple.com",
    'starter_code' => <<<'JS'
const utilisateur = { nom: "Ana", email: "ana@exemple.com", age: 28 };

// TODO : extrais nom et email par déstructuration en une seule instruction,
// puis affiche "Nom : <nom>" et "Email : <email>"
JS,
    'solution_code' => <<<'JS'
const utilisateur = { nom: "Ana", email: "ana@exemple.com", age: 28 };

const { nom, email } = utilisateur;

console.log(`Nom : ${nom}`);
console.log(`Email : ${email}`);
JS,
    'expected_output' => "Nom : Ana\nEmail : ana@exemple.com",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 8.1 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Fusionner des configurations',
    'description' => "Écris une fonction fusionner(...objets) qui combine un nombre variable d'objets via spread.",
    'instructions' => "Écris une fonction fusionner(...objets) (paramètre rest) qui combine tous les objets reçus en un seul, en utilisant le spread : sur une clé en conflit, le dernier objet fourni l'emporte. Applique-la à defaut = { theme: \"clair\", langue: \"fr\" }, utilisateur = { langue: \"en\" } et session = { theme: \"sombre\" }, dans cet ordre, puis affiche \"theme : <theme>\" et \"langue : <langue>\" du résultat. Le programme doit afficher exactement :\ntheme : sombre\nlangue : en",
    'starter_code' => <<<'JS'
const defaut = { theme: "clair", langue: "fr" };
const utilisateur = { langue: "en" };
const session = { theme: "sombre" };

// TODO : fonction fusionner(...objets) qui combine tous les objets reçus via
// spread (le dernier l'emporte sur une clé en conflit), puis affiche
// "theme : <theme>" et "langue : <langue>" du résultat de
// fusionner(defaut, utilisateur, session)
JS,
    'solution_code' => <<<'JS'
function fusionner(...objets) {
  return objets.reduce((resultat, objet) => ({ ...resultat, ...objet }), {});
}

const defaut = { theme: "clair", langue: "fr" };
const utilisateur = { langue: "en" };
const session = { theme: "sombre" };

const config = fusionner(defaut, utilisateur, session);

console.log(`theme : ${config.theme}`);
console.log(`langue : ${config.langue}`);
JS,
    'expected_output' => "theme : sombre\nlangue : en",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 8.3 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Erreur de validation personnalisée',
    'description' => "Crée une classe ValidationError extends Error et utilise-la dans une fonction de validation.",
    'instructions' => "Écris une classe ValidationError extends Error avec un constructeur(message, champ) qui appelle super(message), fixe this.name à \"ValidationError\" et stocke this.champ. Écris validerFormulaire(email, motDePasse) qui lève une ValidationError si email est vide (champ \"email\") ou si motDePasse fait moins de 8 caractères (champ \"motDePasse\"), et retourne true sinon. Appelle-la avec (\"\", \"azerty12\") dans un try/catch qui affiche \"Erreur sur le champ \\\"<champ>\\\" : <message>\", puis avec (\"ana@exemple.com\", \"azerty12\") dans un try/catch qui affiche \"Formulaire valide !\" en cas de succès. Le programme doit afficher exactement :\nErreur sur le champ \"email\" : L'email est obligatoire\nFormulaire valide !",
    'starter_code' => <<<'JS'
// TODO : classe ValidationError extends Error (constructor(message, champ),
// super(message), this.name, this.champ). Fonction validerFormulaire(email,
// motDePasse) qui lève ValidationError si besoin. Deux appels dans des
// try/catch (voir instructions).
JS,
    'solution_code' => <<<'JS'
class ValidationError extends Error {
  constructor(message, champ) {
    super(message);
    this.name = "ValidationError";
    this.champ = champ;
  }
}

function validerFormulaire(email, motDePasse) {
  if (email.trim() === "") {
    throw new ValidationError("L'email est obligatoire", "email");
  }
  if (motDePasse.length < 8) {
    throw new ValidationError("Le mot de passe doit faire au moins 8 caractères", "motDePasse");
  }
  return true;
}

try {
  validerFormulaire("", "azerty12");
} catch (erreur) {
  console.log(`Erreur sur le champ "${erreur.champ}" : ${erreur.message}`);
}

try {
  validerFormulaire("ana@exemple.com", "azerty12");
  console.log("Formulaire valide !");
} catch (erreur) {
  console.log(`Erreur sur le champ "${erreur.champ}" : ${erreur.message}`);
}
JS,
    'expected_output' => "Erreur sur le champ \"email\" : L'email est obligatoire\nFormulaire valide !",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 8.3 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => "Distinguer plusieurs types d'erreurs",
    'description' => "Utilise instanceof pour réagir différemment selon le type d'erreur personnalisée reçue dans un même catch.",
    'instructions' => "Écris deux classes d'erreur ErreurValidation extends Error et ErreurReseau extends Error (chacune fixant this.name dans son constructeur). Écris traiterCommande(commande) qui lève ErreurValidation si commande.montant <= 0, ErreurReseau si commande.montant > 1000, et retourne sinon \"Commande de <montant> € traitée\". Écris gererCommande(commande) qui appelle traiterCommande dans un try/catch : en cas de succès affiche le résultat ; en cas d'ErreurValidation affiche \"Corrigez la commande : <message>\" ; en cas d'ErreurReseau affiche \"Réessayez plus tard : <message>\" (utilise instanceof). Appelle gererCommande successivement avec { montant: 50 }, { montant: -10 } puis { montant: 2000 }. Le programme doit afficher exactement :\nCommande de 50 € traitée\nCorrigez la commande : Le montant doit être positif\nRéessayez plus tard : Serveur injoignable pour ce montant",
    'starter_code' => <<<'JS'
// TODO : classes ErreurValidation et ErreurReseau (extends Error).
// traiterCommande(commande) qui lève l'une ou l'autre selon le montant.
// gererCommande(commande) qui distingue les erreurs avec instanceof dans un
// seul catch. Appelle-la avec les 3 commandes fournies dans les instructions.
JS,
    'solution_code' => <<<'JS'
class ErreurReseau extends Error {
  constructor(message) {
    super(message);
    this.name = "ErreurReseau";
  }
}

class ErreurValidation extends Error {
  constructor(message) {
    super(message);
    this.name = "ErreurValidation";
  }
}

function traiterCommande(commande) {
  if (commande.montant <= 0) {
    throw new ErreurValidation("Le montant doit être positif");
  }
  if (commande.montant > 1000) {
    throw new ErreurReseau("Serveur injoignable pour ce montant");
  }
  return `Commande de ${commande.montant} € traitée`;
}

function gererCommande(commande) {
  try {
    const resultat = traiterCommande(commande);
    console.log(resultat);
  } catch (erreur) {
    if (erreur instanceof ErreurValidation) {
      console.log(`Corrigez la commande : ${erreur.message}`);
    } else if (erreur instanceof ErreurReseau) {
      console.log(`Réessayez plus tard : ${erreur.message}`);
    } else {
      console.log(`Erreur inattendue : ${erreur.message}`);
    }
  }
}

gererCommande({ montant: 50 });
gererCommande({ montant: -10 });
gererCommande({ montant: 2000 });
JS,
    'expected_output' => "Commande de 50 € traitée\nCorrigez la commande : Le montant doit être positif\nRéessayez plus tard : Serveur injoignable pour ce montant",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

echo "✓ 4 exercices créés (chapitres 8.1 et 8.3 uniquement -- 8.2 est théorie seule)\n";

echo "\n=== Module 8 créé avec succès (module id=$moduleId) ===\n";
