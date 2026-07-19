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

self.onmessage = function (event) {
  const logs = [];
  const capture = (...args) => {
    logs.push(args.map(stringifyArg).join(' '));
  };
  console.log = capture;
  console.info = capture;
  console.warn = capture;
  console.error = capture;

  try {
    const runner = new Function(event.data.code);
    runner();
    self.postMessage({ output: logs.join('\\n'), error: null });
  } catch (err) {
    const message = err && err.message ? \`\${err.name}: \${err.message}\` : String(err);
    self.postMessage({ output: logs.join('\\n'), error: message });
  }
};
`;

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
