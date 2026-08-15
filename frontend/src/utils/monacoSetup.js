// Auto-hébergement de l'éditeur Monaco.
//
// Par défaut, @monaco-editor/react ne contient pas Monaco : il le télécharge à
// l'exécution depuis cdn.jsdelivr.net. Acceptable en développement, pas pour
// une mise en ligne publique — cela reviendrait à exécuter sur chaque page un
// script tiers disposant des pleins droits (dont l'accès au token JWT gardé en
// mémoire), à laisser l'éditeur tomber en panne dès que le CDN est
// injoignable, et à transmettre l'adresse IP de chaque visiteur à un tiers
// sans que rien ne le mentionne.
//
// loader.config({ monaco }) indique à @monaco-editor/react d'utiliser
// l'instance importée depuis node_modules, donc empaquetée par Vite et servie
// par notre propre nginx. Plus aucune requête ne sort vers un CDN.
//
// Ce module a un effet de bord et doit s'exécuter AVANT le premier rendu d'un
// <Editor>. Il est importé une seule fois, depuis components/common/
// MonacoEditor.jsx, lui-même chargé en import() dynamique : la configuration
// voyage ainsi dans le même chunk paresseux que Monaco. Il n'est
// volontairement importé ni depuis main.jsx (Monaco retomberait dans le chunk
// d'entrée) ni depuis ExerciceSolver/TheorieManager (les imports `?worker`
// ci-dessous sont propres à Vite et casseraient le rendu de ces composants
// sous Vitest).

import * as monaco from 'monaco-editor';
import { loader } from '@monaco-editor/react';

// La syntaxe `?worker` est celle de Vite : chaque import produit un bundle de
// worker séparé, et le constructeur importé instancie ce worker.
import editorWorker from 'monaco-editor/esm/vs/editor/editor.worker?worker';
import jsonWorker from 'monaco-editor/esm/vs/language/json/json.worker?worker';
import cssWorker from 'monaco-editor/esm/vs/language/css/css.worker?worker';
import htmlWorker from 'monaco-editor/esm/vs/language/html/html.worker?worker';
import tsWorker from 'monaco-editor/esm/vs/language/typescript/ts.worker?worker';

// Monaco délègue l'analyse du code à des Web Workers ; sans MonacoEnvironment,
// il tenterait de les charger depuis une URL par défaut qui n'existe pas dans
// un build Vite, et l'éditeur perdrait silencieusement autocomplétion et
// diagnostics tout en continuant de s'afficher.
//
// Les deux langages réellement utilisés sont `javascript` (ExerciceSolver) et
// `html` (TheorieManager) ; css et json accompagnent le service HTML, qui
// analyse aussi les <style> et attributs embarqués.
self.MonacoEnvironment = {
  getWorker(_workerId, label) {
    switch (label) {
      case 'json':
        return new jsonWorker();
      case 'css':
      case 'scss':
      case 'less':
        return new cssWorker();
      case 'html':
      case 'handlebars':
      case 'razor':
        return new htmlWorker();
      case 'typescript':
      case 'javascript':
        return new tsWorker();
      default:
        return new editorWorker();
    }
  },
};

loader.config({ monaco });
