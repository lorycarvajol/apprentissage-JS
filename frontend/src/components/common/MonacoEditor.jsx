// Point d'entrée réel de Monaco, chargé à la demande.
//
// Ce module n'est JAMAIS importé statiquement : CodeEditor.jsx le charge en
// import() dynamique. C'est ce qui isole Monaco (~4 Mo) dans un chunk séparé,
// téléchargé seulement quand un éditeur est réellement affiché — la page de
// connexion, le tableau de bord et la liste des modules n'en paient pas le
// coût.
//
// L'import à effet de bord ci-dessous configure Monaco pour être servi depuis
// notre propre bundle plutôt que depuis un CDN tiers (voir utils/monacoSetup.js).
// Il est ici, et pas dans main.jsx, pour que Monaco et sa configuration
// atterrissent dans le même chunk paresseux.
import '../../utils/monacoSetup.js';
import Editor from '@monaco-editor/react';

export default Editor;
