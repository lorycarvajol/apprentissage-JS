<?php

/**
 * Crée le Module 6 ("JavaScript asynchrone") du curriculum JavaScript — voir
 * ROADMAP.md à la racine du dépôt. Suppose que les Modules 1 à 5 ont déjà été
 * créés et occupent order_index=1 à 5.
 *
 * Ce module a nécessité un correctif préalable du sandbox d'exécution
 * (frontend/src/utils/jsSandbox.js) : le Worker envoyait sa sortie capturée
 * immédiatement après l'exécution synchrone du code, avant que la file de
 * microtâches (Promise/async-await) ou les setTimeout n'aient eu la moindre
 * chance de s'exécuter — tout console.log placé dans du code asynchrone était
 * silencieusement perdu, sans erreur (pire que M5 : le code semblait correct
 * mais était quand même noté faux). Le Worker attend maintenant la fin de
 * tout le travail asynchrone en cours avant d'envoyer son résultat.
 *
 * fetch/XMLHttpRequest restent désactivés dans le sandbox (politique
 * volontaire de non-accès réseau, inchangée) : les exercices 6.3 utilisent
 * une fonction asynchrone simulée (Promise + setTimeout) à la place d'un
 * vrai fetch, pour enseigner la même mécanique async/await/try-catch sans
 * dépendre du réseau.
 *
 * expected_output de chaque exercice a été calculé hors-ligne en rejouant
 * solution_code à travers une réplique fidèle du Worker corrigé (Node vm,
 * mêmes globals setTimeout/console/Promise que le vrai sandbox), et
 * revérifié par régression sur des exercices M3/M4 déjà en base.
 *
 * Usage : php database/seed_module6.php
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

echo "=== Création du Module 6 : JavaScript asynchrone ===\n\n";

// ================================================================
// Garde-fou : ne pas rejouer le script si le module existe déjà
// ================================================================
$check = $pdo->prepare("SELECT id FROM modules WHERE title = :title");
$check->execute(['title' => 'JavaScript asynchrone']);
if ($check->fetch()) {
    echo "Le module 'JavaScript asynchrone' existe déjà, arrêt du script.\n";
    exit(0);
}

// ================================================================
// MODULE 6
// ================================================================
$insertModule = $pdo->prepare(
    "INSERT INTO modules (title, description, order_index, is_published)
    VALUES (:title, :description, 6, 1)"
);
$insertModule->execute([
    'title' => 'JavaScript asynchrone',
    'description' => "Sixième module du curriculum JavaScript : callbacks et boucle d'événements, promesses, et async/await (voir ROADMAP.md). Deuxième module sans équivalent PHP direct.",
]);
$moduleId = (int) $pdo->lastInsertId();
echo "✓ Module créé (id=$moduleId, order_index=6)\n";

// ================================================================
// CHAPITRES
// ================================================================
$insertChapitre = $pdo->prepare(
    "INSERT INTO chapitres (module_id, title, description, order_index, is_published)
    VALUES (:module_id, :title, :description, :order_index, 1)"
);

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => "Callbacks et la boucle d'événements",
    'description' => "Code synchrone vs asynchrone, setTimeout, pile d'exécution vs file d'attente, notion d'event loop.",
    'order_index' => 1,
]);
$chap1 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'Promesses',
    'description' => "L'objet Promise, ses trois états, then/catch/finally, et Promise.all.",
    'order_index' => 2,
]);
$chap2 = (int) $pdo->lastInsertId();

$insertChapitre->execute([
    'module_id' => $moduleId,
    'title' => 'async/await',
    'description' => "async/await comme sucre syntaxique au-dessus des promesses, try/catch autour d'un await, et l'idée de fetch pour appeler une API.",
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

// --- Chapitre 6.1 ---
$theory1 = <<<'HTML'
<p>Jusqu'ici, tout le code de ce cours s'exécutait <strong>de façon synchrone</strong> : chaque ligne attend que la précédente soit terminée avant de s'exécuter. Certaines opérations — attendre un délai, une réponse réseau, une action utilisateur — ne peuvent pas se terminer instantanément sans bloquer tout le reste. C'est là qu'intervient l'<strong>asynchrone</strong>.</p>

<h2>Synchrone vs asynchrone</h2>

<pre><code>console.log("1");
console.log("2");
console.log("3");
// affiche toujours 1, 2, 3 dans cet ordre, sans exception</code></pre>

<p>Du code synchrone est entièrement prévisible : chaque instruction bloque la suivante jusqu'à sa fin. Du code asynchrone, lui, <strong>délègue</strong> une opération pour plus tard, sans bloquer le programme en attendant qu'elle se termine.</p>

<h2>setTimeout : différer une exécution</h2>

<pre><code>console.log("1");
setTimeout(() => console.log("2"), 1000);
console.log("3");
// affiche 1, 3, puis 2 une seconde plus tard</code></pre>

<p><code>setTimeout(fonction, delai)</code> ne "met pas en pause" le programme : il continue immédiatement, et exécute <code>fonction</code> seulement après le délai — pendant ce temps, la ligne suivante (<code>console.log("3")</code>) s'exécute sans attendre.</p>

<h2>Pile d'exécution et file d'attente</h2>

<p>JavaScript exécute le code synchrone sur une <strong>pile d'exécution</strong> (call stack) — une fonction à la fois, jusqu'à ce qu'elle se termine. Le code différé (callback de <code>setTimeout</code>, réponse réseau...) attend dans une <strong>file d'attente</strong>, et n'est exécuté qu'une fois la pile complètement vide.</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module6-chap1/pile-file-event-loop.svg" alt="Schéma en trois zones : la pile d'exécution où le code synchrone s'exécute, la file d'attente où patientent les callbacks différés, et la boucle d'événements qui transfère un callback de la file vers la pile dès que celle-ci est vide" />
  <figcaption>Figure 1 : la boucle d'événements ne transfère un callback de la file vers la pile que lorsque celle-ci est totalement vide</figcaption>
</figure>

<h2>La boucle d'événements (event loop)</h2>

<p>La <strong>boucle d'événements</strong> est le mécanisme qui surveille en permanence la pile d'exécution : dès qu'elle est vide, elle prend le prochain callback en attente dans la file et l'exécute. C'est ce mécanisme qui explique pourquoi même <code>setTimeout(fn, 0)</code> — un délai de zéro seconde — ne s'exécute jamais immédiatement : il doit d'abord attendre que tout le code synchrone en cours se termine.</p>

<pre><code>console.log("1");
setTimeout(() => console.log("2"), 0);
console.log("3");
// affiche 1, 3, 2 — jamais 1, 2, 3</code></pre>

<h2>Callbacks imbriqués : le callback hell</h2>

<p>Enchaîner plusieurs opérations asynchrones avec des callbacks conduit vite à un code imbriqué de plus en plus profond, surnommé le <strong>callback hell</strong> :</p>

<pre><code>etape1(() => {
  etape2(() => {
    etape3(() => {
      console.log("terminé");
    });
  });
});</code></pre>

<p>Chaque étape ne peut démarrer qu'à l'intérieur du callback de la précédente — le code devient difficile à lire au-delà de 2 ou 3 niveaux. Les Promesses (chapitre suivant) existent précisément pour résoudre ce problème.</p>

<h2>En résumé</h2>

<ul>
  <li>Le code synchrone bloque ligne par ligne ; le code asynchrone délègue une opération sans bloquer le reste</li>
  <li><code>setTimeout(fn, delai)</code> exécute <code>fn</code> plus tard, sans jamais bloquer le code qui suit</li>
  <li>La pile d'exécution traite le code synchrone ; la file d'attente patiente jusqu'à ce que la pile soit vide</li>
  <li>La boucle d'événements ne transfère jamais un callback vers la pile tant qu'elle n'est pas totalement vide — même avec un délai de 0</li>
  <li>Des callbacks imbriqués en cascade deviennent vite illisibles (callback hell)</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap1,
    'title' => "Callbacks et la boucle d'événements",
    'content' => $theory1,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 6.2 ---
$theory2 = <<<'HTML'
<p>Une <strong>Promise</strong> représente le résultat futur d'une opération asynchrone — une valeur qui n'existe pas encore, mais qui existera (ou échouera) plus tard. Elle remplace avantageusement les callbacks imbriqués vus au chapitre précédent.</p>

<h2>Créer une Promise</h2>

<pre><code>function chargerDonnees() {
  return new Promise((resolve, reject) => {
    setTimeout(() => {
      const succes = true;
      if (succes) {
        resolve("Données chargées !"); // l'opération a réussi
      } else {
        reject(new Error("Échec du chargement")); // l'opération a échoué
      }
    }, 1000);
  });
}</code></pre>

<p><code>resolve</code> et <code>reject</code> sont deux fonctions fournies automatiquement : appeler l'une ou l'autre détermine si la Promise se termine avec succès ou en échec.</p>

<h2>Les trois états</h2>

<p>Une Promise passe toujours par les mêmes trois états, dans cet ordre :</p>

<ul>
  <li><strong>pending</strong> (en attente) — l'opération n'est pas encore terminée</li>
  <li><strong>fulfilled</strong> (résolue) — l'opération a réussi, une valeur est disponible</li>
  <li><strong>rejected</strong> (rejetée) — l'opération a échoué, une erreur est disponible</li>
</ul>

<figure class="theory-image size-medium align-center">
  <img src="/images/module6-chap2/feu-tricolore-promesse.svg" alt="Les trois états d'une promesse représentés comme un feu tricolore : orange pending en attente, vert fulfilled résolue, rouge rejected rejetée" />
  <figcaption>Figure 1 : une Promise commence toujours en attente, puis bascule une seule fois vers résolue ou rejetée</figcaption>
</figure>

<h2>then, catch, finally</h2>

<pre><code>chargerDonnees()
  .then((message) => console.log(message))   // exécuté si resolve() a été appelé
  .catch((erreur) => console.log(erreur.message)) // exécuté si reject() a été appelé
  .finally(() => console.log("terminé"));      // exécuté dans tous les cas</code></pre>

<p>Contrairement aux callbacks imbriqués, <code>.then()</code> peut s'enchaîner à plat, chaque étape recevant le résultat de la précédente — bien plus lisible qu'un empilement de callbacks.</p>

<h2>Promise.all : exécuter en parallèle</h2>

<p><code>Promise.all(tableauDePromises)</code> lance plusieurs opérations asynchrones <strong>en même temps</strong> plutôt que l'une après l'autre, et attend qu'elles soient toutes terminées :</p>

<pre><code>Promise.all([
  chargerRessource("styles.css"),
  chargerRessource("app.js"),
  chargerRessource("data.json"),
])
  .then((resultats) => console.log(resultats.join(", ")))
  .catch((erreur) => console.log(`Erreur : ${erreur.message}`));</code></pre>

<p>Si <strong>une seule</strong> des promesses est rejetée, <code>Promise.all</code> rejette immédiatement l'ensemble — même si les autres ont réussi — et c'est le <code>.catch()</code> qui reçoit l'erreur.</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module6-chap2/promise-all-paralleles.svg" alt="Trois flèches parallèles représentant trois promesses lancées en même temps par Promise.all, convergeant vers un seul résultat si toutes réussissent, ou vers une seule erreur si l'une échoue" />
  <figcaption>Figure 2 : Promise.all attend toutes les promesses, mais échoue entièrement si une seule échoue</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li>Une Promise représente un résultat futur, dans l'un de trois états : pending, fulfilled, rejected</li>
  <li><code>.then()</code> gère le succès, <code>.catch()</code> l'échec, <code>.finally()</code> s'exécute dans tous les cas</li>
  <li>Les <code>.then()</code> s'enchaînent à plat, contrairement aux callbacks imbriqués</li>
  <li><code>Promise.all</code> lance plusieurs promesses en parallèle, mais échoue entièrement si une seule échoue</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap2,
    'title' => 'Promesses',
    'content' => $theory2,
    'order_index' => 1,
    'estimated_time' => 15,
]);

// --- Chapitre 6.3 ---
$theory3 = <<<'HTML'
<p><code>async</code>/<code>await</code> est une syntaxe qui permet d'écrire du code asynchrone en lui donnant l'<strong>apparence</strong> de code synchrone — bien plus lisible qu'un enchaînement de <code>.then()</code>, pour un résultat strictement équivalent : c'est du "sucre syntaxique" au-dessus des Promises.</p>

<h2>async et await</h2>

<pre><code>async function afficherDonnees() {
  const message = await chargerDonnees(); // attend que la Promise se résolve
  console.log(message);
}</code></pre>

<p><code>await</code> ne peut s'utiliser qu'à l'intérieur d'une fonction déclarée <code>async</code>. Il "met en pause" cette fonction (et uniquement elle — le reste du programme continue) jusqu'à ce que la Promise se résolve, puis reprend avec sa valeur.</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module6-chap3/then-vs-async-await.svg" alt="Comparaison ligne à ligne d'un même appel réseau écrit d'abord avec .then() puis avec async/await, montrant la structure plus plate de la seconde version" />
  <figcaption>Figure 1 : même comportement, une lecture bien plus linéaire avec async/await</figcaption>
</figure>

<h2>try/catch autour d'un await</h2>

<p>Une Promise rejetée à l'intérieur d'un <code>await</code> se comporte comme une erreur classique : elle peut être interceptée avec <code>try</code>/<code>catch</code>, exactement comme du code synchrone :</p>

<pre><code>async function afficherDonnees() {
  try {
    const message = await chargerDonnees();
    console.log(message);
  } catch (erreur) {
    console.log(`Erreur : ${erreur.message}`);
  }
}</code></pre>

<h2>fetch : appeler une API</h2>

<p><code>fetch(url)</code> est la fonction native du navigateur pour effectuer une requête réseau et récupérer des données depuis une API — elle retourne une Promise, et s'utilise donc naturellement avec <code>async</code>/<code>await</code> :</p>

<pre><code>async function recupererUtilisateurs() {
  const reponse = await fetch("https://api.exemple.com/utilisateurs");
  const donnees = await reponse.json();
  return donnees;
}</code></pre>

<p>Dans les exercices de ce chapitre, le réseau n'est pas disponible (environnement d'exécution isolé, sans accès internet) : une fonction <strong>simulée</strong> imitant la forme d'un appel <code>fetch</code> (une Promise qui se résout après un court délai) est fournie à la place — la mécanique <code>async</code>/<code>await</code>/<code>try</code>/<code>catch</code> apprise ici est rigoureusement la même qu'avec un vrai <code>fetch</code>.</p>

<figure class="theory-image size-large align-center">
  <img src="/images/module6-chap3/flux-fetch-await.svg" alt="Flux : navigateur envoie une requête via fetch, attend la réponse (point d'arrêt visuel sur await), reçoit une réponse JSON, puis met à jour la page" />
  <figcaption>Figure 2 : await marque une pause visuelle dans le déroulé, le temps que la réponse arrive</figcaption>
</figure>

<h2>En résumé</h2>

<ul>
  <li><code>async</code>/<code>await</code> est une autre écriture des Promises, plus lisible, sans changer leur comportement</li>
  <li><code>await</code> ne s'utilise que dans une fonction <code>async</code>, et met en pause seulement cette fonction</li>
  <li><code>try</code>/<code>catch</code> autour d'un <code>await</code> intercepte une Promise rejetée, comme une erreur classique</li>
  <li><code>fetch</code> retourne une Promise et s'utilise naturellement avec <code>await</code> ; les exercices utilisent une version simulée en l'absence de réseau</li>
</ul>
HTML;

$insertTheory->execute([
    'chapitre_id' => $chap3,
    'title' => 'async/await',
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

// --- Chapitre 6.1 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Simuler un chargement',
    'description' => "Utilise setTimeout et un callback pour simuler un chargement de données.",
    'instructions' => "Écris une fonction chargerDonnees(callback) qui affiche \"Chargement en cours...\", puis, après un délai de 50ms (setTimeout), appelle callback avec le message \"Données chargées !\". Appelle chargerDonnees en lui passant une fonction qui affiche le message reçu. Le programme doit afficher exactement :\nChargement en cours...\nDonnées chargées !",
    'starter_code' => <<<'JS'
// TODO : fonction chargerDonnees(callback) qui affiche "Chargement en
// cours...", puis après 50ms (setTimeout) appelle callback("Données
// chargées !"). Appelle-la avec une fonction qui affiche le message reçu.
JS,
    'solution_code' => <<<'JS'
function chargerDonnees(callback) {
  console.log("Chargement en cours...");
  setTimeout(() => {
    callback("Données chargées !");
  }, 50);
}

chargerDonnees((message) => {
  console.log(message);
});
JS,
    'expected_output' => "Chargement en cours...\nDonnées chargées !",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 6.1 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap1,
    'title' => 'Trois étapes en cascade',
    'description' => "Enchaîne 3 opérations asynchrones avec des callbacks imbriqués (callback hell).",
    'instructions' => "Écris trois fonctions etape1(callback), etape2(callback) et etape3(callback) qui affichent chacune \"Étape N terminée\" après un délai de 10ms (setTimeout), puis appellent callback(). Enchaîne-les en imbriquant leurs callbacks (etape1 appelle etape2 dans son callback, qui appelle etape3 dans le sien), et affiche \"Toutes les étapes sont terminées\" une fois etape3 achevée. Le programme doit afficher exactement :\nÉtape 1 terminée\nÉtape 2 terminée\nÉtape 3 terminée\nToutes les étapes sont terminées",
    'starter_code' => <<<'JS'
// TODO : etape1/etape2/etape3(callback) affichent "Étape N terminée" après
// 10ms puis appellent callback(). Enchaîne-les en imbriquant les callbacks,
// puis affiche "Toutes les étapes sont terminées" à la fin.
JS,
    'solution_code' => <<<'JS'
function etape1(callback) {
  setTimeout(() => {
    console.log("Étape 1 terminée");
    callback();
  }, 10);
}

function etape2(callback) {
  setTimeout(() => {
    console.log("Étape 2 terminée");
    callback();
  }, 10);
}

function etape3(callback) {
  setTimeout(() => {
    console.log("Étape 3 terminée");
    callback();
  }, 10);
}

etape1(() => {
  etape2(() => {
    etape3(() => {
      console.log("Toutes les étapes sont terminées");
    });
  });
});
JS,
    'expected_output' => "Étape 1 terminée\nÉtape 2 terminée\nÉtape 3 terminée\nToutes les étapes sont terminées",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 6.2 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'De callback à Promise',
    'description' => "Transforme la fonction à callback du chapitre précédent en fonction basée sur une Promise.",
    'instructions' => "Réécris chargerDonnees() (sans paramètre callback cette fois) pour qu'elle retourne une Promise : elle affiche \"Chargement en cours...\", puis après 50ms (setTimeout) résout la Promise avec \"Données chargées !\". Utilise .then() pour afficher le message une fois résolu. Le programme doit afficher exactement :\nChargement en cours...\nDonnées chargées !",
    'starter_code' => <<<'JS'
// TODO : fonction chargerDonnees() qui retourne une Promise. Affiche
// "Chargement en cours...", puis après 50ms résout avec "Données chargées !".
// Utilise .then() pour afficher le résultat.
JS,
    'solution_code' => <<<'JS'
function chargerDonnees() {
  return new Promise((resolve) => {
    console.log("Chargement en cours...");
    setTimeout(() => {
      resolve("Données chargées !");
    }, 50);
  });
}

chargerDonnees().then((message) => {
  console.log(message);
});
JS,
    'expected_output' => "Chargement en cours...\nDonnées chargées !",
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 6.2 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap2,
    'title' => 'Charger 3 ressources en parallèle',
    'description' => "Utilise Promise.all pour charger 3 ressources en parallèle et gère l'échec de l'une d'elles.",
    'instructions' => "Écris une fonction chargerRessource(nom, doitEchouer) qui retourne une Promise : après 10ms (setTimeout), elle résout avec \"<nom> chargé\" si doitEchouer est false, ou rejette avec une erreur \"Échec du chargement de <nom>\" si doitEchouer est true. Avec Promise.all, charge en parallèle chargerRessource(\"styles.css\", false), chargerRessource(\"app.js\", true) et chargerRessource(\"data.json\", false). En cas de succès de toutes, affiche les résultats joints par \", \" ; en cas d'échec d'une seule, affiche \"Erreur : <message>\". Le programme doit afficher exactement :\nErreur : Échec du chargement de app.js",
    'starter_code' => <<<'JS'
// TODO : fonction chargerRessource(nom, doitEchouer) qui retourne une
// Promise résolue/rejetée après 10ms selon doitEchouer. Utilise Promise.all
// sur 3 ressources ("app.js" doit échouer), affiche les résultats ou
// "Erreur : <message>" selon le cas.
JS,
    'solution_code' => <<<'JS'
function chargerRessource(nom, doitEchouer) {
  return new Promise((resolve, reject) => {
    setTimeout(() => {
      if (doitEchouer) {
        reject(new Error(`Échec du chargement de ${nom}`));
      } else {
        resolve(`${nom} chargé`);
      }
    }, 10);
  });
}

Promise.all([
  chargerRessource("styles.css", false),
  chargerRessource("app.js", true),
  chargerRessource("data.json", false),
])
  .then((resultats) => {
    console.log(resultats.join(", "));
  })
  .catch((erreur) => {
    console.log(`Erreur : ${erreur.message}`);
  });
JS,
    'expected_output' => 'Erreur : Échec du chargement de app.js',
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

// --- Chapitre 6.3 : Guidé ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Récupérer des utilisateurs (async/await)',
    'description' => "Utilise une fonction async pour récupérer et afficher une liste simulée d'utilisateurs.",
    'instructions' => "Écris une fonction recupererUtilisateurs() qui retourne une Promise résolue après 30ms (setTimeout) avec le tableau [\"Ana\", \"Léo\", \"Mia\"]. Écris une fonction async afficherUtilisateurs() qui attend (await) ce résultat, puis affiche les noms joints par \", \". Le programme doit afficher exactement :\nAna, Léo, Mia",
    'starter_code' => <<<'JS'
// TODO : fonction recupererUtilisateurs() qui retourne une Promise résolue
// après 30ms avec ["Ana", "Léo", "Mia"]. Fonction async afficherUtilisateurs()
// qui l'attend (await) puis affiche les noms joints par ", ".
JS,
    'solution_code' => <<<'JS'
function recupererUtilisateurs() {
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve(["Ana", "Léo", "Mia"]);
    }, 30);
  });
}

async function afficherUtilisateurs() {
  const utilisateurs = await recupererUtilisateurs();
  console.log(utilisateurs.join(", "));
}

afficherUtilisateurs();
JS,
    'expected_output' => 'Ana, Léo, Mia',
    'difficulty' => 'easy',
    'points' => 10,
    'order_index' => 1,
]);

// --- Chapitre 6.3 : Défi ---
$insertExercice->execute([
    'chapitre_id' => $chap3,
    'title' => 'Deux appels dépendants avec gestion d\'erreur',
    'description' => "Enchaîne deux appels asynchrones dépendants (le résultat du premier sert au second) et gère l'échec.",
    'instructions' => "Écris recupererUtilisateur(id) : Promise résolue après 10ms avec { id: 1, nom: \"Ana\" } si id vaut 1, sinon rejetée avec l'erreur \"Utilisateur introuvable\". Écris recupererCommandes(utilisateurId) : Promise résolue après 10ms avec [\"Commande #1 pour l'utilisateur <id>\", \"Commande #2 pour l'utilisateur <id>\"]. Écris une fonction async afficherCommandesUtilisateur(id) qui récupère l'utilisateur, puis ses commandes avec l'id obtenu, affiche chaque commande, et affiche \"Erreur : <message>\" si une étape échoue (try/catch). Appelle-la successivement (en attendant chaque appel) avec id=1 puis id=2. Le programme doit afficher exactement :\nCommande #1 pour l'utilisateur 1\nCommande #2 pour l'utilisateur 1\nErreur : Utilisateur introuvable",
    'starter_code' => <<<'JS'
// TODO : recupererUtilisateur(id) et recupererCommandes(utilisateurId)
// retournent des Promise (voir instructions). Fonction async
// afficherCommandesUtilisateur(id) qui enchaîne les deux avec try/catch,
// puis appelle-la successivement avec id=1 et id=2 (en attendant chaque appel).
JS,
    'solution_code' => <<<'JS'
function recupererUtilisateur(id) {
  return new Promise((resolve, reject) => {
    setTimeout(() => {
      if (id === 1) {
        resolve({ id: 1, nom: "Ana" });
      } else {
        reject(new Error("Utilisateur introuvable"));
      }
    }, 10);
  });
}

function recupererCommandes(utilisateurId) {
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve([
        `Commande #1 pour l'utilisateur ${utilisateurId}`,
        `Commande #2 pour l'utilisateur ${utilisateurId}`,
      ]);
    }, 10);
  });
}

async function afficherCommandesUtilisateur(id) {
  try {
    const utilisateur = await recupererUtilisateur(id);
    const commandes = await recupererCommandes(utilisateur.id);
    commandes.forEach((commande) => console.log(commande));
  } catch (erreur) {
    console.log(`Erreur : ${erreur.message}`);
  }
}

async function main() {
  await afficherCommandesUtilisateur(1);
  await afficherCommandesUtilisateur(2);
}

main();
JS,
    'expected_output' => "Commande #1 pour l'utilisateur 1\nCommande #2 pour l'utilisateur 1\nErreur : Utilisateur introuvable",
    'difficulty' => 'medium',
    'points' => 20,
    'order_index' => 2,
]);

echo "✓ 6 exercices créés\n";

echo "\n=== Module 6 créé avec succès (module id=$moduleId) ===\n";
