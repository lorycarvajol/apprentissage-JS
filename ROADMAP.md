# Feuille de route — Curriculum JavaScript

Contenu de cours à créer via `/admin`. Le contenu de cours est actuellement vide ;
ce document sert de plan de travail, pas de contenu final — chapitres et exercices
peuvent être fusionnés, scindés ou réordonnés librement pendant l'authoring.

**Portée** : une plateforme d'apprentissage JavaScript relativement complète — pas
seulement la POO. La POO n'est qu'un module parmi d'autres (M7), pas l'organisation
centrale du curriculum.

**Format par chapitre** :
- **Théorie** — notions clés à couvrir
- **Exercices** — un exercice *Guidé* (application directe) et un exercice *Défi*
  (combine plusieurs notions)
- **Illustrations** — une illustration *Concept* (pour la théorie) et une illustration
  *Application* (pour l'exercice), afin d'illustrer régulièrement chaque étape

**9 modules · 27 chapitres · 54 exercices suggérés · 54 illustrations suggérées**

**Statut** : ✅ Fait = créé en base (`apprentissage_js`) via un script `backend/database/seed_moduleN.php`, théories + exercices + illustrations SVG réels, vérifié via l'API. ⏳ À faire = plan ci-dessous, rien en base pour l'instant.

---

## Sommaire

| Module | Titre | Chapitres | Statut |
|---|---|---|---|
| M1 | Bases du langage | 1.1 – 1.3 | ✅ Fait |
| M2 | Boucles et collections | 2.1 – 2.3 | ✅ Fait |
| M3 | Fonctions | 3.1 – 3.3 | ✅ Fait |
| M4 | Chaînes, dates et données textuelles | 4.1 – 4.3 | ⏳ À faire |
| M5 | Le DOM et les événements | 5.1 – 5.3 | ⏳ À faire |
| M6 | JavaScript asynchrone | 6.1 – 6.3 | ⏳ À faire |
| M7 | Programmation orientée objet | 7.1 – 7.3 | ⏳ À faire |
| M8 | JavaScript moderne et bonnes pratiques | 8.1 – 8.3 | ⏳ À faire |
| M9 | Projet intégrateur | 9.1 – 9.3 | ⏳ À faire |

---

## M1 — Bases du langage ✅ Fait

Poser le vocabulaire et la syntaxe de base avant toute logique plus complexe.

*Créé en base via `backend/database/seed_module1.php` — 3 théories, 6 exercices, 6 illustrations SVG (`frontend/public/images/module1-chap{1,2,3}/`).*

### 1.1 Variables, types et syntaxe
- **Théorie** : `let`/`const`/`var` et leurs portées, types primitifs (string, number,
  boolean, null, undefined, symbol, bigint), `typeof`, conversion implicite/explicite
- **Exercices**
  - *Guidé* — script qui déclare des variables pour une fiche produit (nom, prix,
    enStock) et les affiche formatées
  - *Défi* — corriger un extrait de code qui mélange `var`/`let` de façon incohérente
    (bug de portée/hoisting)
- **Illustrations**
  - *Concept* — étiquettes de « boîtes » typées (chaîne, nombre, booléen) avec leur
    valeur à l'intérieur
  - *Application* — schéma avant/après montrant la portée de bloc de `let` vs `var`
    (deux blocs imbriqués, une variable qui « fuit » avec `var`)

### 1.2 Opérateurs et expressions
- **Théorie** : opérateurs arithmétiques, comparaison stricte vs non stricte
  (`===` vs `==`), opérateurs logiques, opérateur ternaire
- **Exercices**
  - *Guidé* — calculer un prix TTC à partir d'un prix HT et d'un taux de TVA
  - *Défi* — fonction qui détermine si un utilisateur peut accéder à un contenu
    (âge ET abonnement actif) via une expression logique unique
- **Illustrations**
  - *Concept* — tableau de vérité visuel pour `&&` et `||` (deux colonnes d'entrée,
    une colonne de résultat)
  - *Application* — ligne de calcul en cascade montrant l'ordre d'évaluation d'une
    expression avec priorités d'opérateurs

### 1.3 Structures conditionnelles
- **Théorie** : `if`/`else if`/`else`, `switch`, ternaire imbriqué (à éviter),
  lisibilité
- **Exercices**
  - *Guidé* — classer une note en mention (Passable/Bien/Très bien) avec
    `if`/`else if`
  - *Défi* — réécrire une cascade de `if`/`else` en `switch` quand c'est plus
    lisible, et justifier le cas inverse
- **Illustrations**
  - *Concept* — organigramme de décision classique (losange de condition,
    branches oui/non)
  - *Application* — le même organigramme comparé côte à côte en `if`/`else` vs
    en `switch`

---

## M2 — Boucles et collections ✅ Fait

Répéter des traitements et manipuler des ensembles de données.

*Créé en base via `backend/database/seed_module2.php` — 3 théories, 6 exercices, 6 illustrations SVG (`frontend/public/images/module2-chap{1,2,3}/`).*

### 2.1 Boucles
- **Théorie** : `for`, `while`, `do...while`, `for...of` (itérables), `for...in`
  (clés d'objet), `break`/`continue`
- **Exercices**
  - *Guidé* — afficher la table de multiplication d'un nombre avec une boucle `for`
  - *Défi* — parcourir un tableau de commandes et interrompre la boucle dès qu'une
    commande dépasse un seuil (`break`)
- **Illustrations**
  - *Concept* — piste circulaire avec un curseur qui boucle et une sortie
    « condition fausse »
  - *Application* — comparaison de `for` classique et `for...of` sur un même
    tableau, montrant ce que contient la variable de boucle à chaque tour

### 2.2 Tableaux indexés et méthodes courantes
- **Théorie** : création, indices, longueur, méthodes de mutation (`push`/`pop`/
  `splice`) vs non-mutantes (`map`/`filter`/`slice`), `find`
- **Exercices**
  - *Guidé* — filtrer un tableau de produits pour ne garder que ceux en stock
  - *Défi* — transformer un tableau de commandes en résumés texte via `map`, puis
    calculer un total via `reduce`
- **Illustrations**
  - *Concept* — rangée de cases numérotées (indices 0, 1, 2…) représentant un
    tableau
  - *Application* — schéma « tuyau » d'un tableau traversant `filter()` puis
    `map()` puis `reduce()`, la forme des données changeant à chaque étape

### 2.3 Objets et structures imbriquées
- **Théorie** : objets littéraux, accès par point vs crochets, imbrication
  objets/tableaux, `Object.keys`/`values`/`entries`
- **Exercices**
  - *Guidé* — extraire les noms de tous les auteurs d'un tableau d'objets « livre »
  - *Défi* — construire un objet de statistiques (nombre par catégorie) à partir
    d'un tableau d'objets hétérogènes
- **Illustrations**
  - *Concept* — schéma d'un objet « carnet d'adresses » contenant un tableau de
    contacts, chacun étant lui-même un objet
  - *Application* — diagramme de « chemin d'accès » vers une valeur profondément
    imbriquée (ex. `commande.client.adresse.ville`)

---

## M3 — Fonctions ✅ Fait

Découper le code en blocs réutilisables et comprendre la portée.

*Créé en base via `backend/database/seed_module3.php` — 3 théories, 6 exercices, 6 illustrations SVG (`frontend/public/images/module3-chap{1,2,3}/`).*

### 3.1 Déclarer et utiliser des fonctions
- **Théorie** : déclaration de fonction, expression de fonction, arrow function,
  paramètres par défaut, valeur de retour
- **Exercices**
  - *Guidé* — `calculerRemise(prix, pourcentage)` avec une valeur par défaut pour
    `pourcentage`
  - *Défi* — réécrire une même fonction dans les trois syntaxes (déclaration,
    expression, arrow) et identifier les différences
- **Illustrations**
  - *Concept* — schéma « boîte noire » : entrées à gauche, traitement au centre,
    sortie à droite
  - *Application* — comparatif ligne à ligne des trois syntaxes de fonction, parties
    communes surlignées

### 3.2 Portée et closures
- **Théorie** : portée de bloc vs de fonction, portée lexicale, closures, cas
  d'usage (compteur privé, fabrique de fonctions)
- **Exercices**
  - *Guidé* — fabrique `creerCompteur()` qui retourne une fonction
    `incrementer()` gardant son propre compte
  - *Défi* — corriger un bug classique de closure dans une boucle (`var i` capturé
    dans un `setTimeout`)
- **Illustrations**
  - *Concept* — schéma « sac à dos » : une fonction interne qui emporte avec elle
    les variables de son environnement de création
  - *Application* — chronologie de 3 appels successifs à `creerCompteur()`
    produisant 3 compteurs indépendants

### 3.3 Fonctions d'ordre supérieur
- **Théorie** : fonction acceptant/retournant une fonction, callbacks, `map`/
  `filter`/`reduce` en détail, quand préférer une boucle
- **Exercices**
  - *Guidé* — utiliser `reduce()` pour calculer le total d'un panier
  - *Défi* — composer deux fonctions utilitaires (ex. normaliser puis trier) en une
    seule pipeline
- **Illustrations**
  - *Concept* — flèche entrant dans une fonction représentée comme un rouage, avec
    une fonction « callback » glissée dedans
  - *Application* — chaîne de rouages engrenés représentant une pipeline de
    transformations successives

---

## M4 — Chaînes, dates et données textuelles ⏳ À faire

Manipuler du texte et des dates, deux besoins omniprésents.

### 4.1 Méthodes de chaînes de caractères
- **Théorie** : template literals, `slice`/`substring`, `split`/`join`, `trim`,
  `includes`/`startsWith`/`endsWith`, transformation de casse
- **Exercices**
  - *Guidé* — construire un message de bienvenue personnalisé via template literal
  - *Défi* — `slugify(texte)` qui transforme un titre en URL propre (minuscules,
    tirets, sans accents)
- **Illustrations**
  - *Concept* — règle graduée au-dessus d'une chaîne de caractères montrant les
    indices utilisés par `slice()`
  - *Application* — schéma avant/après d'un titre transformé étape par étape en
    slug

### 4.2 Expressions régulières de base
- **Théorie** : syntaxe minimale (littéraux, classes de caractères, quantificateurs),
  `test()`/`match()`, cas d'usage réalistes (validation email, extraction)
- **Exercices**
  - *Guidé* — valider le format d'une adresse email avec une regex simple
  - *Défi* — extraire tous les hashtags d'un texte libre avec `match()` et une regex
    globale
- **Illustrations**
  - *Concept* — loupe posée sur un motif répété dans une chaîne, mettant en
    évidence ce qui « matche »
  - *Application* — décomposition d'une regex simple en blocs annotés (chaque
    symbole expliqué)

### 4.3 Dates et formats
- **Théorie** : objet `Date`, création/lecture, calculs de durée, formatage avec
  `Intl.DateTimeFormat`
- **Exercices**
  - *Guidé* — afficher la date du jour formatée en français (jour/mois/année)
  - *Défi* — calculer le nombre de jours restants avant une échéance donnée
- **Illustrations**
  - *Concept* — frise chronologique simple avec un point « aujourd'hui » et un
    point « échéance »
  - *Application* — une même date affichée dans 3 formats différents (ISO,
    français, relatif « dans 3 jours »)

---

## M5 — Le DOM et les événements ⏳ À faire

Le premier module vraiment spécifique au navigateur — sans équivalent PHP.

### 5.1 Sélectionner et manipuler le DOM
- **Théorie** : `querySelector`/`querySelectorAll`, lecture/écriture de contenu et
  d'attributs, création/insertion d'éléments
- **Exercices**
  - *Guidé* — script qui modifie le texte et la couleur d'un titre au chargement
    de la page
  - *Défi* — générer dynamiquement une liste HTML à partir d'un tableau de
    données JavaScript
- **Illustrations**
  - *Concept* — arbre du DOM avec un nœud surligné, correspondant à une ligne
    `document.querySelector()`
  - *Application* — schéma avant/après de la page montrant l'insertion d'un
    nouvel élément dans l'arbre

### 5.2 Événements et interactivité
- **Théorie** : `addEventListener`, objet `event`, propagation (bubbling),
  délégation d'événements
- **Exercices**
  - *Guidé* — bouton qui incrémente un compteur affiché à l'écran au clic
  - *Défi* — liste de tâches où un seul écouteur sur le conteneur gère le clic sur
    n'importe quel élément enfant (délégation)
- **Illustrations**
  - *Concept* — onde qui part d'un clic sur un élément enfant et remonte l'arbre
    du DOM (bulles concentriques)
  - *Application* — écouteur par élément (flèches multiples) vs écouteur délégué
    unique (une seule flèche vers le parent)

### 5.3 Formulaires et validation côté client
- **Théorie** : événement `submit`, `preventDefault`, lecture des champs,
  validation avant envoi, retour visuel d'erreur
- **Exercices**
  - *Guidé* — formulaire d'inscription simple qui empêche l'envoi si le champ
    email est vide
  - *Défi* — formulaire avec plusieurs règles de validation (longueur du mot de
    passe, confirmation) et messages d'erreur ciblés par champ
- **Illustrations**
  - *Concept* — formulaire annoté avec un garde-barrière avant la case
    « envoyé »
  - *Application* — un champ de formulaire dans ses trois états (neutre, erreur,
    valide) avec les styles associés

---

## M6 — JavaScript asynchrone ⏳ À faire

Deuxième module sans vrai équivalent PHP (le PHP sibling est synchrone par requête).

### 6.1 Callbacks et la boucle d'événements
- **Théorie** : code synchrone vs asynchrone, `setTimeout`, pile d'exécution vs
  file d'attente, notion d'event loop
- **Exercices**
  - *Guidé* — simuler un chargement avec `setTimeout` et un callback affiché
    après le délai
  - *Défi* — enchaîner 3 opérations asynchrones avec des callbacks imbriqués et
    observer le « callback hell »
- **Illustrations**
  - *Concept* — schéma pile / file / event loop (les trois zones classiques avec
    flèches de transfert)
  - *Application* — chronologie de l'ordre réel d'exécution d'un code contenant
    un `setTimeout(0)` au milieu de code synchrone

### 6.2 Promesses
- **Théorie** : `Promise`, états (pending/fulfilled/rejected), `then`/`catch`/
  `finally`, `Promise.all`
- **Exercices**
  - *Guidé* — transformer la fonction à callback du chapitre précédent en
    fonction basée sur une `Promise`
  - *Défi* — charger 3 ressources en parallèle avec `Promise.all` et gérer
    l'échec de l'une d'elles
- **Illustrations**
  - *Concept* — les 3 états d'une promesse sous forme de feu tricolore (en
    attente / résolue / rejetée)
  - *Application* — `Promise.all` avec 3 flèches parallèles convergeant vers un
    seul résultat (ou vers une erreur)

### 6.3 async/await et fetch
- **Théorie** : `async`/`await` comme sucre syntaxique au-dessus des promesses,
  `try`/`catch` autour d'un `await`, `fetch` pour appeler une API
- **Exercices**
  - *Guidé* — fonction `async` qui récupère une liste d'utilisateurs depuis une
    API publique et les affiche
  - *Défi* — enchaîner deux appels `fetch` dépendants (le résultat du premier
    sert de paramètre au second) avec gestion d'erreur
- **Illustrations**
  - *Concept* — comparaison ligne à ligne d'un même appel réseau écrit en
    `.then()` puis en `async`/`await`
  - *Application* — flux navigateur → `fetch` → attente → réponse JSON → mise à
    jour de la page, avec un point d'arrêt visuel sur `await`

---

## M7 — Programmation orientée objet ⏳ À faire

La POO en JavaScript, un module du curriculum parmi d'autres — pas son organisation
centrale.

### 7.1 Objets, classes et constructeurs
- **Théorie** : objets littéraux vs classes, `class`/`constructor`, `new`, `this`,
  plusieurs instances d'une classe
- **Exercices**
  - *Guidé* — classe `Personne(nom, age)` + méthode `seSaluer()`
  - *Défi* — classe `Rectangle(largeur, hauteur)` avec `perimetre()`/`aire()`
- **Illustrations**
  - *Concept* — un « moule » relié à plusieurs instances aux valeurs différentes
  - *Application* — flèche partant d'une méthode vers « sa » boîte-instance en
    mémoire (`this`)

### 7.2 Encapsulation et héritage
- **Théorie** : champs privés `#`, `get`/`set`, `extends`, `super()`
- **Exercices**
  - *Guidé* — classe `CompteBancaire` avec `#solde` privé et `deposer()`/`retirer()`
  - *Défi* — `Animal` → `Chien extends Animal` avec redéfinition de
    `seDeplacer()`
- **Illustrations**
  - *Concept* — boîte fermée à cadenas autour d'une donnée privée, seule porte
    « méthode publique » pour y accéder
  - *Application* — arbre à une racine (`Animal`) et deux branches (`Chien`,
    `Chat`), flèches « hérite de »

### 7.3 Polymorphisme et prototypes
- **Théorie** : duck typing, tableau polymorphe, chaîne de prototypes en bref
- **Exercices**
  - *Guidé* — tableau de formes hétérogènes (`Cercle`, `Carre`), boucle appelant
    `.aire()` sur chacune
  - *Défi* — fonction générique acceptant tout objet ayant une méthode
    `.versTexte()`, quelle que soit sa classe
- **Illustrations**
  - *Concept* — boucle circulaire au-dessus d'une rangée de formes différentes,
    toutes reliées à la même étiquette `.aire()`
  - *Application* — chaîne instance → `Classe.prototype` → `Object.prototype` →
    `null`

---

## M8 — JavaScript moderne et bonnes pratiques ⏳ À faire

### 8.1 Destructuring, spread/rest et template literals
- **Théorie** : déstructuration d'objets/tableaux, opérateur spread (`...`),
  paramètres rest, template literals avancés
- **Exercices**
  - *Guidé* — extraire `nom` et `email` d'un objet utilisateur par déstructuration
    en un seul temps
  - *Défi* — `fusionner(...objets)` qui combine un nombre variable d'objets de
    configuration via spread
- **Illustrations**
  - *Concept* — schéma « objet éclaté » : un objet source d'un côté, ses
    propriétés extraites individuellement de l'autre, reliées par des flèches
    nommées
  - *Application* — fusion de 3 objets de config superposés en un seul objet
    final (dernier gagnant en cas de conflit)

### 8.2 Modules ES6 et organisation du code
- **Théorie** : `export`/`import` (nommé, par défaut), un fichier = une
  responsabilité, éviter les scripts monolithiques
- **Exercices**
  - *Guidé* — extraire `formaterPrix()` dans son propre module et l'importer
    ailleurs
  - *Défi* — réorganiser un script de 100 lignes en 3 modules cohérents avec
    leurs imports croisés
- **Illustrations**
  - *Concept* — schéma de fichiers reliés par des flèches d'import/export
  - *Application* — avant/après d'un même projet : un seul gros fichier vs
    plusieurs modules ciblés

### 8.3 Gestion des erreurs et débogage
- **Théorie** : `try`/`catch`/`finally`, classes d'erreur personnalisées
  (`extends Error`), utilisation du débogueur/console
- **Exercices**
  - *Guidé* — classe `ValidationError extends Error` utilisée dans une fonction
    de validation de formulaire
  - *Défi* — fonction qui distingue plusieurs types d'erreurs personnalisées dans
    un même `catch` et réagit différemment à chacune
- **Illustrations**
  - *Concept* — organigramme `try` → succès/erreur → `catch` → `finally`
  - *Application* — session de débogage annotée (points d'arrêt, inspection de
    variable) mise en schéma

---

## M9 — Projet intégrateur ⏳ À faire

Assembler les modules précédents dans une mini-application cohérente.

### 9.1 Concevoir une mini-application
- **Théorie** : découper un besoin en fonctionnalités, choisir une structure de
  données, esquisser l'architecture avant de coder
- **Exercices**
  - *Guidé* — rédiger le cahier des charges simplifié d'un gestionnaire de
    tâches (fonctionnalités, structure de données)
  - *Défi* — esquisser l'architecture (fichiers, fonctions/classes prévues) d'une
    application « liste de courses partagée »
- **Illustrations**
  - *Concept* — schéma de planification : besoin → fonctionnalités → structure
    de données → interface
  - *Application* — maquette filaire (wireframe) simple de l'interface du
    gestionnaire de tâches

### 9.2 Construire l'application
- **Théorie** : état de l'application, rendu à partir de l'état, gestion des
  interactions utilisateur
- **Exercices**
  - *Guidé* — implémenter l'ajout et l'affichage des tâches à partir d'un
    tableau d'état
  - *Défi* — implémenter le marquage « terminé » et la suppression, en gardant
    l'affichage synchronisé avec l'état
- **Illustrations**
  - *Concept* — cycle état → rendu → interaction → mise à jour de l'état (boucle
    fermée)
  - *Application* — une tâche avant/après un clic sur « terminé », du clic
    jusqu'à la mise à jour visuelle

### 9.3 Persistance et finitions
- **Théorie** : `localStorage`, sérialisation JSON, gestion des erreurs
  réseau/stockage, petites touches UX
- **Exercices**
  - *Guidé* — sauvegarder et recharger la liste de tâches via `localStorage` au
    démarrage
  - *Défi* — gérer proprement le cas où `localStorage` est indisponible ou
    corrompu (fallback + message clair)
- **Illustrations**
  - *Concept* — flux tâche → `JSON.stringify` → `localStorage` → `JSON.parse` →
    tâche à nouveau
  - *Application* — chemins « cas normal » vs « cas d'erreur » lors du
    chargement au démarrage

---

## Notes de suivi

- Avancement : **M1, M2 et M3 créés en base et vérifiés via l'API** (3/9 modules,
  9/27 chapitres). Prochaine étape : M4 — Chaînes, dates et données textuelles.
- Pour M3.2 Défi, le scénario ROADMAP initial ("bug de closure dans un
  `setTimeout`") a été adapté en un tableau de fonctions appelées après la
  boucle : `jsSandbox.js` envoie sa sortie capturée dès la fin de l'exécution
  synchrone, donc tout `console.log` dans un vrai callback asynchrone
  (`setTimeout`) n'apparaîtrait jamais dans `output` — le même bug de closure
  reste démontré, sans dépendre d'async (M6 le couvrira).
- Pour M1/M2/M3, les deux exercices par chapitre (Guidé + Défi) ont été implémentés
  d'un coup, avec `expected_output` calculé hors-ligne via Node (réplique exacte
  de la capture `console.log` de `jsSandbox.js`) plutôt que deviné à l'œil — à
  reproduire pour la suite plutôt que la passe « Guidé d'abord » envisagée
  initialement ci-dessous.
- Chaque module fait est rejouable sans risque : les scripts `seed_moduleN.php`
  ont un garde-fou qui les arrête si le module existe déjà en base.
- `CLAUDE.md` a été mis à jour pour refléter la portée définie ici (curriculum
  JS complet, POO = un module parmi d'autres).
