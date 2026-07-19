import { useState, useEffect } from 'react';
import ModuleList from '../components/content/ModuleList';
import MainLayout from '../components/layout/MainLayout';
import { getModules } from '../services/contentService';
import '../styles/Content.css';

const ModulesPage = () => {
  const [modules, setModules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchModules();
  }, []);

  const fetchModules = async () => {
    try {
      setLoading(true);
      const response = await getModules();
      setModules(response.modules || []);
    } catch (err) {
      console.error('Erreur lors du chargement des modules:', err);
      setError('Impossible de charger les modules. Veuillez réessayer.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <MainLayout>
      <div className="page-container">
        {/* Page Header */}
        <div className="page-header-simple">
          <h1>Parcours de formation JavaScript</h1>
          <p>Découvrez la Programmation Orientée Objet avec JavaScript</p>
        </div>

        {/* Main content */}
        <div className="page-content">
          {error && (
            <div className="alert alert-error">
              {error}
              <button onClick={fetchModules} className="btn btn-sm">
                Réessayer
              </button>
            </div>
          )}

          <div className="section">
            <div className="section-header">
              <h2>Modules disponibles</h2>
              <p className="section-subtitle">
                {modules.length} module{modules.length > 1 ? 's' : ''} de formation
              </p>
            </div>

            <ModuleList modules={modules} loading={loading} />
          </div>
        </div>
      </div>
    </MainLayout>
  );
};

export default ModulesPage;
