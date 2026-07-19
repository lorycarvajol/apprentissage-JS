/**
 * Traduit les erreurs JS les plus fréquentes chez un débutant en explications
 * en français simple. Le message technique original est toujours conservé
 * (jamais caché) — cette fonction ajoute une explication, elle ne remplace rien.
 *
 * Contrairement au projet PHP jumeau, où CodeExecutionService écrivait les
 * erreurs PHP dans la sortie standard (result.output), ici le sandbox client
 * (voir jsSandbox.js) capture l'exception JS et la renvoie sous la forme
 * "NomErreur: message" dans result.error — c'est ce texte qui est analysé ici.
 */

const ERROR_PATTERNS = [
  {
    regex: /^SyntaxError: Unexpected token '?([^'\n]+)'?/,
    friendly: (m) =>
      `Erreur de syntaxe : JavaScript ne s'attendait pas à voir "${m[1]}" à cet endroit. Vérifiez les accolades { }, parenthèses ( ) et points-virgules ; juste avant dans votre code.`,
  },
  {
    regex: /^SyntaxError: Unexpected end of input/,
    friendly: () =>
      "Il manque une accolade fermante } ou une parenthèse fermante ) quelque part : le code se termine avant que tout ce qui a été ouvert soit refermé.",
  },
  {
    regex: /^SyntaxError: Missing initializer in const declaration/,
    friendly: () =>
      "Une variable déclarée avec const doit recevoir une valeur dès sa déclaration (const x = ...;) — elle ne peut pas être assignée plus tard.",
  },
  {
    regex: /^ReferenceError: (\w+) is not defined/,
    friendly: (m) =>
      `La variable ou fonction "${m[1]}" est utilisée mais n'a jamais été déclarée. Vérifiez l'orthographe, ou déclarez-la avec let/const/function avant de l'utiliser.`,
  },
  {
    regex: /^TypeError: Cannot read propert(?:y|ies) '?of undefined \(reading '([^']+)'\)/,
    friendly: (m) =>
      `Vous essayez de lire la propriété "${m[1]}" sur une valeur qui vaut undefined à ce moment-là. Vérifiez que l'objet a bien été créé/initialisé avant d'y accéder.`,
  },
  {
    regex: /^TypeError: Cannot read propert(?:y|ies) of null \(reading '([^']+)'\)/,
    friendly: (m) =>
      `Vous essayez de lire la propriété "${m[1]}" sur une valeur null. Vérifiez que la variable contient bien un objet avant d'y accéder.`,
  },
  {
    regex: /^TypeError: (\S+) is not a function/,
    friendly: (m) =>
      `"${m[1]}" n'est pas une fonction. Vérifiez l'orthographe de son nom, ou qu'elle est bien définie avant d'être appelée.`,
  },
  {
    regex: /^TypeError: Assignment to constant variable\./,
    friendly: () =>
      "Vous essayez de réassigner une variable déclarée avec const. Utilisez let si sa valeur doit pouvoir changer.",
  },
  {
    regex: /^RangeError: Maximum call stack size exceeded/,
    friendly: () =>
      "Votre code entre dans une récursion infinie (une fonction s'appelle elle-même sans jamais s'arrêter). Vérifiez la condition d'arrêt de votre fonction récursive.",
  },
];

/**
 * @param {?string} rawError - le contenu de `result.error` renvoyé par jsSandbox.js
 * @returns {{ friendly: string, technical: string } | null} null si ce n'est
 *   pas une erreur JS reconnaissable (ex: pas d'erreur du tout)
 */
export function translateJsError(rawError) {
  if (!rawError || typeof rawError !== 'string') {
    return null;
  }

  for (const pattern of ERROR_PATTERNS) {
    const match = rawError.match(pattern.regex);
    if (match) {
      return {
        friendly: pattern.friendly(match),
        technical: rawError.trim(),
      };
    }
  }

  return {
    friendly: "JavaScript a rencontré une erreur en exécutant votre code. Le détail technique ci-dessous peut vous aider à localiser le problème.",
    technical: rawError.trim(),
  };
}
