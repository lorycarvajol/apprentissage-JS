import { useState, useEffect, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import { getChapitreWithContent, getModuleWithChapitres, getModules, markTheorieAsRead } from '../services/contentService';
import SidebarNavigation from '../components/content/SidebarNavigation';
import MainLayout from '../components/layout/MainLayout';
import ExerciceSolver from '../components/content/ExerciceSolver';
import '../styles/Content.css';

const ChapitrePage = () => {
  const { id } = useParams();
  const [chapitre, setChapitre] = useState(null);
  const [module, setModule] = useState(null);
  const [theories, setTheories] = useState([]);
  const [exercices, setExercices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [activeExercice, setActiveExercice] = useState(null);
  const [readTheorieIds, setReadTheorieIds] = useState(new Set());
  // Chapitres du même module (pour "chapitre précédent/suivant") et liste
  // complète des modules (pour basculer vers le module précédent/suivant
  // une fois arrivé au premier/dernier chapitre du module courant).
  const [siblingChapitres, setSiblingChapitres] = useState([]);
  const [allModules, setAllModules] = useState([]);
  const pageEnteredAt = useRef(Date.now());

  useEffect(() => {
    fetchChapitre();
  }, [id]);

  const handleMarkAsRead = async (theorieId) => {
    if (readTheorieIds.has(theorieId)) return;

    const timeSpent = Math.round((Date.now() - pageEnteredAt.current) / 1000);
    setReadTheorieIds((prev) => new Set(prev).add(theorieId));

    try {
      await markTheorieAsRead(theorieId, timeSpent);
    } catch (err) {
      console.error('Erreur lors du marquage de la théorie comme lue:', err);
    }
  };

  const fetchChapitre = async () => {
    try {
      setLoading(true);
      const response = await getChapitreWithContent(id);

      if (response.success) {
        setChapitre(response.chapitre);
        setModule(response.module || null);
        setTheories(response.theories || []);
        setExercices(response.exercices || []);

        if (response.chapitre?.module_id) {
          const [moduleChapitresRes, modulesRes] = await Promise.all([
            getModuleWithChapitres(response.chapitre.module_id),
            getModules(),
          ]);
          setSiblingChapitres(moduleChapitresRes.success ? moduleChapitresRes.chapitres || [] : []);
          setAllModules(modulesRes.success ? modulesRes.modules || [] : []);
        }
      }
    } catch (err) {
      console.error('Erreur lors du chargement du chapitre:', err);
      setError('Impossible de charger le chapitre');
    } finally {
      setLoading(false);
    }
  };

  // Détermine le chapitre/module précédent et suivant à partir de la
  // position du chapitre courant dans sa fratrie, avec bascule vers le
  // module voisin une fois arrivé au premier/dernier chapitre du module.
  const currentIndex = siblingChapitres.findIndex((c) => c.id === chapitre?.id);
  const prevChapitre = currentIndex > 0 ? siblingChapitres[currentIndex - 1] : null;
  const nextChapitre =
    currentIndex >= 0 && currentIndex < siblingChapitres.length - 1
      ? siblingChapitres[currentIndex + 1]
      : null;
  const prevModule = !prevChapitre && module
    ? allModules.find((m) => m.order_index === module.order_index - 1)
    : null;
  const nextModule = !nextChapitre && module
    ? allModules.find((m) => m.order_index === module.order_index + 1)
    : null;

  if (activeExercice) {
    return (
      <ExerciceSolver
        exercice={activeExercice}
        onClose={() => setActiveExercice(null)}
      />
    );
  }

  if (loading) {
    return (
      <MainLayout>
        <div className="page-with-sidebar">
          <SidebarNavigation currentModuleId={chapitre?.module_id} />
          <div className="page-content">
            <div className="loading-container">
              <div className="spinner"></div>
              <p>Chargement du chapitre...</p>
            </div>
          </div>
        </div>
      </MainLayout>
    );
  }

  if (error || !chapitre) {
    return (
      <MainLayout>
        <div className="page-with-sidebar">
          <SidebarNavigation />
          <div className="page-content">
            <div className="alert alert-error">
              {error || 'Chapitre non trouvé'}
            </div>
            <Link to="/modules" className="btn btn-secondary">
              ← Retour aux modules
            </Link>
          </div>
        </div>
      </MainLayout>
    );
  }

  return (
    <MainLayout>
      <div className="page-with-sidebar">
        <SidebarNavigation currentModuleId={chapitre.module_id} />

        <div className="page-content">
          {/* Header */}
          <div className="chapitre-page-header">
            <div className="breadcrumb">
              <Link to="/modules">Modules</Link>
              <span>›</span>
              <Link to={`/modules/${chapitre.module_id}`}>
                {module ? `Module ${module.order_index}` : 'Module'}
              </Link>
              <span>›</span>
              <span>{chapitre.title}</span>
            </div>

            <h1 className="chapitre-page-title">
              <span className="chapitre-number">Chapitre {chapitre.order_index}</span>
              {chapitre.title}
            </h1>

            {chapitre.description && (
              <p className="chapitre-page-description">{chapitre.description}</p>
            )}
          </div>

          {/* Progress Indicator */}
          <div className="content-progress">
            <div className="progress-item">
              <span className="progress-icon">📚</span>
              <span>{theories.length} Contenu{theories.length > 1 ? 's' : ''} théorique{theories.length > 1 ? 's' : ''}</span>
            </div>
            {exercices.length > 0 && (
              <div className="progress-item">
                <span className="progress-icon">💻</span>
                <span>{exercices.length} Exercice{exercices.length > 1 ? 's' : ''}</span>
              </div>
            )}
          </div>

          {/* Theories Content */}
          {theories.length > 0 && (
            <div className="content-section">
              {theories.map((theorie, index) => (
                <div key={theorie.id} className="theory-container">
                  {theories.length > 1 && (
                    <div className="section-header-small">
                      <span className="section-number">Partie {index + 1}</span>
                      <h2>{theorie.title}</h2>
                      {theorie.estimated_time && (
                        <span className="time-badge">
                          ⏱️ {theorie.estimated_time} min de lecture
                        </span>
                      )}
                    </div>
                  )}

                  <div
                    className="theory-content"
                    dangerouslySetInnerHTML={{ __html: theorie.content }}
                  />

                  <button
                    type="button"
                    className="btn btn-secondary"
                    disabled={readTheorieIds.has(theorie.id)}
                    onClick={() => handleMarkAsRead(theorie.id)}
                  >
                    {readTheorieIds.has(theorie.id) ? '✓ Lu' : 'Marquer comme lu'}
                  </button>

                  {index < theories.length - 1 && (
                    <div className="content-divider"></div>
                  )}
                </div>
              ))}
            </div>
          )}

          {/* Exercises Section */}
          {exercices.length > 0 && (
            <div className="content-section exercises-section">
              <div className="section-header-large">
                <h2>💻 Exercices Pratiques</h2>
                <p>Mettez en pratique ce que vous avez appris</p>
              </div>

              <div className="exercices-grid">
                {exercices.map((exercice, index) => (
                  <div key={exercice.id} className="exercice-card">
                    <div className="exercice-card-header">
                      <div className="exercice-number">Exercice {index + 1}</div>
                      <div className="exercice-meta">
                        <span className={`difficulty difficulty-${exercice.difficulty}`}>
                          {exercice.difficulty === 'easy' && '🟢 Facile'}
                          {exercice.difficulty === 'medium' && '🟡 Moyen'}
                          {exercice.difficulty === 'hard' && '🔴 Difficile'}
                        </span>
                        <span className="points-badge">
                          ⭐ {exercice.points} pts
                        </span>
                      </div>
                    </div>

                    <h3 className="exercice-title">{exercice.title}</h3>
                    <p className="exercice-description">{exercice.description}</p>

                    {exercice.instructions && (
                      <div className="exercice-instructions">
                        <strong>📋 Instructions :</strong>
                        <p>{exercice.instructions}</p>
                      </div>
                    )}

                    {exercice.starter_code && (
                      <div className="code-block">
                        <div className="code-header">💻 Code de départ</div>
                        <pre><code>{exercice.starter_code}</code></pre>
                      </div>
                    )}

                    <button
                      type="button"
                      className="btn btn-primary btn-block"
                      onClick={() => setActiveExercice(exercice)}
                    >
                      Commencer l'exercice →
                    </button>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Empty States */}
          {theories.length === 0 && exercices.length === 0 && (
            <div className="empty-state">
              <h3>📝 Contenu en préparation</h3>
              <p>Le contenu de ce chapitre sera bientôt disponible.</p>
            </div>
          )}

          {/* Navigation Buttons */}
          <div className="chapter-navigation">
            <div className="nav-slot nav-prev">
              {prevChapitre ? (
                <Link to={`/chapitres/${prevChapitre.id}`} className="btn btn-secondary nav-btn">
                  <span className="nav-btn-arrow">←</span>
                  <span className="nav-btn-text">
                    <span className="nav-btn-label">Chapitre précédent</span>
                    <span className="nav-btn-title">{prevChapitre.title}</span>
                  </span>
                </Link>
              ) : prevModule ? (
                <Link to={`/modules/${prevModule.id}`} className="btn btn-secondary nav-btn">
                  <span className="nav-btn-arrow">←</span>
                  <span className="nav-btn-text">
                    <span className="nav-btn-label">Module précédent</span>
                    <span className="nav-btn-title">{prevModule.title}</span>
                  </span>
                </Link>
              ) : (
                <Link to="/modules" className="btn btn-secondary nav-btn">
                  ← Tous les modules
                </Link>
              )}
            </div>

            <div className="nav-slot nav-next">
              {nextChapitre ? (
                <Link to={`/chapitres/${nextChapitre.id}`} className="btn btn-primary nav-btn">
                  <span className="nav-btn-text">
                    <span className="nav-btn-label">Chapitre suivant</span>
                    <span className="nav-btn-title">{nextChapitre.title}</span>
                  </span>
                  <span className="nav-btn-arrow">→</span>
                </Link>
              ) : nextModule ? (
                <Link to={`/modules/${nextModule.id}`} className="btn btn-primary nav-btn">
                  <span className="nav-btn-text">
                    <span className="nav-btn-label">Module suivant</span>
                    <span className="nav-btn-title">{nextModule.title}</span>
                  </span>
                  <span className="nav-btn-arrow">→</span>
                </Link>
              ) : (
                <Link to="/modules" className="btn btn-primary nav-btn">
                  🎉 Parcours terminé — Voir tous les modules
                </Link>
              )}
            </div>
          </div>
        </div>
      </div>
    </MainLayout>
  );
};

export default ChapitrePage;
