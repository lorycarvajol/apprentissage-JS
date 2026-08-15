import { lazy, Suspense } from 'react';

// Remplaçant direct de `import Editor from '@monaco-editor/react'` : mêmes
// props, mais l'éditeur n'est téléchargé qu'au moment où il s'affiche.
//
// Monaco est auto-hébergé (plus de CDN tiers, voir utils/monacoSetup.js), donc
// empaqueté avec l'application. Sans ce découpage, ses ~4 Mo se retrouveraient
// dans le chunk d'entrée et seraient téléchargés dès la page de connexion,
// alors que seuls ExerciceSolver et TheorieManager en ont besoin.
const MonacoEditor = lazy(() => import('./MonacoEditor.jsx'));

const CodeEditor = (props) => (
  <Suspense fallback={<div className="editor-loading">Chargement de l'éditeur…</div>}>
    <MonacoEditor {...props} />
  </Suspense>
);

export default CodeEditor;
