import { Link } from 'react-router-dom';
import '../../styles/Content.css';

const ModuleList = ({ modules, loading = false }) => {
  if (loading) {
    return (
      <div className="loading-container">
        <div className="spinner"></div>
        <p>Chargement des modules...</p>
      </div>
    );
  }

  if (!modules || modules.length === 0) {
    return (
      <div className="empty-state">
        <h3>Aucun module disponible</h3>
        <p>Les modules seront bientôt disponibles.</p>
      </div>
    );
  }

  return (
    <div className="module-list">
      {modules.map((module) => (
        <Link
          key={module.id}
          to={`/modules/${module.id}`}
          className="module-card"
        >
          <div className="module-header">
            <div className="module-number">Module {module.order_index}</div>
            {!module.is_published && (
              <span className="badge badge-draft">Brouillon</span>
            )}
          </div>

          <h3 className="module-title">{module.title}</h3>

          {module.description && (
            <p className="module-description">{module.description}</p>
          )}

          <div className="module-footer">
            <span className="module-link-text">Voir les chapitres →</span>
          </div>
        </Link>
      ))}
    </div>
  );
};

export default ModuleList;
