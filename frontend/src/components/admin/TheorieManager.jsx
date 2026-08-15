import { useState, useEffect, useMemo } from 'react';
import { getModules, getChapitres, getTheories } from '../../services/contentService';
import { createTheorie, updateTheorie, deleteTheorie } from '../../services/adminService';
import Editor from '../common/CodeEditor';
import '../../styles/Editor.css';

const TheorieManager = () => {
  const [theories, setTheories] = useState([]);
  const [chapitres, setChapitres] = useState([]);
  const [modules, setModules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showEditor, setShowEditor] = useState(false);
  const [editingTheorie, setEditingTheorie] = useState(null);
  // Aucun module sélectionné par défaut : la sidebar reste compacte et
  // affiche tout le parcours d'un coup d'œil, sans qu'aucune liste ne
  // s'ouvre tant que l'admin n'a pas cliqué dessus.
  const [selectedModuleId, setSelectedModuleId] = useState(null);
  const [formData, setFormData] = useState({
    chapitre_id: '',
    title: '',
    content: '',
    order_index: '',
    estimated_time: ''
  });
  const [message, setMessage] = useState({ type: '', text: '' });
  const [showPreview, setShowPreview] = useState(false);
  const [previewWindow, setPreviewWindow] = useState(null);
  const [editorRef, setEditorRef] = useState(null);

  useEffect(() => {
    fetchData();
  }, []);

  // Module du chapitre en cours d'édition, pour surligner la bonne entrée
  // dans la sidebar de la preview (même composant SidebarNavigation que sur
  // la vraie page).
  const currentModuleId = chapitres.find((c) => c.id === formData.chapitre_id)?.module_id ?? null;

  // Envoyer les mises à jour du contenu vers la fenêtre de preview
  useEffect(() => {
    if (previewWindow && !previewWindow.closed && formData.content) {
      previewWindow.postMessage({
        type: 'PREVIEW_UPDATE',
        content: formData.content,
        title: formData.title || 'Aperçu',
        moduleId: currentModuleId
      }, window.location.origin);
    }
  }, [formData.content, formData.title, previewWindow, currentModuleId]);

  // Renvoyer immédiatement le contenu courant dès que la fenêtre de preview
  // signale qu'elle est montée, plutôt que de deviner via un polling à durée fixe
  // (qui peut louper la fenêtre si elle met plus de 2s à charger, ex: premier
  // démarrage de Vite).
  useEffect(() => {
    const handlePreviewReady = (event) => {
      if (event.data?.type === 'PREVIEW_READY' && previewWindow && !previewWindow.closed) {
        previewWindow.postMessage({
          type: 'PREVIEW_UPDATE',
          content: formData.content,
          title: formData.title || 'Aperçu',
          moduleId: currentModuleId
        }, window.location.origin);
      }
    };

    window.addEventListener('message', handlePreviewReady);
    return () => window.removeEventListener('message', handlePreviewReady);
  }, [previewWindow, formData.content, formData.title, currentModuleId]);

  const fetchData = async () => {
    try {
      setLoading(true);
      const [theoriesResponse, chapitresResponse, modulesResponse] = await Promise.all([
        getTheories(),
        getChapitres(false),
        getModules(false)
      ]);
      setTheories(theoriesResponse.theories || []);
      setChapitres(chapitresResponse.chapitres || []);
      setModules(modulesResponse.modules || []);
    } catch (error) {
      console.error('Erreur:', error);
      setMessage({ type: 'error', text: 'Erreur lors du chargement des données' });
    } finally {
      setLoading(false);
    }
  };

  // Modules triés par ordre d'affichage réel (order_index), et non par id
  // technique : rien ne garantit que les deux coïncident (modules réordonnés
  // après coup, suppressions, etc.).
  const sortedModules = useMemo(
    () => [...modules].sort((a, b) => a.order_index - b.order_index),
    [modules]
  );

  // Chapitres groupés par module, triés par leur propre order_index.
  const chapitresByModule = useMemo(() => {
    const map = new Map();
    for (const chapitre of chapitres) {
      if (!map.has(chapitre.module_id)) {
        map.set(chapitre.module_id, []);
      }
      map.get(chapitre.module_id).push(chapitre);
    }
    for (const list of map.values()) {
      list.sort((a, b) => a.order_index - b.order_index);
    }
    return map;
  }, [chapitres]);

  // Théories groupées par chapitre, triées par leur propre order_index.
  const theoriesByChapitre = useMemo(() => {
    const map = new Map();
    for (const theorie of theories) {
      if (!map.has(theorie.chapitre_id)) {
        map.set(theorie.chapitre_id, []);
      }
      map.get(theorie.chapitre_id).push(theorie);
    }
    for (const list of map.values()) {
      list.sort((a, b) => a.order_index - b.order_index);
    }
    return map;
  }, [theories]);

  // Un libellé "Module N › Chapitre M : Titre" pour lever toute ambiguïté
  // dans le select (deux chapitres de modules différents peuvent avoir des
  // titres proches, et l'ordre affiché seul ne suffit pas à les distinguer).
  const chapitreLabel = (chapitre) => {
    const module = modules.find((m) => m.id === chapitre.module_id);
    const modulePrefix = module ? `Module ${module.order_index} › ` : '';
    return `${modulePrefix}Chapitre ${chapitre.order_index} : ${chapitre.title}`;
  };

  // Prochain ordre d'affichage suggéré pour une nouvelle théorie : le suivant
  // de la dernière théorie existante du chapitre choisi (et non la longueur
  // totale de toutes les théories, sans rapport avec ce chapitre précis).
  const suggestNextOrderIndex = (chapitreId) => {
    const existing = theoriesByChapitre.get(chapitreId) || [];
    if (existing.length === 0) return 1;
    return Math.max(...existing.map((t) => t.order_index)) + 1;
  };

  const handleOpenEditor = (theorie = null, presetChapitreId = null) => {
    if (theorie) {
      setEditingTheorie(theorie);
      setFormData({
        chapitre_id: theorie.chapitre_id,
        title: theorie.title,
        content: theorie.content || '',
        order_index: theorie.order_index,
        estimated_time: theorie.estimated_time || ''
      });
    } else {
      const selectedModuleChapitres = selectedModuleId
        ? chapitres.filter((c) => c.module_id === selectedModuleId)
        : [];
      const chapitreId = presetChapitreId
        ?? (selectedModuleChapitres.length > 0 ? selectedModuleChapitres[0].id : null)
        ?? (chapitres.length > 0 ? chapitres[0].id : '');
      setEditingTheorie(null);
      setFormData({
        chapitre_id: chapitreId,
        title: '',
        content: '',
        order_index: chapitreId ? suggestNextOrderIndex(chapitreId) : '',
        estimated_time: ''
      });
    }
    setShowEditor(true);
    setMessage({ type: '', text: '' });
  };

  // Quand on change de chapitre dans le formulaire (création uniquement), on
  // recalcule l'ordre suggéré pour ce chapitre précis plutôt que de laisser
  // un chiffre qui ne correspond à rien pour le nouveau chapitre choisi.
  const handleChapitreChange = (newChapitreId) => {
    setFormData((prev) => ({
      ...prev,
      chapitre_id: newChapitreId,
      order_index: editingTheorie ? prev.order_index : suggestNextOrderIndex(newChapitreId)
    }));
  };

  const handleCloseEditor = () => {
    if (window.confirm('Êtes-vous sûr de vouloir fermer ? Les modifications non sauvegardées seront perdues.')) {
      // Fermer la fenêtre de preview si elle est ouverte
      if (previewWindow && !previewWindow.closed) {
        previewWindow.close();
      }
      setShowEditor(false);
      setEditingTheorie(null);
      setFormData({ chapitre_id: '', title: '', content: '', order_index: '', estimated_time: '' });
      setShowPreview(false);
      setPreviewWindow(null);
      setEditorRef(null);
    }
  };

  const handleOpenPreviewWindow = () => {
    // Si la fenêtre existe déjà et n'est pas fermée, la mettre au premier plan
    if (previewWindow && !previewWindow.closed) {
      previewWindow.focus();
      return;
    }

    // Ouvrir une nouvelle fenêtre de preview
    const width = 1200;
    const height = 800;
    const left = window.screenX + (window.outerWidth - width) / 2;
    const top = window.screenY + (window.outerHeight - height) / 2;

    const newWindow = window.open(
      '/preview',
      'preview',
      `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
    );

    // window.open renvoie null (ou une fenêtre déjà fermée) si le navigateur
    // bloque le popup — sans ce contrôle, l'échec est silencieux et donne
    // l'impression que la preview "ne se synchronise pas".
    if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
      alert('La fenêtre de preview a été bloquée par le navigateur. Autorisez les popups pour ce site puis réessayez.');
      return;
    }

    setPreviewWindow(newWindow);

    // Le contenu initial est envoyé dès réception du message PREVIEW_READY
    // (voir le useEffect dédié). On surveille juste ici la fermeture manuelle
    // de la fenêtre pour réinitialiser l'état côté admin.
    const checkClosed = setInterval(() => {
      if (newWindow.closed) {
        clearInterval(checkClosed);
        setPreviewWindow(null);
      }
    }, 500);
  };

  const insertImageTemplate = (type) => {
    const templates = {
      simple: '<figure class="theory-image size-medium">\n  <img src="/images/exemple/nom-image.png" alt="Description de l\'image" />\n  <figcaption>Légende de l\'image</figcaption>\n</figure>',
      small: '<figure class="theory-image size-small">\n  <img src="/images/exemple/nom-image.png" alt="Description" />\n  <figcaption>Légende</figcaption>\n</figure>',
      large: '<figure class="theory-image size-large">\n  <img src="/images/exemple/nom-image.png" alt="Description" />\n  <figcaption>Légende</figcaption>\n</figure>',
      highlight: '<div class="image-highlight">\n  <figure>\n    <img src="/images/exemple/nom-image.png" alt="Description importante" />\n    <figcaption>Image importante avec fond coloré</figcaption>\n  </figure>\n</div>',
      sideBySide: '<div class="theory-images-row">\n  <figure>\n    <img src="/images/exemple/image1.png" alt="Description 1" />\n    <figcaption>Légende image 1</figcaption>\n  </figure>\n  <figure>\n    <img src="/images/exemple/image2.png" alt="Description 2" />\n    <figcaption>Légende image 2</figcaption>\n  </figure>\n</div>',
      grid: '<div class="theory-image-grid">\n  <figure>\n    <img src="/images/exemple/image1.png" alt="Description 1" />\n    <figcaption>Légende 1</figcaption>\n  </figure>\n  <figure>\n    <img src="/images/exemple/image2.png" alt="Description 2" />\n    <figcaption>Légende 2</figcaption>\n  </figure>\n  <figure>\n    <img src="/images/exemple/image3.png" alt="Description 3" />\n    <figcaption>Légende 3</figcaption>\n  </figure>\n</div>',
      float: '<img src="/images/exemple/nom-image.png" alt="Description" class="float-left" />\n<p>Votre texte continue à côté de l\'image...</p>\n<div class="clear"></div>',
      codeBlock: '<pre><code class="language-javascript">\n// Votre code JavaScript ici\nclass Example {\n  // ...\n}\n</code></pre>',
      paragraph: '<p>Votre paragraphe ici...</p>',
      heading: '<h3>Titre de section</h3>',
      list: '<ul>\n  <li>Élément 1</li>\n  <li>Élément 2</li>\n  <li>Élément 3</li>\n</ul>'
    };

    const template = templates[type];
    if (template && editorRef) {
      // Insérer à la position du curseur
      const position = editorRef.getPosition();
      const range = {
        startLineNumber: position.lineNumber,
        startColumn: position.column,
        endLineNumber: position.lineNumber,
        endColumn: position.column
      };

      editorRef.executeEdits('insert-template', [{
        range: range,
        text: '\n\n' + template + '\n\n'
      }]);

      // Mettre à jour le state avec le nouveau contenu
      const newContent = editorRef.getValue();
      setFormData({ ...formData, content: newContent });

      // Mettre le focus sur l'éditeur
      editorRef.focus();
    } else if (template && !editorRef) {
      // Fallback si l'éditeur n'est pas encore monté
      setFormData({ ...formData, content: formData.content + '\n\n' + template + '\n\n' });
    }
  };

  const saveTheorie = async (closeAfter) => {
    if (!formData.chapitre_id) {
      setMessage({ type: 'error', text: 'Veuillez sélectionner un chapitre' });
      return;
    }

    try {
      if (editingTheorie) {
        await updateTheorie(editingTheorie.id, formData);
        setMessage({ type: 'success', text: 'Théorie mise à jour avec succès' });
      } else {
        const result = await createTheorie(formData);
        setMessage({ type: 'success', text: 'Théorie créée avec succès' });
        // Bascule en mode édition pour pouvoir continuer sans tout perdre
        if (result.theorie) {
          setEditingTheorie(result.theorie);
        }
      }

      if (closeAfter) {
        setShowEditor(false);
        setEditingTheorie(null);
        setFormData({ chapitre_id: '', title: '', content: '', order_index: '', estimated_time: '' });
      }
      fetchData();
    } catch (error) {
      console.error('Erreur:', error);
      setMessage({ type: 'error', text: `Erreur: ${error.response?.data?.error || error.message}` });
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    await saveTheorie(true);
  };

  const handleSaveWithoutClosing = async () => {
    await saveTheorie(false);
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cette théorie ?')) {
      return;
    }

    try {
      await deleteTheorie(id);
      setMessage({ type: 'success', text: 'Théorie supprimée avec succès' });
      fetchData();
    } catch (error) {
      console.error('Erreur:', error);
      setMessage({ type: 'error', text: `Erreur: ${error.response?.data?.error || error.message}` });
    }
  };

  const selectedModule = sortedModules.find((m) => m.id === selectedModuleId) || null;
  const selectedModuleChapitres = selectedModuleId ? (chapitresByModule.get(selectedModuleId) || []) : [];

  if (loading) {
    return <div style={{ textAlign: 'center', padding: '40px' }}><div className="spinner"></div></div>;
  }

  // Fullscreen Editor View
  if (showEditor) {
    return (
      <div className="fullscreen-editor">
        <div className="editor-header">
          <div className="editor-header-left">
            <h2>{editingTheorie ? 'Modifier la théorie' : 'Nouvelle théorie'}</h2>
            {editingTheorie && <span className="editor-id">ID: {editingTheorie.id}</span>}
          </div>
          <div className="editor-header-right">
            <button
              type="button"
              className="btn btn-sm btn-secondary"
              onClick={() => setShowPreview(!showPreview)}
            >
              {showPreview ? '👁️ Masquer preview' : '👁️ Voir preview'}
            </button>
            <button
              type="button"
              className="btn btn-sm btn-primary"
              onClick={handleOpenPreviewWindow}
            >
              🪟 Ouvrir preview dans nouvelle fenêtre
            </button>
            <button
              type="button"
              className="btn btn-sm btn-secondary"
              onClick={handleCloseEditor}
            >
              ✕ Fermer
            </button>
          </div>
        </div>

        {message.text && (
          <div className={`alert alert-${message.type}`} style={{ margin: '20px', marginBottom: 0 }}>
            {message.text}
          </div>
        )}

        <form onSubmit={handleSubmit} className="editor-form">
          <div className="editor-sidebar">
            <div className="editor-sidebar-section">
              <h3>Informations</h3>

              <div className="form-group">
                <label className="form-label">Chapitre *</label>
                <select
                  className="form-control"
                  value={formData.chapitre_id}
                  onChange={(e) => handleChapitreChange(parseInt(e.target.value))}
                  required
                >
                  <option value="">Sélectionnez un chapitre</option>
                  {chapitres.map((chapitre) => (
                    <option key={chapitre.id} value={chapitre.id}>
                      {chapitreLabel(chapitre)}
                    </option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label className="form-label">Titre *</label>
                <input
                  type="text"
                  className="form-control"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  required
                  placeholder="Titre de la théorie"
                />
              </div>

              <div className="form-group">
                <label className="form-label">Ordre d'affichage *</label>
                <input
                  type="number"
                  className="form-control"
                  value={formData.order_index}
                  onChange={(e) => setFormData({ ...formData, order_index: parseInt(e.target.value) })}
                  required
                  min="1"
                />
              </div>

              <div className="form-group">
                <label className="form-label">Temps estimé (min)</label>
                <input
                  type="number"
                  className="form-control"
                  value={formData.estimated_time}
                  onChange={(e) => setFormData({ ...formData, estimated_time: parseInt(e.target.value) })}
                  min="1"
                  placeholder="15"
                />
              </div>
            </div>

            <div className="editor-sidebar-section">
              <h3>Insérer</h3>

              <div className="insert-buttons">
                <button type="button" className="btn btn-sm btn-block" onClick={() => insertImageTemplate('heading')}>
                  📝 Titre
                </button>
                <button type="button" className="btn btn-sm btn-block" onClick={() => insertImageTemplate('paragraph')}>
                  📄 Paragraphe
                </button>
                <button type="button" className="btn btn-sm btn-block" onClick={() => insertImageTemplate('list')}>
                  📋 Liste
                </button>
                <button type="button" className="btn btn-sm btn-block" onClick={() => insertImageTemplate('codeBlock')}>
                  💻 Code JS
                </button>
                <button type="button" className="btn btn-sm btn-block" onClick={() => insertImageTemplate('simple')}>
                  🖼️ Image
                </button>
                <button type="button" className="btn btn-sm btn-block" onClick={() => insertImageTemplate('highlight')}>
                  ⭐ Image valeur
                </button>
                <button type="button" className="btn btn-sm btn-block" onClick={() => insertImageTemplate('sideBySide')}>
                  ↔️ 2 Images
                </button>
                <button type="button" className="btn btn-sm btn-block" onClick={() => insertImageTemplate('grid')}>
                  ⊞ Grille images
                </button>
              </div>
            </div>

            <div className="editor-actions">
              <button
                type="button"
                className="btn btn-secondary btn-block"
                onClick={handleSaveWithoutClosing}
              >
                💾 Enregistrer
              </button>
              <button type="submit" className="btn btn-primary btn-block">
                ✅ {editingTheorie ? 'Enregistrer et fermer' : 'Créer'}
              </button>
            </div>
          </div>

          <div className="editor-main">
            <div className="editor-main-header">
              <label className="form-label">Contenu HTML *</label>
              <span className="editor-hint">Utilisez les boutons à gauche pour insérer du contenu</span>
            </div>

            <div className="monaco-wrapper">
              <Editor
                height="100%"
                defaultLanguage="html"
                value={formData.content}
                onChange={(value) => setFormData({ ...formData, content: value || '' })}
                onMount={(editor) => setEditorRef(editor)}
                theme="vs-dark"
                options={{
                  minimap: { enabled: false },
                  fontSize: 14,
                  lineNumbers: 'on',
                  wordWrap: 'on',
                  automaticLayout: true,
                  scrollBeyondLastLine: false,
                  formatOnPaste: true,
                  formatOnType: true
                }}
              />
            </div>
          </div>

          {showPreview && (
            <div className="editor-preview">
              <div className="editor-preview-header">
                <h3>Aperçu</h3>
              </div>
              <div className="editor-preview-content theory-content" dangerouslySetInnerHTML={{ __html: formData.content }} />
            </div>
          )}
        </form>
      </div>
    );
  }

  // List View
  return (
    <div>
      <div className="manager-header">
        <h2>Gestion des Théories</h2>
        <button className="btn btn-primary" onClick={() => handleOpenEditor()}>
          + Nouvelle Théorie
        </button>
      </div>

      {message.text && (
        <div className={`alert alert-${message.type}`}>
          {message.text}
        </div>
      )}

      {theories.length === 0 ? (
        <div className="empty-state">
          <h3>Aucune théorie</h3>
          <p>Commencez par créer votre première théorie</p>
        </div>
      ) : (
        <div className="content-manager-layout">
          <div className="content-manager-sidebar">
            {sortedModules.map((module) => {
              const moduleChapitres = chapitresByModule.get(module.id) || [];
              const moduleTheorieCount = moduleChapitres.reduce(
                (sum, chapitre) => sum + (theoriesByChapitre.get(chapitre.id) || []).length,
                0
              );
              if (moduleTheorieCount === 0) return null;

              return (
                <button
                  type="button"
                  key={module.id}
                  className={`content-manager-sidebar-item${module.id === selectedModuleId ? ' active' : ''}`}
                  onClick={() => setSelectedModuleId(module.id)}
                >
                  <span className="content-manager-sidebar-order">Module {module.order_index}</span>
                  <span className="content-manager-sidebar-title">{module.title}</span>
                  <span className="content-manager-sidebar-count">{moduleTheorieCount}</span>
                </button>
              );
            })}
          </div>

          <div className="content-manager-main">
            {!selectedModule ? (
              <div className="content-manager-empty">
                <p>Sélectionnez un module dans la liste pour voir et gérer ses théories.</p>
              </div>
            ) : (
              <div className="module-group">
                <div className="module-panel-header">
                  <span className="module-panel-order">Module {selectedModule.order_index}</span>
                  <span className="module-panel-title">{selectedModule.title}</span>
                </div>

                {selectedModuleChapitres.map((chapitre) => {
                  const chapitreTheories = theoriesByChapitre.get(chapitre.id) || [];
                  if (chapitreTheories.length === 0) return null;

                  return (
                    <div className="chapitre-subgroup" key={chapitre.id}>
                      <div className="chapitre-subgroup-header">
                        <span className="chapitre-subgroup-order">Chapitre {chapitre.order_index}</span>
                        <span className="chapitre-subgroup-title">{chapitre.title}</span>
                      </div>

                      <table className="admin-table">
                        <thead>
                          <tr>
                            <th>Ordre</th>
                            <th>Titre</th>
                            <th>Temps estimé</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          {chapitreTheories.map((theorie) => (
                            <tr key={theorie.id}>
                              <td>{theorie.order_index}</td>
                              <td><strong>{theorie.title}</strong></td>
                              <td>{theorie.estimated_time ? `${theorie.estimated_time} min` : '-'}</td>
                              <td>
                                <div className="admin-table-actions">
                                  <button
                                    className="btn btn-sm btn-secondary"
                                    onClick={() => handleOpenEditor(theorie)}
                                  >
                                    Modifier
                                  </button>
                                  <button
                                    className="btn btn-sm btn-danger"
                                    onClick={() => handleDelete(theorie.id)}
                                  >
                                    Supprimer
                                  </button>
                                </div>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                      <button
                        type="button"
                        className="btn btn-sm btn-secondary chapitre-subgroup-add"
                        onClick={() => handleOpenEditor(null, chapitre.id)}
                      >
                        + Ajouter une théorie à ce chapitre
                      </button>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default TheorieManager;
