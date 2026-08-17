import { useState, useRef } from 'react';
import Editor from '../common/CodeEditor';
import api from '../../services/api';
import JsCheatSheet from './JsCheatSheet';
import { runJs, runJsWithDom } from '../../utils/jsSandbox';
import { translateJsError } from '../../utils/jsErrorTranslator';
import { useTrophees } from '../../contexts/TropheesContext';
import '../../styles/Editor.css';

const ExerciceSolver = ({ exercice, onClose }) => {
  const [code, setCode] = useState(exercice.starter_code || '');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [result, setResult] = useState(null);
  const [showCheatSheet, setShowCheatSheet] = useState(false);
  const startedAtRef = useRef(Date.now());
  const { rafraichir: rafraichirTrophees } = useTrophees();

  const handleSubmit = async () => {
    setIsSubmitting(true);
    setResult(null);

    const timeSpent = Math.round((Date.now() - startedAtRef.current) / 1000);

    try {
      // Contrairement au projet PHP jumeau (où le serveur exécutait le code
      // soumis via un subprocess), le code JS est exécuté ici même, dans le
      // navigateur de l'apprenant (sandbox Worker par défaut, ou iframe
      // sandboxée avec un vrai DOM si l'exercice a un html_fixture -- voir
      // jsSandbox.js). Le backend ne reçoit que le résultat déjà capturé et
      // se contente de le comparer à expected_output pour la notation.
      const executed = exercice.html_fixture
        ? await runJsWithDom(code, exercice.html_fixture)
        : await runJs(code);

      const response = await api.post(`/exercices/${exercice.id}/submit`, {
        code,
        output: executed.output,
        error: executed.error,
        timed_out: executed.timedOut,
        time_spent: timeSpent,
      });
      setResult(response.data);

      /**
       * La soumission peut décrocher un ou plusieurs titres — le backend appelle
       * GamificationService::checkAndAwardBadges() — mais sa réponse ne les
       * énumère pas. On relit donc la collection pour que l'onglet « Trophées »
       * s'allume dans la seconde, et non au prochain chargement de page.
       *
       * Sans await ni catch remontant : l'exercice vient d'être noté, une
       * lueur qui n'apparaît pas ne doit surtout pas transformer une réussite
       * en message d'erreur.
       */
      if (response.data?.is_correct) {
        rafraichirTrophees();
      }
    } catch (err) {
      setResult({
        is_correct: false,
        error: err.response?.data?.error || 'Une erreur est survenue lors de la soumission.',
        output: '',
        timed_out: false,
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleReset = () => {
    setCode(exercice.starter_code || '');
    setResult(null);
  };

  // Les erreurs JS (SyntaxError, TypeError, ReferenceError...) arrivent dans
  // result.error, capturées côté client par le sandbox — voir jsSandbox.js.
  const jsError = result && !result.is_correct ? translateJsError(result.error) : null;

  return (
    <div className="fullscreen-editor">
      <div className="editor-header">
        <div className="editor-header-left">
          <h2>{exercice.title}</h2>
          <span className="editor-id">
            {exercice.difficulty === 'easy' && '🟢 Facile'}
            {exercice.difficulty === 'medium' && '🟡 Moyen'}
            {exercice.difficulty === 'hard' && '🔴 Difficile'}
            {' · '}⭐ {exercice.points} pts
          </span>
        </div>
        <div className="editor-header-right">
          <button type="button" className="btn btn-sm btn-secondary" onClick={() => setShowCheatSheet(true)}>
            📎 Aide-mémoire
          </button>
          <button type="button" className="btn btn-sm btn-secondary" onClick={handleReset}>
            ↺ Réinitialiser
          </button>
          <button type="button" className="btn btn-sm btn-secondary" onClick={onClose}>
            ✕ Fermer
          </button>
        </div>
      </div>

      {showCheatSheet && <JsCheatSheet onClose={() => setShowCheatSheet(false)} />}

      <div className="editor-form">
        <div className="editor-sidebar">
          <div className="editor-sidebar-section">
            <h3>Énoncé</h3>
            <p style={{ color: '#ccc', fontSize: '13px', lineHeight: 1.6 }}>
              {exercice.description}
            </p>
          </div>

          {exercice.instructions && (
            <div className="editor-sidebar-section">
              <h3>Instructions</h3>
              <p style={{ color: '#ccc', fontSize: '13px', lineHeight: 1.6 }}>
                {exercice.instructions}
              </p>
            </div>
          )}

          {exercice.html_fixture && (
            <div className="editor-sidebar-section">
              <h3>HTML de départ</h3>
              <p style={{ color: '#ccc', fontSize: '12px', lineHeight: 1.6, marginBottom: '8px' }}>
                Cette page existe déjà quand votre code s'exécute :
              </p>
              <pre style={{ background: '#1e1e1e', color: '#f8f8f2', padding: '12px', borderRadius: '6px', fontSize: '12px', whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>
                {exercice.html_fixture}
              </pre>
            </div>
          )}

          <div className="editor-actions">
            <button
              type="button"
              className="btn btn-primary btn-block"
              onClick={handleSubmit}
              disabled={isSubmitting}
            >
              {isSubmitting ? 'Exécution...' : '▶️ Exécuter et soumettre'}
            </button>
          </div>
        </div>

        <div className="editor-main">
          <div className="editor-main-header">
            <label className="form-label">Votre code</label>
            <span className="editor-hint">Complétez les TODO puis soumettez</span>
          </div>

          <div className="monaco-wrapper">
            <Editor
              height="100%"
              defaultLanguage="javascript"
              value={code}
              onChange={(value) => setCode(value || '')}
              theme="vs-dark"
              options={{
                minimap: { enabled: false },
                fontSize: 14,
                lineNumbers: 'on',
                wordWrap: 'on',
                scrollBeyondLastLine: false,
              }}
            />
          </div>
        </div>

        <div className="editor-preview">
          <div className="editor-preview-header">
            <h3>Résultat</h3>
          </div>
          <div className="editor-preview-content">
            {!result && !isSubmitting && (
              <p style={{ color: '#999' }}>
                Cliquez sur « Exécuter et soumettre » pour tester votre code.
              </p>
            )}

            {isSubmitting && <p>Exécution en cours...</p>}

            {result && (
              <div>
                <div className={`alert ${result.is_correct ? 'alert-success' : 'alert-warning'}`}>
                  {result.is_correct
                    ? `✓ Correct ! ${result.points_earned > 0 ? `+${result.points_earned} points` : '(déjà validé précédemment)'}`
                    : result.timed_out
                      ? '⏱️ Ton code met trop de temps à s\'exécuter — vérifie qu\'il n\'y a pas de boucle infinie.'
                      : jsError
                        ? '🛠️ Ton code contient une erreur qui l\'empêche de s\'exécuter.'
                        : '🤔 Pas tout à fait — compare ta sortie avec celle attendue ci-dessous et ajuste ton code.'}
                </div>

                {jsError ? (
                  <>
                    <div className="alert alert-error" style={{ marginTop: '16px' }}>
                      💡 {jsError.friendly}
                    </div>
                    <details className="js-error-technical">
                      <summary>Voir le message technique</summary>
                      <pre style={{ background: '#1e1e1e', color: '#f8b4b4', padding: '12px', borderRadius: '6px', fontSize: '12px', whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>
                        {jsError.technical}
                      </pre>
                    </details>
                  </>
                ) : (
                  <>
                    <h4 style={{ marginTop: '20px', marginBottom: '8px', fontSize: '13px' }}>Sortie obtenue :</h4>
                    <pre style={{ background: '#1e1e1e', color: '#f8f8f2', padding: '12px', borderRadius: '6px', fontSize: '13px', whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>
                      {result.output || '(aucune sortie)'}
                    </pre>

                    {!result.is_correct && result.expected_output && (
                      <>
                        <h4 style={{ marginTop: '16px', marginBottom: '8px', fontSize: '13px' }}>Sortie attendue :</h4>
                        <pre style={{ background: '#1e1e1e', color: '#8b9bb4', padding: '12px', borderRadius: '6px', fontSize: '13px', whiteSpace: 'pre-wrap' }}>
                          {result.expected_output}
                        </pre>
                      </>
                    )}
                  </>
                )}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default ExerciceSolver;
