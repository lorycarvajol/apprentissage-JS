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

**Statut** : ✅ Fait = créé en base (`apprentissage_js`) via un script `backend/database/seed_moduleN.php`, théories + exercices + illustrations SVG réels, vérifié via l'API. 🟡 Partiel = au moins un chapitre en théorie + illustrations seulement, sans exercices notés (limitation du sandbox pour ce chapitre précis, voir Notes de suivi). ⏳ À faire = plan ci-dessous, rien en base pour l'instant.

---

## Sommaire

| Module | Titre | Chapitres | Statut |
|---|---|---|---|
| M1 | Bases du langage | 1.1 – 1.3 | ✅ Fait |
| M2 | Boucles et collections | 2.1 – 2.3 | ✅ Fait |
| M3 | Fonctions | 3.1 – 3.3 | ✅ Fait |
| M4 | Chaînes, dates et données textuelles | 4.1 – 4.3 | ✅ Fait |
| M5 | Le DOM et les événements | 5.1 – 5.3 | ✅ Fait |
| M6 | JavaScript asynchrone | 6.1 – 6.3 | ✅ Fait |
| M7 | Programmation orientée objet | 7.1 – 7.3 | ✅ Fait |
| M8 | JavaScript moderne et bonnes pratiques | 8.1 – 8.3 | 🟡 Partiel (8.2 théorie seule) |
| M9 | Vers les frameworks modernes | 9.1 – 9.3 | 🟡 Partiel (9.1/9.3 théorie seule) |

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

## M4 — Chaînes, dates et données textuelles ✅ Fait

Manipuler du texte et des dates, deux besoins omniprésents.

*Créé en base via `backend/database/seed_module4.php` — 3 théories, 6 exercices, 6 illustrations SVG (`frontend/public/images/module4-chap{1,2,3}/`).*

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

## M5 — Le DOM et les événements ✅ Fait

Le premier module vraiment spécifique au navigateur — sans équivalent PHP.

*Créé en base via `backend/database/seed_module5.php` (3 théories, 6 illustrations SVG dans `frontend/public/images/module5-chap{1,2,3}/`) puis `backend/database/seed_module5_exercices.php` (6 exercices, ajoutés après coup une fois le sandbox étendu au DOM réel — voir Notes de suivi).*

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

## M6 — JavaScript asynchrone ✅ Fait

Deuxième module sans vrai équivalent PHP (le PHP sibling est synchrone par requête).

*Créé en base via `backend/database/seed_module6.php` — 3 théories, 6 exercices, 6 illustrations SVG (`frontend/public/images/module6-chap{1,2,3}/`). A nécessité un correctif préalable du sandbox, voir Notes de suivi.*

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

## M7 — Programmation orientée objet ✅ Fait

La POO en JavaScript, un module du curriculum parmi d'autres — pas son organisation
centrale.

*Créé en base via `backend/database/seed_module7.php` — 3 théories, 6 exercices, 6 illustrations SVG (`frontend/public/images/module7-chap{1,2,3}/`). Aucun changement de sandbox requis (JS synchrone standard).*

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

## M8 — JavaScript moderne et bonnes pratiques 🟡 Partiel (8.2 théorie seule)

*Créé en base via `backend/database/seed_module8.php` — 3 théories, 6 illustrations SVG (`frontend/public/images/module8-chap{1,2,3}/`), mais **seulement 4 exercices** (8.1 et 8.3, 2 chacun) : 8.2 (Modules ES6) est théorie seule, voir Notes de suivi.*

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

## M9 — Vers les frameworks modernes 🟡 Partiel (9.1/9.3 théorie seule)

**Redéfini entièrement sur demande explicite de l'utilisateur — n'est plus le
"Projet intégrateur" décrit ci-dessous à l'origine.** L'ancien plan (cahier
des charges en texte libre, app de tâches avec rendu DOM réel et
`localStorage`) cumulait trois problèmes structurels à la fois (voir Notes de
suivi) et, indépendamment de ça, n'intéressait pas l'utilisateur pour la
conclusion du cursus. Nouveau rôle du module : conclure le cursus vanilla JS
en expliquant pourquoi les frameworks front-end existent, construire "à la
main" le modèle par composants qu'ils automatisent tous, puis dresser un
panorama de l'écosystème actuel (React/Vue/Angular/Svelte, méta-frameworks).

*Créé en base via `backend/database/seed_module9.php` — 3 théories, 6
illustrations SVG (`frontend/public/images/module9-chap{1,2,3}/`), 2
exercices notés (chapitre 9.2 uniquement — 9.1 et 9.3 sont théorie seule, du
contenu de panorama sans exercice de code à y rattacher naturellement).*

### 9.1 Pourquoi des frameworks ? — théorie seule
- **Théorie** : les limites du vanilla JS à grande échelle (synchronisation
  manuelle état → DOM qui devient une source d'erreurs), notion de composant
  et de réactivité, déclaratif vs impératif
- **Illustrations**
  - *Concept* — état relié par des fils enchevêtrés à plusieurs endroits du
    DOM, chacun à mettre à jour manuellement
  - *Application* — comparaison avant/après : mise à jour manuelle ciblée du
    DOM vs une seule expression déclarative

### 9.2 Le modèle par composants
- **Théorie** : un composant comme fonction(props) → rendu, props en lecture
  seule, état local via closure (rappel du module 3.2), recomposition,
  composition de composants
- **Exercices**
  - *Guidé* — `composantBouton({ texte, couleur })`, une fonction qui prend
    des props et retourne une chaîne de rendu
  - *Défi* — `creerCompteur()` (état local via closure) combiné à un
    `composantCarte()` qui compose son rendu, pour simuler concrètement une
    recomposition après changement d'état
- **Illustrations**
  - *Concept* — un composant représenté comme une fonction : props en
    entrée, rendu en sortie
  - *Application* — un compteur avant/après un changement d'état, montrant
    le nouveau rendu produit par la recomposition

### 9.3 Panorama et choisir sa techno — théorie seule
- **Théorie** : React (librairie, JSX, Virtual DOM — ce que ce projet utilise
  en frontend), Vue (progressif), Angular (complet, TypeScript), Svelte
  (compilé) ; les méta-frameworks (Next.js, Nuxt, SvelteKit, Astro) ; comment
  orienter son choix selon le contexte
- **Illustrations**
  - *Concept* — comparatif visuel des 4 frameworks selon deux axes
    (librairie/complet, virtual DOM/compilé)
  - *Application* — trois piles verticales : React+Next.js, Vue+Nuxt,
    Svelte+SvelteKit, chaque méta-framework ajoutant SSR/routing/API routes

---

## Notes de suivi

- Avancement : **les 9 modules sont créés en base et vérifiés via l'API — le
  plan initial est entièrement authored** (27/27 chapitres). M1-M7 et M9.2
  ont théorie + exercices + illustrations complets (le sandbox DOM de M5 a
  été construit rétroactivement, voir plus bas) ; seul M8 reste partiel
  (8.1/8.3 complets, 8.2 théorie seule) ainsi que M9.1/M9.3 (théorie +
  illustrations seulement, pas d'exercices de code prévus pour ces
  chapitres de panorama). Ce qui reste, si on y revient : M8.2 attend le
  même genre de chantier que celui qui a débloqué M5 (support multi-fichiers
  pour `import`/`export`, plus lourd — touche aussi le schéma et l'UI admin,
  pas seulement le sandbox) — pas une priorité immédiate.
- **M9 a été entièrement redéfini sur demande explicite de l'utilisateur : ce
  n'est plus le "Projet intégrateur" que ce document décrivait à l'origine.**
  L'ancien plan (rédiger un cahier des charges en texte libre, construire une
  app de tâches avec rendu DOM réel et persistance `localStorage`) cumulait
  trois problèmes structurels détectés avant d'écrire le contenu : (a) un
  cahier des charges en texte libre n'est pas un exercice notable par ce
  mécanisme de plateforme (comparaison de `console.log` à `expected_output`,
  pas de correction de texte libre) ; (b) "l'affichage" des tâches retombait
  dans le même trou que M5 (pas de `document` dans le Worker) ; (c)
  `localStorage` n'existe pas du tout dans le scope global d'un Worker dédié
  — spécification navigateur (`Window` uniquement), pas une restriction
  ajoutée par ce projet comme pour `fetch`. Indépendamment de ces problèmes
  techniques, l'utilisateur ne voulait de toute façon pas de ce module en
  conclusion du cursus. Nouveau rôle : expliquer pourquoi les frameworks
  existent, construire "à la main" (JS pur, sans DOM) le modèle par
  composants qu'ils automatisent tous — un vrai exercice noté, pont concret
  vers les closures du module 3 — puis dresser un panorama de l'écosystème
  actuel. Aucune limitation de sandbox sur le contenu retenu : tout est du JS
  synchrone standard.
- **M8.2 (Modules ES6) est théorie seule, même cause structurelle que M5 :
  `import`/`export` lèvent une SyntaxError immédiate sous `new Function(code)`
  (vérifié : "Unexpected token 'export'", "Cannot use import statement
  outside a module") — le sandbox exécute une seule chaîne de code dans un
  seul scope, pas un vrai module. Contrairement au correctif de M6 (un
  changement borné dans un seul fichier), bien faire du multi-fichiers
  demanderait un vrai support de plusieurs fichiers par exercice (schéma +
  `ExerciceManager.jsx`) et un chargement de modules réel (`import()`
  dynamique + réécriture des spécificateurs) — un chantier plus large,
  volontairement pas fait ici. 8.1 (destructuring/spread/rest) et 8.3
  (try/catch, classes d'erreur) sont du JS synchrone standard, sans ce
  problème, et ont leurs 2 exercices chacun.**
- **M6 a nécessité un vrai correctif de `jsSandbox.js` (pas juste un
  contournement par exercice), fait avec l'accord de l'utilisateur avant
  d'écrire le contenu.** Le Worker envoyait `postMessage` immédiatement après
  l'exécution synchrone du code soumis, avant que la file de microtâches
  (Promise/async-await) ou les `setTimeout` n'aient eu la moindre chance de
  s'exécuter — tout `console.log` placé dans un callback asynchrone était
  silencieusement perdu, sans erreur (pire que le trou de M5 : le code
  semblait correct mais était quand même noté faux). Vérifié concrètement
  avant correctif via une réplique Node du Worker (setTimeout/Promise.then/
  async-await ne capturaient que la partie synchrone du log).
  Correctif : `setTimeout` est remplacé dans le Worker par une version qui
  compte les minuteurs en attente (`pendingTimers`), et `postMessage` n'est
  envoyé qu'une fois ce compteur revenu à zéro (+ un tour de microtâches
  supplémentaire pour laisser une chaîne `.then()`/`await` sans minuteur se
  vider). `self.onerror`/`unhandledrejection` sont aussi capturés pour que les
  erreurs asynchrones remontent proprement. `setInterval` n'est volontairement
  pas suivi (un intervalle oublié ne doit pas bloquer indéfiniment l'envoi du
  résultat — le timeout global de 5s du thread principal reste le filet de
  sécurité final). Validé par une simulation Node fidèle (vm avec les mêmes
  globals que le vrai Worker) sur 9 cas async/erreur/sync, puis par régression
  sur des `solution_code` réels de M3/M4 déjà en base (sortie identique,
  aucune régression). `fetch`/`XMLHttpRequest` restent désactivés (politique
  de non-accès réseau inchangée) : les exercices 6.3 utilisent une fonction
  async simulée (Promise + `setTimeout`) à la place d'un vrai `fetch`.
- **M5 a d'abord été livré sans exercices notés, puis le sandbox DOM a été
  construit comme chantier séparé et M5 complété rétroactivement — résolu.**
  Rappel du blocage initial : le sandbox (`jsSandbox.js`) tournait
  uniquement dans un Web Worker sans `document`/`window` (isolation
  délibérée, `worker.terminate()` garantit le kill d'une boucle infinie).
  Du code utilisant `document.querySelector`/`addEventListener` y levait
  systématiquement une `ReferenceError`. Décision prise (option b de
  l'analyse d'alors) : ajouter `runJsWithDom(code, htmlFixture)`, un second
  mode d'exécution utilisant une vraie iframe sandboxée
  (`sandbox="allow-scripts allow-forms"`, pas de `allow-same-origin` —
  origine opaque, aucun accès aux cookies/storage de la page, aucune
  navigation top-level) plutôt qu'un DOM simulé. Compromis assumé :
  contrairement à `worker.terminate()`, aucune API web n'offre de kill
  garanti d'un script synchrone en cours d'exécution dans une iframe — un
  `while(true)` gèlerait l'onglet jusqu'à fermeture/rechargement (récupérable,
  pas catastrophique, même risque que n'importe quel bac à sable JS grand
  public type CodePen/JSFiddle). Deux filets ajoutés en plus du timeout de
  5s : un rejet heuristique de `while(true)`/`while(1)`/`for(;;)` avant même
  d'exécuter le code, et le sandboxing de l'iframe lui-même (aucune fuite
  possible vers la page ou les autres utilisateurs même en cas de blocage).
  `expected_output`/`normalizeOutput()` restent inchangés côté backend — le
  contrat de retour de `runJsWithDom` est identique à `runJs` (Worker), donc
  aucun nouveau mécanisme de comparaison n'a été nécessaire ; seul un champ
  `exercices.html_fixture` (nullable) a été ajouté au schéma pour porter le
  HTML de départ. Mécanisme vérifié par exécution réelle (Chrome headless
  piloté via le protocole DevTools, pas par lecture de code) avant d'écrire
  le moindre exercice — a permis de détecter deux bugs concrets avant mise
  en prod : `form.requestSubmit()` silencieusement bloqué sans
  `allow-forms`, et un faux timeout dû à un budget de test trop court plutôt
  qu'à un vrai défaut. Les 6 exercices de M5 simulent l'interaction
  utilisateur directement dans le code (`element.click()`,
  `form.requestSubmit()`, déjà fournis dans `starter_code`) puisqu'il n'y a
  personne pour cliquer pendant une correction automatique.
- Pour M3.2 Défi, le scénario ROADMAP initial ("bug de closure dans un
  `setTimeout`") a été adapté en un tableau de fonctions appelées après la
  boucle : `jsSandbox.js` envoie sa sortie capturée dès la fin de l'exécution
  synchrone, donc tout `console.log` dans un vrai callback asynchrone
  (`setTimeout`) n'apparaîtrait jamais dans `output` — le même bug de closure
  reste démontré, sans dépendre d'async (M6 le couvrira).
- Pour M4.3, le scénario ROADMAP initial ("afficher la date du jour") a été
  remplacé par une date fixe en UTC (`Date.UTC` + `Intl.DateTimeFormat` avec
  `timeZone: "UTC"`) : le Worker du sandbox tourne dans le fuseau horaire du
  navigateur de chaque apprenant, donc "aujourd'hui" ou un formatage sans
  fuseau explicite produirait une sortie différente d'un apprenant à l'autre —
  incompatible avec la comparaison stricte à `expected_output`. Même piège à
  éviter pour tout exercice futur touchant à des dates/fuseaux.
- Pour M1/M2/M3/M4, les deux exercices par chapitre (Guidé + Défi) ont été implémentés
  d'un coup, avec `expected_output` calculé hors-ligne via Node (réplique exacte
  de la capture `console.log` de `jsSandbox.js`) plutôt que deviné à l'œil — à
  reproduire pour la suite plutôt que la passe « Guidé d'abord » envisagée
  initialement ci-dessous.
- Chaque module fait est rejouable sans risque : les scripts `seed_moduleN.php`
  ont un garde-fou qui les arrête si le module existe déjà en base.
- `CLAUDE.md` a été mis à jour pour refléter la portée définie ici (curriculum
  JS complet, POO = un module parmi d'autres).

---

## Chantiers techniques futurs

Liste de chantiers identifiés mais volontairement non traités dans le cadre
de la mise en place de la CI (2026-07-19) — priorisation à revoir plus tard.

- **Couvrir les Services PDO par des tests PHPUnit** (`AuthService`,
  `GamificationService`, `ProgressionService`, etc.) — actuellement
  impossible à tester isolément car ils appellent `Database::getConnection()`
  de façon statique, sans point d'injection. Nécessite un petit refactor
  (injection de la connexion) avant de pouvoir écrire ces tests.
- **Tester `jsSandbox.js`** (le sandbox d'exécution en Web Worker) — jsdom
  n'implémente pas les Web Workers, il faudrait soit un mock lourd, soit un
  vrai test runner en navigateur headless (Playwright/Puppeteer).
- **Tester les flux mutants** (inscription, connexion, soumission
  d'exercice) — le test d'intégration actuel est volontairement en lecture
  seule sur la vraie base de dev ; une couverture correcte demanderait une
  base de données de test dédiée.
- **Faire tourner réellement la suite Integration en CI** (pas seulement la
  laisser se skip) — demande un service container MySQL dans le workflow
  GitHub Actions, l'exécution de `backend/database/migrate.php` + au moins un
  `seed_moduleN.php`, et le démarrage de `php -S localhost:8010 -t public/`
  en arrière-plan avant `phpunit`.
- **Audit UX apprenant** — vérifier que le dashboard et la salle des trophées
  font vraiment ressortir la progression/les séries/les badges de façon
  utile (non vérifié en profondeur jusqu'ici).
- **Audit d'accessibilité** — navigation clavier, contraste des couleurs en
  thèmes clair/sombre, comportement lecteur d'écran : rien n'a été vérifié
  systématiquement.
- **Outillage admin d'import/export en masse pour le contenu des cours** — le
  contenu des 9 modules a été écrit à la main via des scripts PHP de seed ;
  une fonctionnalité d'import/export en masse dans le panneau admin
  accélérerait beaucoup la rédaction de contenu future.
- **Revisiter les deux limitations connues du sandbox déjà documentées
  ci-dessus dans "Notes de suivi"** : M5 (DOM — pas de `document` dans le
  Worker) et M8.2 (modules ES6 — `import`/`export` lève une SyntaxError sous
  `new Function()`). Voir les entrées correspondantes plus haut pour le
  raisonnement détaillé, non répété ici.
