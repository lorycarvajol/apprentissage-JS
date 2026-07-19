import { useState, useEffect, useMemo } from 'react';
import { getModules, getChapitres } from '../../services/contentService';
import { createChapitre, updateChapitre, deleteChapitre } from '../../services/adminService';

const ChapitreManager = () => {
  const [chapitres, setChapitres] = useState([]);
  const [modules, setModules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingChapitre, setEditingChapitre] = useState(null);
  // Aucun module sélectionné par défaut : la sidebar reste compacte et
  // affiche tout le parcours d'un coup d'œil, sans qu'aucune liste ne
  // s'ouvre tant que l'admin n'a pas cliqué dessus.
  const [selectedModuleId, setSelectedModuleId] = useState(null);
  const [formData, setFormData] = useState({
    module_id: '',
    title: '',
    description: '',
    order_index: '',
    is_published: true
  });
  const [message, setMessage] = useState({ type: '', text: '' });

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      setLoading(true);
      const [chapitresResponse, modulesResponse] = await Promise.all([
        getChapitres(false), // Inclure non publiés
        getModules(false) // Inclure non publiés
      ]);
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

  // Regroupe les chapitres par module, dans l'ordre réel du parcours plutôt
  // qu'en une seule liste plate difficile à situer.
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

  // Prochain ordre d'affichage suggéré pour un nouveau chapitre : le suivant
  // du dernier chapitre existant du module choisi (et non la longueur totale
  // de tous les chapitres, qui n'a aucun rapport avec ce module précis).
  const suggestNextOrderIndex = (moduleId) => {
    const existing = chapitresByModule.get(moduleId) || [];
    if (existing.length === 0) return 1;
    return Math.max(...existing.map((c) => c.order_index)) + 1;
  };

  const handleOpenModal = (chapitre = null, presetModuleId = null) => {
    if (chapitre) {
      setEditingChapitre(chapitre);
      setFormData({
        module_id: chapitre.module_id,
        title: chapitre.title,
        description: chapitre.description || '',
        order_index: chapitre.order_index,
        is_published: chapitre.is_published
      });
    } else {
      const moduleId = presetModuleId ?? selectedModuleId ?? (sortedModules.length > 0 ? sortedModules[0].id : '');
      setEditingChapitre(null);
      setFormData({
        module_id: moduleId,
        title: '',
        description: '',
        order_index: moduleId ? suggestNextOrderIndex(moduleId) : '',
        is_published: true
      });
    }
    setShowModal(true);
    setMessage({ type: '', text: '' });
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setEditingChapitre(null);
    setFormData({ module_id: '', title: '', description: '', order_index: '', is_published: true });
  };

  // Quand on change de module dans le formulaire (création uniquement), on
  // recalcule l'ordre suggéré pour ce module précis plutôt que de laisser un
  // chiffre qui ne correspond à rien pour le nouveau module choisi.
  const handleModuleChange = (newModuleId) => {
    setFormData((prev) => ({
      ...prev,
      module_id: newModuleId,
      order_index: editingChapitre ? prev.order_index : suggestNextOrderIndex(newModuleId)
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formData.module_id) {
      setMessage({ type: 'error', text: 'Veuillez sélectionner un module' });
      return;
    }

    try {
      if (editingChapitre) {
        await updateChapitre(editingChapitre.id, formData);
        setMessage({ type: 'success', text: 'Chapitre mis à jour avec succès' });
      } else {
        await createChapitre(formData);
        setMessage({ type: 'success', text: 'Chapitre créé avec succès' });
      }

      handleCloseModal();
      fetchData();
    } catch (error) {
      console.error('Erreur:', error);
      setMessage({ type: 'error', text: `Erreur: ${error.response?.data?.error || error.message}` });
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer ce chapitre ?')) {
      return;
    }

    try {
      await deleteChapitre(id);
      setMessage({ type: 'success', text: 'Chapitre supprimé avec succès' });
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

  return (
    <div>
      <div className="manager-header">
        <h2>Gestion des Chapitres</h2>
        <button className="btn btn-primary" onClick={() => handleOpenModal()}>
          + Nouveau Chapitre
        </button>
      </div>

      {message.text && (
        <div className={`alert alert-${message.type}`}>
          {message.text}
        </div>
      )}

      {chapitres.length === 0 ? (
        <div className="empty-state">
          <h3>Aucun chapitre</h3>
          <p>Commencez par créer votre premier chapitre</p>
        </div>
      ) : (
        <div className="content-manager-layout">
          <div className="content-manager-sidebar">
            {sortedModules.map((module) => {
              const count = (chapitresByModule.get(module.id) || []).length;
              return (
                <button
                  type="button"
                  key={module.id}
                  className={`content-manager-sidebar-item${module.id === selectedModuleId ? ' active' : ''}`}
                  onClick={() => setSelectedModuleId(module.id)}
                >
                  <span className="content-manager-sidebar-order">Module {module.order_index}</span>
                  <span className="content-manager-sidebar-title">{module.title}</span>
                  <span className="content-manager-sidebar-count">{count}</span>
                </button>
              );
            })}
          </div>

          <div className="content-manager-main">
            {!selectedModule ? (
              <div className="content-manager-empty">
                <p>Sélectionnez un module dans la liste pour voir et gérer ses chapitres.</p>
              </div>
            ) : (
              <div className="module-group">
                <div className="module-panel-header">
                  <span className="module-panel-order">Module {selectedModule.order_index}</span>
                  <span className="module-panel-title">{selectedModule.title}</span>
                  <span className="module-panel-count">
                    {selectedModuleChapitres.length} chapitre{selectedModuleChapitres.length > 1 ? 's' : ''}
                  </span>
                  {!selectedModule.is_published && (
                    <span className="badge badge-warning">Module brouillon</span>
                  )}
                </div>

                {selectedModuleChapitres.length === 0 ? (
                  <p className="form-hint">Ce module n'a pas encore de chapitre.</p>
                ) : (
                  <table className="admin-table">
                    <thead>
                      <tr>
                        <th>Chapitre</th>
                        <th>Titre</th>
                        <th>Description</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {selectedModuleChapitres.map((chapitre) => (
                        <tr key={chapitre.id}>
                          <td>{chapitre.order_index}</td>
                          <td><strong>{chapitre.title}</strong></td>
                          <td>{chapitre.description}</td>
                          <td>
                            <span className={`badge ${chapitre.is_published ? 'badge-success' : 'badge-warning'}`}>
                              {chapitre.is_published ? 'Publié' : 'Brouillon'}
                            </span>
                          </td>
                          <td>
                            <div className="admin-table-actions">
                              <button
                                className="btn btn-sm btn-secondary"
                                onClick={() => handleOpenModal(chapitre)}
                              >
                                Modifier
                              </button>
                              <button
                                className="btn btn-sm btn-danger"
                                onClick={() => handleDelete(chapitre.id)}
                              >
                                Supprimer
                              </button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
                <button
                  type="button"
                  className="btn btn-sm btn-secondary module-group-add"
                  onClick={() => handleOpenModal(null, selectedModule.id)}
                >
                  + Ajouter un chapitre à ce module
                </button>
              </div>
            )}
          </div>
        </div>
      )}

      {showModal && (
        <div className="modal-overlay" onClick={handleCloseModal}>
          <div className="modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3>{editingChapitre ? 'Modifier le chapitre' : 'Nouveau chapitre'}</h3>
            </div>

            <form onSubmit={handleSubmit}>
              <div className="modal-body">
                <div className="form-group">
                  <label className="form-label">Module *</label>
                  <select
                    className="form-control"
                    value={formData.module_id}
                    onChange={(e) => handleModuleChange(parseInt(e.target.value))}
                    required
                  >
                    <option value="">Sélectionnez un module</option>
                    {sortedModules.map((module) => (
                      <option key={module.id} value={module.id}>
                        Module {module.order_index} — {module.title}
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
                  <p className="form-hint">
                    Position de ce chapitre dans son module (1 = premier chapitre du module).
                  </p>
                </div>

                <div className="form-group">
                  <label className="form-check">
                    <input
                      type="checkbox"
                      checked={formData.is_published}
                      onChange={(e) => setFormData({ ...formData, is_published: e.target.checked })}
                    />
                    <span>Publier le chapitre</span>
                  </label>
                </div>
              </div>

              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={handleCloseModal}>
                  Annuler
                </button>
                <button type="submit" className="btn btn-primary">
                  {editingChapitre ? 'Mettre à jour' : 'Créer'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default ChapitreManager;
