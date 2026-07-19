// Exécution du code JS soumis par l'apprenant, côté client. C'est l'équivalent
// pour ce projet de CodeExecutionService.php (backend PHP jumeau) : mêmes
// garanties recherchées (isolation, timeout, sortie capturée) mais un moteur
// différent, puisqu'il n'y a pas de serveur JS ici.
//
// Un Worker dédié (pas une iframe) est utilisé car worker.terminate() coupe
// immédiatement l'exécution — y compris une boucle infinie synchrone — ce
// qu'une simple suppression d'iframe ne garantit pas. Le Worker tourne dans
// un scope isolé (pas de `document`/`window`, pas d'accès au DOM ou aux
// cookies de la page), et fetch/XMLHttpRequest/importScripts y sont
// neutralisés pour rester dans l'esprit du sandbox PHP (pas de réseau).
//
// Le Worker attend la fin du code asynchrone (setTimeout/Promise/async-await)
// avant de renvoyer sa sortie capturée — voir waitForPendingWork ci-dessous.
// Sans cette attente, postMessage partait immédiatement après l'exécution
// synchrone, avant que la moindre microtâche (Promise/await) ou macrotâche
// (setTimeout) n'ait eu la chance de s'exécuter : tout console.log placé
// dans un callback asynchrone était alors silencieusement perdu, sans erreur
// — un bug découvert à l'écriture du module 6 (JavaScript asynchrone) et
// corrigé ici plutôt que travaillé autour dans chaque exercice.

const TIMEOUT_MS = 5000;

const WORKER_SOURCE = `
self.fetch = undefined;
self.XMLHttpRequest = undefined;
self.importScripts = undefined;

function stringifyArg(arg) {
  if (typeof arg === 'string') return arg;
  if (arg === undefined) return 'undefined';
  if (arg instanceof Error) return arg.name + ': ' + arg.message;
  try {
    return JSON.stringify(arg);
  } catch (e) {
    return String(arg);
  }
}

// setTimeout est remplacé par une version qui compte les minuteurs encore en
// attente (pendingTimers), pour savoir quand tout le code asynchrone du code
// soumis est réellement terminé. setInterval n'est volontairement PAS suivi :
// un intervalle jamais nettoyé ne doit pas bloquer indéfiniment l'envoi du
// résultat (le timeout global de 5s côté thread principal reste le filet de
// sécurité final dans tous les cas).
let pendingTimers = 0;
const nativeSetTimeout = self.setTimeout;

self.setTimeout = function (fn, delay, ...args) {
  pendingTimers++;
  return nativeSetTimeout(function () {
    pendingTimers--;
    if (typeof fn === 'function') fn(...args);
  }, delay);
};

self.onmessage = function (event) {
  const logs = [];
  const capture = (...args) => {
    logs.push(args.map(stringifyArg).join(' '));
  };
  console.log = capture;
  console.info = capture;
  console.warn = capture;
  console.error = capture;

  let capturedError = null;

  self.onerror = function (message, source, lineno, colno, error) {
    if (capturedError === null) {
      capturedError = error && error.message ? \`\${error.name}: \${error.message}\` : String(message);
    }
    return true;
  };
  self.addEventListener('unhandledrejection', function (evt) {
    if (capturedError === null) {
      const reason = evt.reason;
      capturedError = reason && reason.message
        ? \`\${reason.name}: \${reason.message}\`
        : String(reason);
    }
  });

  try {
    const runner = new Function(event.data.code);
    runner();
  } catch (err) {
    capturedError = err && err.message ? \`\${err.name}: \${err.message}\` : String(err);
  }

  function post() {
    self.postMessage({ output: logs.join('\\n'), error: capturedError });
  }

  function waitForPendingWork() {
    if (pendingTimers > 0) {
      nativeSetTimeout(waitForPendingWork, 15);
      return;
    }
    // un tour de plus pour laisser les microtâches en attente (Promise.then,
    // continuation après await) se vider avant de conclure que tout est fini
    nativeSetTimeout(function () {
      if (pendingTimers > 0) {
        waitForPendingWork();
      } else {
        post();
      }
    }, 0);
  }

  waitForPendingWork();
};
`;

/**
 * Détecte les boucles infinies synchrones les plus évidentes (while(true),
 * while(1), for(;;)) avant même de lancer le code. Filet de sécurité en
 * complément — pas en remplacement — du timeout : voir runJsWithDom()
 * ci-dessous pour la raison exacte pour laquelle ce filet supplémentaire
 * existe uniquement pour l'exécution avec DOM, pas pour runJs().
 */
const OBVIOUS_INFINITE_LOOP = /\bwhile\s*\(\s*(?:true|1)\s*\)|\bfor\s*\(\s*;\s*;\s*\)/;

/**
 * Exécute du code JS dans un Worker isolé, avec un timeout, et retourne la
 * sortie capturée (console.log/warn/error/info) ainsi qu'une éventuelle
 * erreur — au même format que ce qu'attend le backend pour /submit.
 *
 * @returns {Promise<{output: string, error: ?string, timedOut: boolean}>}
 */
export function runJs(code, { timeoutMs = TIMEOUT_MS } = {}) {
  return new Promise((resolve) => {
    const blob = new Blob([WORKER_SOURCE], { type: 'application/javascript' });
    const workerUrl = URL.createObjectURL(blob);
    const worker = new Worker(workerUrl);

    let settled = false;

    const cleanup = () => {
      worker.terminate();
      URL.revokeObjectURL(workerUrl);
    };

    const timer = setTimeout(() => {
      if (settled) return;
      settled = true;
      cleanup();
      resolve({ output: '', error: null, timedOut: true });
    }, timeoutMs);

    worker.onmessage = (event) => {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      cleanup();
      resolve({
        output: event.data.output || '',
        error: event.data.error || null,
        timedOut: false,
      });
    };

    worker.onerror = (event) => {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      cleanup();
      resolve({ output: '', error: event.message || 'Erreur inconnue', timedOut: false });
    };

    worker.postMessage({ code });
  });
}

// ============================================================================
// runJsWithDom : exécution avec accès DOM réel, pour les exercices du
// module 5 (querySelector, addEventListener, createElement...).
//
// Le Worker ci-dessus n'a délibérément pas de `document`/`window` -- aucun
// contexte de navigation (iframe compris) n'expose d'équivalent à
// worker.terminate() capable de couper une boucle infinie synchrone déjà en
// cours d'exécution. C'est un choix assumé (voir ROADMAP.md, section M5) :
// une vraie iframe sandboxée offre un DOM fidèle à 100% (zéro simulation,
// zéro écart de comportement avec un vrai navigateur) au prix d'un risque
// résiduel -- un while(true) synchrone gèlerait l'onglet jusqu'à fermeture/
// rechargement, récupérable mais pas un kill garanti. Deux filets en plus du
// timeout habituel : OBVIOUS_INFINITE_LOOP rejette les motifs les plus
// évidents avant même de lancer le code, et l'iframe est sandboxée sans
// allow-same-origin (origine opaque : aucun accès aux cookies/storage de la
// page, aucune navigation top-level, aucun popup).
// ============================================================================

function buildIframeSrcdoc(htmlFixture, code) {
  // JSON.stringify échappe proprement guillemets/retours à la ligne pour
  // obtenir un littéral JS valide, mais PAS "</script>" -- si le code de
  // l'apprenant contient cette sous-chaîne (ex: dans un console.log), le
  // parseur HTML fermerait notre balise <script> en plein milieu. On casse
  // la séquence sans changer la valeur runtime de la chaîne (\/ dans un
  // littéral JS vaut simplement /).
  const escapedCode = JSON.stringify(code).replace(/<\/script/gi, '<\\/script');

  return `<!doctype html>
<html>
<head><meta charset="utf-8"></head>
<body>
${htmlFixture || ''}
<script>
(function () {
  self.fetch = undefined;
  self.XMLHttpRequest = undefined;

  function stringifyArg(arg) {
    if (typeof arg === 'string') return arg;
    if (arg === undefined) return 'undefined';
    if (arg instanceof Error) return arg.name + ': ' + arg.message;
    try {
      return JSON.stringify(arg);
    } catch (e) {
      return String(arg);
    }
  }

  const logs = [];
  const capture = function () {
    logs.push(Array.prototype.slice.call(arguments).map(stringifyArg).join(' '));
  };
  console.log = capture;
  console.info = capture;
  console.warn = capture;
  console.error = capture;

  let pendingTimers = 0;
  const nativeSetTimeout = window.setTimeout.bind(window);
  window.setTimeout = function (fn, delay) {
    const extraArgs = Array.prototype.slice.call(arguments, 2);
    pendingTimers++;
    return nativeSetTimeout(function () {
      pendingTimers--;
      if (typeof fn === 'function') fn.apply(null, extraArgs);
    }, delay);
  };

  let capturedError = null;

  window.onerror = function (message, source, lineno, colno, error) {
    if (capturedError === null) {
      capturedError = error && error.message ? error.name + ': ' + error.message : String(message);
    }
    return true;
  };
  window.addEventListener('unhandledrejection', function (evt) {
    if (capturedError === null) {
      const reason = evt.reason;
      capturedError = reason && reason.message
        ? reason.name + ': ' + reason.message
        : String(reason);
    }
  });

  try {
    const runner = new Function(${escapedCode});
    runner();
  } catch (err) {
    capturedError = err && err.message ? err.name + ': ' + err.message : String(err);
  }

  function post() {
    parent.postMessage({ output: logs.join('\\n'), error: capturedError }, '*');
  }

  function waitForPendingWork() {
    if (pendingTimers > 0) {
      nativeSetTimeout(waitForPendingWork, 15);
      return;
    }
    nativeSetTimeout(function () {
      if (pendingTimers > 0) {
        waitForPendingWork();
      } else {
        post();
      }
    }, 0);
  }

  waitForPendingWork();
})();
</script>
</body>
</html>`;
}

/**
 * Exécute du code JS avec un vrai DOM (querySelector, addEventListener,
 * createElement...) dans une iframe sandboxée (sandbox="allow-scripts", pas
 * de allow-same-origin -- origine opaque), initialisée avec htmlFixture
 * comme contenu de départ. Même contrat de retour que runJs(), pour que le
 * reste de la chaîne (ExerciceSolver, comparaison à expected_output côté
 * backend) n'ait besoin d'aucun changement.
 *
 * @param {string} code
 * @param {string} htmlFixture - HTML de départ inséré dans <body> avant exécution
 * @returns {Promise<{output: string, error: ?string, timedOut: boolean}>}
 */
export function runJsWithDom(code, htmlFixture, { timeoutMs = TIMEOUT_MS } = {}) {
  return new Promise((resolve) => {
    if (OBVIOUS_INFINITE_LOOP.test(code)) {
      resolve({
        output: '',
        error: "Boucle infinie détectée : évitez while(true)/while(1)/for(;;) sans condition d'arrêt claire.",
        timedOut: false,
      });
      return;
    }

    const iframe = document.createElement('iframe');
    // allow-forms : sans ce flag, form.requestSubmit()/la soumission réelle
    // d'un formulaire sont silencieusement bloqués (aucune erreur, aucun
    // événement "submit" déclenché) -- constaté en testant un exercice de
    // validation de formulaire. Reste sans risque : sans allow-same-origin
    // ni allow-top-navigation, une soumission qui échapperait à un
    // preventDefault() oublié ne peut naviguer qu'à l'intérieur de l'iframe
    // elle-même, jamais la page parente -- l'exercice échoue proprement
    // (timeout, plus de postMessage) plutôt que de réussir à tort.
    iframe.setAttribute('sandbox', 'allow-scripts allow-forms');
    iframe.style.display = 'none';

    let settled = false;

    const cleanup = () => {
      window.removeEventListener('message', onMessage);
      if (iframe.parentNode) {
        iframe.parentNode.removeChild(iframe);
      }
    };

    const timer = setTimeout(() => {
      if (settled) return;
      settled = true;
      cleanup();
      resolve({ output: '', error: null, timedOut: true });
    }, timeoutMs);

    function onMessage(event) {
      if (event.source !== iframe.contentWindow) return;
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      cleanup();
      resolve({
        output: event.data.output || '',
        error: event.data.error || null,
        timedOut: false,
      });
    }

    window.addEventListener('message', onMessage);

    iframe.srcdoc = buildIframeSrcdoc(htmlFixture, code);
    document.body.appendChild(iframe);
  });
}
