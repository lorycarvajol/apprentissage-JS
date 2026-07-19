import { useState, useEffect, useMemo } from 'react';
import { getModules, getChapitres, getExercices } from '../../services/contentService';
import { createExercice, updateExercice, deleteExercice } from '../../services/adminService';
import { runJs } from '../../utils/jsSandbox';

const ExerciceManager = () => {
  const [exercices, setExercices] = useState([]);
  const [chapitres, setChapitres] = useState([]);
  const [modules, setModules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingExercice, setEditingExercice] = useState(null);
  // Aucun module sélectionné par défaut : la sidebar reste compacte et
  // affiche tout le parcours d'un coup d'œil, sans qu'aucune liste ne
  // s'ouvre tant que l'admin n'a pas cliqué dessus.
  const [selectedModuleId, setSelectedModuleId] = useState(null);
  const [formData, setFormData] = useState({
    chapitre_id: '',
    title: '',
    description: '',
    instructions: '',
    starter_code: '',
    solution_code: '',
    expected_output: '',
    difficulty: 'easy',
    points: '',
    order_index: ''
  });
  const [message, setMessage] = useState({ type: '', text: '' });
  const [testingSolution, setTestingSolution] = useState(false);

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      setLoading(true);
      const [exercicesResponse, chapitresResponse, modulesResponse] = await Promise.all([
        getExercices(),
        getChapitres(false), // Inclure non publiés
        getModules(false)
      ]);
      setExercices(exercicesResponse.exercices || []);
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

  // Exercices groupés par chapitre, triés par leur propre order_index.
  const exercicesByChapitre = useMemo(() => {
    const map = new Map();
    for (const exercice of exercices) {
      if (!map.has(exercice.chapitre_id)) {
        map.set(exercice.chapitre_id, []);
      }
      map.get(exercice.chapitre_id).push(exercice);
    }
    for (const list of map.values()) {
      list.sort((a, b) => a.order_index - b.order_index);
    }
    return map;
  }, [exercices]);

  // Un libellé "Module N › Chapitre M : Titre" pour lever toute ambiguïté
  // dans le select (deux chapitres de modules différents peuvent avoir des
  // titres proches, et l'ordre affiché seul ne suffit pas à les distinguer).
  const chapitreLabel = (chapitre) => {
    const module = modules.find((m) => m.id === chapitre.module_id);
    const modulePrefix = module ? `Module ${module.order_index} › ` : '';
    return `${modulePrefix}Chapitre ${chapitre.order_index} : ${chapitre.title}`;
  };

  // Prochain ordre d'affichage suggéré pour un nouvel exercice : le suivant
  // du dernier exercice existant du chapitre choisi (et non la longueur
  // totale de tous les exercices, sans rapport avec ce chapitre précis).
  const suggestNextOrderIndex = (chapitreId) => {
    const existing = exercicesByChapitre.get(chapitreId) || [];
    if (existing.length === 0) return 1;
    return Math.max(...existing.map((ex) => ex.order_index)) + 1;
  };

  const handleOpenModal = (exercice = null, presetChapitreId = null) => {
    if (exercice) {
      setEditingExercice(exercice);
      setFormData({
        chapitre_id: exercice.chapitre_id,
        title: exercice.title,
        description: exercice.description || '',
        instructions: exercice.instructions || '',
        starter_code: exercice.starter_code || '',
        solution_code: exercice.solution_code || '',
        expected_output: exercice.expected_output || '',
        difficulty: exercice.difficulty || 'easy',
        points: exercice.points || '',
        order_index: exercice.order_index
      });
    } else {
      const selectedModuleChapitres = selectedModuleId
        ? chapitres.filter((c) => c.module_id === selectedModuleId)
        : [];
      const chapitreId = presetChapitreId
        ?? (selectedModuleChapitres.length > 0 ? selectedModuleChapitres[0].id : null)
        ?? (chapitres.length > 0 ? chapitres[0].id : '');
      setEditingExercice(null);
      setFormData({
        chapitre_id: chapitreId,
        title: '',
        description: '',
        instructions: '',
        starter_code: '',
        solution_code: '',
        expected_output: '',
        difficulty: 'easy',
        points: '',
        order_index: chapitreId ? suggestNextOrderIndex(chapitreId) : ''
      });
    }
    setShowModal(true);
    setMessage({ type: '', text: '' });
  };

  // Quand on change de chapitre dans le formulaire (création uniquement), on
  // recalcule l'ordre suggéré pour ce chapitre précis plutôt que de laisser
  // un chiffre qui ne correspond à rien pour le nouveau chapitre choisi.
  const handleChapitreChange = (newChapitreId) => {
    setFormData((prev) => ({
      ...prev,
      chapitre_id: newChapitreId,
      order_index: editingExercice ? prev.order_index : suggestNextOrderIndex(newChapitreId)
    }));
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setEditingExercice(null);
    setFormData({
      chapitre_id: '',
      title: '',
      description: '',
      instructions: '',
      starter_code: '',
      solution_code: '',
      expected_output: '',
      difficulty: 'easy',
      points: '',
      order_index: ''
    });
  };

  // Contrairement au projet PHP jumeau, où CodeExecutionService recalculait
  // la sortie de référence à chaque soumission côté serveur, il n'y a ici
  // aucun moteur JS serveur : la sortie attendue doit être précalculée une
  // fois, ici, dans le même sandbox Worker que celui utilisé par l'apprenant
  // (voir jsSandbox.js), puis stockée dans expected_output.
  const handleTestSolution = async () => {
    if (!formData.solution_code.trim()) {
      setMessage({ type: 'error', text: 'Renseignez le code de la solution avant de le tester' });
      return;
    }

    setTestingSolution(true);
    setMessage({ type: '', text: '' });

    try {
      const executed = await runJs(formData.solution_code);

      if (executed.timedOut) {
        setMessage({ type: 'error', text: 'La solution met trop de temps à s\'exécuter (boucle infinie ?)' });
        return;
      }

      if (executed.error) {
        setMessage({ type: 'error', text: `La solution contient une erreur : ${executed.error}` });
        return;
      }

      setFormData((prev) => ({ ...prev, expected_output: executed.output }));
      setMessage({ type: 'success', text: 'Sortie de référence calculée avec succès' });
    } finally {
      setTestingSolution(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formData.chapitre_id) {
      setMessage({ type: 'error', text: 'Veuillez sélectionner un chapitre' });
      return;
    }

    try {
      if (editingExercice) {
        await updateExercice(editingExercice.id, formData);
        setMessage({ type: 'success', text: 'Exercice mis à jour avec succès' });
      } else {
        await createExercice(formData);
        setMessage({ type: 'success', text: 'Exercice créé avec succès' });
      }

      handleCloseModal();
      fetchData();
    } catch (error) {
      console.error('Erreur:', error);
      setMessage({ type: 'error', text: `Erreur: ${error.response?.data?.error || error.message}` });
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cet exercice ?')) {
      return;
    }

    try {
      await deleteExercice(id);
      setMessage({ type: 'success', text: 'Exercice supprimé avec succès' });
      fetchData();
    } catch (error) {
      console.error('Erreur:', error);
      setMessage({ type: 'error', text: `Erreur: ${error.response?.data?.error || error.message}` });
    }
  };

  const selectedModule = sortedModules.find((m) => m.id === selectedModuleId) || null;
  const selectedModuleChapitres = selectedModuleId ? (chapitresByModule.get(selectedModuleId) || []) : [];

  const getDifficultyLabel = (difficulty) => {
    const labels = {
      easy: 'Facile',
      medium: 'Moyen',
      hard: 'Difficile'
    };
    return labels[difficulty] || difficulty;
  };

  const getDifficultyBadgeClass = (difficulty) => {
    const classes = {
      easy: 'badge-success',
      medium: 'badge-warning',
      hard: 'badge-danger'
    };
    return classes[difficulty] || 'badge-warning';
  };

  if (loading) {
    return <div style={{ textAlign: 'center', padding: '40px' }}><div className="spinner"></div></div>;
  }

  return (
    <div>
      <div className="manager-header">
        <h2>Gestion des Exercices</h2>
        <button className="btn btn-primary" onClick={() => handleOpenModal()}>
          + Nouvel Exercice
        </button>
      </div>

      {message.text && (
        <div className={`alert alert-${message.type}`}>
          {message.text}
        </div>
      )}

      {exercices.length === 0 ? (
        <div className="empty-state">
          <h3>Aucun exercice</h3>
          <p>Commencez par créer votre premier exercice</p>
        </div>
      ) : (
        <div className="content-manager-layout">
          <div className="content-manager-sidebar">
            {sortedModules.map((module) => {
              const moduleChapitres = chapitresByModule.get(module.id) || [];
              const moduleExerciceCount = moduleChapitres.reduce(
                (sum, chapitre) => sum + (exercicesByChapitre.get(chapitre.id) || []).length,
                0
              );
              if (moduleExerciceCount === 0) return null;

              return (
                <button
                  type="button"
                  key={module.id}
                  className={`content-manager-sidebar-item${module.id === selectedModuleId ? ' active' : ''}`}
                  onClick={() => setSelectedModuleId(module.id)}
                >
                  <span className="content-manager-sidebar-order">Module {module.order_index}</span>
                  <span className="content-manager-sidebar-title">{module.title}</span>
                  <span className="content-manager-sidebar-count">{moduleExerciceCount}</span>
                </button>
              );
            })}
          </div>

          <div className="content-manager-main">
            {!selectedModule ? (
              <div className="content-manager-empty">
                <p>Sélectionnez un module dans la liste pour voir et gérer ses exercices.</p>
              </div>
            ) : (
              <div className="module-group">
                <div className="module-panel-header">
                  <span className="module-panel-order">Module {selectedModule.order_index}</span>
                  <span className="module-panel-title">{selectedModule.title}</span>
                </div>

                {selectedModuleChapitres.map((chapitre) => {
                  const chapitreExercices = exercicesByChapitre.get(chapitre.id) || [];
                  if (chapitreExercices.length === 0) return null;

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
                            <th>Difficulté</th>
                            <th>Points</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          {chapitreExercices.map((exercice) => (
                            <tr key={exercice.id}>
                              <td>{exercice.order_index}</td>
                              <td><strong>{exercice.title}</strong></td>
                              <td>
                                <span className={`badge ${getDifficultyBadgeClass(exercice.difficulty)}`}>
                                  {getDifficultyLabel(exercice.difficulty)}
                                </span>
                              </td>
                              <td>{exercice.points || '-'}</td>
                              <td>
                                <div className="admin-table-actions">
                                  <button
                                    className="btn btn-sm btn-secondary"
                                    onClick={() => handleOpenModal(exercice)}
                                  >
                                    Modifier
                                  </button>
                                  <button
                                    className="btn btn-sm btn-danger"
                                    onClick={() => handleDelete(exercice.id)}
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
                        onClick={() => handleOpenModal(null, chapitre.id)}
                      >
                        + Ajouter un exercice à ce chapitre
                      </button>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      )}

      {showModal && (
        <div className="modal-overlay" onClick={handleCloseModal}>
          <div className="modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3>{editingExercice ? 'Modifier l\'exercice' : 'Nouvel exercice'}</h3>
            </div>

            <form onSubmit={handleSubmit}>
              <div className="modal-body">
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
                  />
                </div>

                <div className="form-group">
                  <label className="form-label">Description</label>
                  <textarea
                    className="form-control"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    placeholder="Description courte de l'exercice..."
                  />
                </div>

                <div className="form-group">
                  <label className="form-label">Instructions</label>
                  <textarea
                    className="form-control"
                    style={{ minHeight: '150px' }}
                    value={formData.instructions}
                    onChange={(e) => setFormData({ ...formData, instructions: e.target.value })}
                    placeholder="Instructions détaillées pour l'exercice..."
                  />
                </div>

                <div className="form-group">
                  <label className="form-label">Code de départ</label>
                  <textarea
                    className="form-control"
                    style={{ minHeight: '200px', fontFamily: 'monospace', fontSize: '13px' }}
                    value={formData.starter_code}
                    onChange={(e) => setFormData({ ...formData, starter_code: e.target.value })}
                    placeholder={'// Code de départ pour l\'exercice'}
                  />
                </div>

                <div className="form-group">
                  <label className="form-label">Code de la solution *</label>
                  <textarea
                    className="form-control"
                    style={{ minHeight: '200px', fontFamily: 'monospace', fontSize: '13px' }}
                    value={formData.solution_code}
                    onChange={(e) => setFormData({ ...formData, solution_code: e.target.value })}
                    placeholder={'// Solution de référence : sa sortie sert de référence pour la correction'}
                    required
                  />
                  <p className="form-hint">
                    Pas de moteur JS côté serveur ici : cliquez sur « Tester la solution » pour
                    l'exécuter dans le même sandbox que celui de l'apprenant (navigateur) et
                    calculer automatiquement la sortie de référence ci-dessous.
                  </p>
                  <button
                    type="button"
                    className="btn btn-sm btn-secondary"
                    onClick={handleTestSolution}
                    disabled={testingSolution}
                  >
                    {testingSolution ? 'Exécution...' : '▶️ Tester la solution'}
                  </button>
                </div>

                <div className="form-group">
                  <label className="form-label">Sortie de référence attendue *</label>
                  <textarea
                    className="form-control"
                    style={{ minHeight: '100px', fontFamily: 'monospace', fontSize: '13px' }}
                    value={formData.expected_output}
                    onChange={(e) => setFormData({ ...formData, expected_output: e.target.value })}
                    placeholder="Calculée automatiquement via « Tester la solution », ou saisie manuellement"
                    required
                  />
                  <p className="form-hint">
                    C'est cette sortie qui est comparée à celle produite par le code de l'apprenant (comparaison insensible aux espaces et à la casse).
                  </p>
                </div>

                <div className="form-group">
                  <label className="form-label">Difficulté *</label>
                  <select
                    className="form-control"
                    value={formData.difficulty}
                    onChange={(e) => setFormData({ ...formData, difficulty: e.target.value })}
                    required
                  >
                    <option value="easy">Facile</option>
                    <option value="medium">Moyen</option>
                    <option value="hard">Difficile</option>
                  </select>
                </div>

                <div className="form-group">
                  <label className="form-label">Points</label>
                  <input
                    type="number"
                    className="form-control"
                    value={formData.points}
                    onChange={(e) => setFormData({ ...formData, points: parseInt(e.target.value) })}
                    min="0"
                    placeholder="Ex: 10"
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
              </div>

              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={handleCloseModal}>
                  Annuler
                </button>
                <button type="submit" className="btn btn-primary">
                  {editingExercice ? 'Mettre à jour' : 'Créer'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default ExerciceManager;
