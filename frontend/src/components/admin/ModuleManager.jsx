import { useState, useEffect, useMemo, useRef } from 'react';
import { getModules } from '../../services/contentService';
import { createModule, updateModule, deleteModule } from '../../services/adminService';

const ModuleManager = ({ isActive = true }) => {
  const [modules, setModules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingModule, setEditingModule] = useState(null);
  // Aucun module sélectionné par défaut : la sidebar reste compacte et
  // affiche tout le parcours d'un coup d'œil, sans qu'aucune liste ne
  // s'ouvre tant que l'admin n'a pas cliqué dessus (même logique que les
  // managers Chapitres/Théories/Exercices).
  const [selectedModuleId, setSelectedModuleId] = useState(null);
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    order_index: '',
    is_published: true
  });
  const [message, setMessage] = useState({ type: '', text: '' });

  useEffect(() => {
    fetchModules();
  }, []);

  // Le composant n'est jamais démonté (cf. AdminPage) : sans ce rechargement,
  // il afficherait indéfiniment les données du premier montage. Le premier
  // passage est ignoré, le useEffect de montage ci-dessus s'en est déjà chargé.
  const montageInitial = useRef(true);
  useEffect(() => {
    if (montageInitial.current) {
      montageInitial.current = false;
      return;
    }
    if (!isActive) return;
    fetchModules({ silencieux: true });
  }, [isActive]);

  // `silencieux` : recharge sans repasser en état de chargement. C'est ce qui
  // permet de rafraîchir à chaque retour sur l'onglet sans faire clignoter un
  // spinner par-dessus une liste déjà affichée.
  const fetchModules = async ({ silencieux = false } = {}) => {
    try {
      if (!silencieux) setLoading(true);
      const response = await getModules(false); // Inclure non publiés
      setModules(response.modules || []);
    } catch (error) {
      console.error('Erreur:', error);
      setMessage({ type: 'error', text: 'Erreur lors du chargement des modules' });
    } finally {
      setLoading(false);
    }
  };

  // Triés par ordre d'affichage réel (order_index) : déjà l'ordre renvoyé
  // par l'API, mais on le garantit ici pour rester cohérent avec les autres
  // managers (Chapitres/Théories/Exercices) qui en dépendent explicitement.
  const sortedModules = useMemo(
    () => [...modules].sort((a, b) => a.order_index - b.order_index),
    [modules]
  );

  const selectedModule = sortedModules.find((m) => m.id === selectedModuleId) || null;

  const handleOpenModal = (module = null) => {
    if (module) {
      setEditingModule(module);
      setFormData({
        title: module.title,
        description: module.description || '',
        order_index: module.order_index,
        is_published: module.is_published
      });
    } else {
      setEditingModule(null);
      setFormData({
        title: '',
        description: '',
        order_index: modules.length + 1,
        is_published: true
      });
    }
    setShowModal(true);
    setMessage({ type: '', text: '' });
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setEditingModule(null);
    setFormData({ title: '', description: '', order_index: '', is_published: true });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      if (editingModule) {
        await updateModule(editingModule.id, formData);
        setMessage({ type: 'success', text: 'Module mis à jour avec succès' });
      } else {
        await createModule(formData);
        setMessage({ type: 'success', text: 'Module créé avec succès' });
      }

      handleCloseModal();
      fetchModules();
    } catch (error) {
      console.error('Erreur:', error);
      setMessage({ type: 'error', text: `Erreur: ${error.response?.data?.error || error.message}` });
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer ce module ?')) {
      return;
    }

    try {
      await deleteModule(id);
      setMessage({ type: 'success', text: 'Module supprimé avec succès' });
      if (selectedModuleId === id) {
        setSelectedModuleId(null);
      }
      fetchModules();
    } catch (error) {
      console.error('Erreur:', error);
      setMessage({ type: 'error', text: `Erreur: ${error.response?.data?.error || error.message}` });
    }
  };

  if (loading) {
    return <div style={{ textAlign: 'center', padding: '40px' }}><div className="spinner"></div></div>;
  }

  return (
    <div>
      <div className="manager-header">
        <h2>Gestion des Modules</h2>
        <button className="btn btn-primary" onClick={() => handleOpenModal()}>
          + Nouveau Module
        </button>
      </div>

      {message.text && (
        <div className={`alert alert-${message.type}`}>
          {message.text}
        </div>
      )}

      {modules.length === 0 ? (
        <div className="empty-state">
          <h3>Aucun module</h3>
          <p>Commencez par créer votre premier module</p>
        </div>
      ) : (
        <div className="content-manager-layout">
          <div className="content-manager-sidebar">
            {sortedModules.map((module) => (
              <button
                type="button"
                key={module.id}
                className={`content-manager-sidebar-item${module.id === selectedModuleId ? ' active' : ''}`}
                onClick={() => setSelectedModuleId(module.id)}
              >
                <span className="content-manager-sidebar-order">Module {module.order_index}</span>
                <span className="content-manager-sidebar-title">{module.title}</span>
                {!module.is_published && <span className="badge badge-warning">Brouillon</span>}
              </button>
            ))}
          </div>

          <div className="content-manager-main">
            {!selectedModule ? (
              <div className="content-manager-empty">
                <p>Sélectionnez un module dans la liste pour voir son détail.</p>
              </div>
            ) : (
              <div className="module-group">
                <div className="module-panel-header">
                  <span className="module-panel-order">Module {selectedModule.order_index}</span>
                  <span className="module-panel-title">{selectedModule.title}</span>
                  <span className={`badge ${selectedModule.is_published ? 'badge-success' : 'badge-warning'}`}>
                    {selectedModule.is_published ? 'Publié' : 'Brouillon'}
                  </span>
                </div>

                <p className="module-panel-description">
                  {selectedModule.description || <em>Aucune description.</em>}
                </p>

                <div className="admin-table-actions module-panel-actions">
                  <button
                    className="btn btn-sm btn-secondary"
                    onClick={() => handleOpenModal(selectedModule)}
                  >
                    ✏️ Modifier
                  </button>
                  <button
                    className="btn btn-sm btn-danger"
                    onClick={() => handleDelete(selectedModule.id)}
                  >
                    🗑️ Supprimer
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {showModal && (
        <div className="modal-overlay" onClick={handleCloseModal}>
          <div className="modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3>{editingModule ? 'Modifier le module' : 'Nouveau module'}</h3>
            </div>

            <form onSubmit={handleSubmit}>
              <div className="modal-body">
                <div className="form-group">
                  <label className="form-label">Titre *</label>
                  <input
                    type="text"
                    className="form-control"
                    value={formData.title}
                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                    required
                  />
                </div>

                <div className="form-group">
                  <label className="form-label">Description</label>
                  <textarea
                    className="form-control"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
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
                  <label className="form-check">
                    <input
                      type="checkbox"
                      checked={formData.is_published}
                      onChange={(e) => setFormData({ ...formData, is_published: e.target.checked })}
                    />
                    <span>Publier le module</span>
                  </label>
                </div>
              </div>

              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={handleCloseModal}>
                  Annuler
                </button>
                <button type="submit" className="btn btn-primary">
                  {editingModule ? 'Mettre à jour' : 'Créer'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default ModuleManager;
