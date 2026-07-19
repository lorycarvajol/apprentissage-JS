import { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { getModules } from '../../services/contentService';
import '../../styles/Content.css';

const SidebarNavigation = ({ currentModuleId = null }) => {
  const [modules, setModules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isOpen, setIsOpen] = useState(false);
  const location = useLocation();

  useEffect(() => {
    fetchModules();
  }, []);

  const fetchModules = async () => {
    try {
      const response = await getModules();
      setModules(response.modules || []);
    } catch (error) {
      console.error('Erreur lors du chargement des modules:', error);
    } finally {
      setLoading(false);
    }
  };

  const toggleSidebar = () => {
    setIsOpen(!isOpen);
  };

  if (loading) {
    return (
      <aside className={`sidebar ${isOpen ? 'sidebar-open' : ''}`}>
        <div className="sidebar-sticky">
          <div className="sidebar-content">
            <p>Chargement...</p>
          </div>
        </div>
      </aside>
    );
  }

  return (
    <>
      {/* Burger menu button for mobile */}
      <button
        className="sidebar-toggle"
        onClick={toggleSidebar}
        aria-label="Toggle navigation"
      >
        <span></span>
        <span></span>
        <span></span>
      </button>

      {/* Overlay for mobile */}
      {isOpen && (
        <div
          className="sidebar-overlay"
          onClick={toggleSidebar}
        ></div>
      )}

      {/* Sidebar */}
      <aside className={`sidebar ${isOpen ? 'sidebar-open' : ''}`}>
        <div className="sidebar-sticky">
          <div className="sidebar-header">
            <h3>Modules</h3>
            <button
              className="sidebar-close"
              onClick={toggleSidebar}
            >
              ✕
            </button>
          </div>

          <nav className="sidebar-content">
            <ul className="sidebar-modules">
              {modules.map((module) => {
                const isActive = currentModuleId === module.id ||
                  location.pathname.startsWith(`/modules/${module.id}`);

                return (
                  <li key={module.id} className="sidebar-module">
                    <Link
                      to={`/modules/${module.id}`}
                      className={`sidebar-module-link ${isActive ? 'active' : ''}`}
                      onClick={() => setIsOpen(false)}
                    >
                      <span className="module-order">{module.order_index}</span>
                      <span className="module-title">{module.title}</span>
                    </Link>
                  </li>
                );
              })}
            </ul>

            <div className="sidebar-footer">
              <Link
                to="/modules"
                className="btn-back-to-modules"
                onClick={() => setIsOpen(false)}
              >
                ← Tous les modules
              </Link>
            </div>
          </nav>
        </div>
      </aside>
    </>
  );
};

export default SidebarNavigation;
