import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { getModuleWithChapitres, getModules } from '../services/contentService';
import ChapitreCard from '../components/content/ChapitreCard';
import SidebarNavigation from '../components/content/SidebarNavigation';
import MainLayout from '../components/layout/MainLayout';
import '../styles/Content.css';

const ModuleDetailPage = () => {
  const { id } = useParams();
  const [module, setModule] = useState(null);
  const [chapitres, setChapitres] = useState([]);
  const [allModules, setAllModules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchModule();
  }, [id]);

  const fetchModule = async () => {
    try {
      setLoading(true);
      const [moduleRes, modulesRes] = await Promise.all([
        getModuleWithChapitres(id),
        getModules(),
      ]);

      if (moduleRes.success) {
        setModule(moduleRes.module);
        setChapitres(moduleRes.chapitres || []);
      }
      setAllModules(modulesRes.success ? modulesRes.modules || [] : []);
    } catch (err) {
      console.error('Erreur lors du chargement du module:', err);
      setError('Impossible de charger le module');
    } finally {
      setLoading(false);
    }
  };

  const prevModule = module
    ? allModules.find((m) => m.order_index === module.order_index - 1)
    : null;
  const nextModule = module
    ? allModules.find((m) => m.order_index === module.order_index + 1)
    : null;

  if (loading) {
    return (
      <MainLayout>
        <div className="page-with-sidebar">
          <SidebarNavigation currentModuleId={parseInt(id)} />
          <div className="page-content">
            <div className="loading-container">
              <div className="spinner"></div>
              <p>Chargement du module...</p>
            </div>
          </div>
        </div>
      </MainLayout>
    );
  }

  if (error || !module) {
    return (
      <MainLayout>
        <div className="page-with-sidebar">
          <SidebarNavigation />
          <div className="page-content">
            <div className="alert alert-error">
              {error || 'Module non trouvé'}
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
        <SidebarNavigation currentModuleId={parseInt(id)} />

        <div className="page-content">
          {/* Breadcrumb */}
          <div className="breadcrumb">
            <Link to="/modules">Modules</Link>
            <span>›</span>
            <span>{module.title}</span>
          </div>

          {/* Module Header */}
          <div className="module-detail-header">
            <div className="module-badge">Module {module.order_index}</div>
            <h1>{module.title}</h1>
            {module.description && (
              <p className="module-detail-description">{module.description}</p>
            )}
          </div>

          {/* Chapitres List */}
          <div className="section">
            <div className="section-header">
              <h2>Chapitres</h2>
              <p className="section-subtitle">
                {chapitres.length} chapitre{chapitres.length > 1 ? 's' : ''} disponible{chapitres.length > 1 ? 's' : ''}
              </p>
            </div>

            {chapitres.length === 0 ? (
              <div className="empty-state">
                <h3>Aucun chapitre disponible</h3>
                <p>Les chapitres seront bientôt ajoutés.</p>
              </div>
            ) : (
              <div className="chapitres-grid">
                {chapitres.map((chapitre) => (
                  <ChapitreCard
                    key={chapitre.id}
                    chapitre={chapitre}
                    moduleId={module.id}
                  />
                ))}
              </div>
            )}
          </div>

          {/* Navigation Buttons */}
          <div className="chapter-navigation">
            <div className="nav-slot nav-prev">
              {prevModule ? (
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
              {nextModule ? (
                <Link to={`/modules/${nextModule.id}`} className="btn btn-primary nav-btn">
                  <span className="nav-btn-text">
                    <span className="nav-btn-label">Module suivant</span>
                    <span className="nav-btn-title">{nextModule.title}</span>
                  </span>
                  <span className="nav-btn-arrow">→</span>
                </Link>
              ) : (
                <span className="nav-end-badge">🎉 Dernier module du parcours</span>
              )}
            </div>
          </div>
        </div>
      </div>
    </MainLayout>
  );
};

export default ModuleDetailPage;
