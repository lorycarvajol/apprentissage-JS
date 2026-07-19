import { useState, useEffect } from 'react';
import MainLayout from '../components/layout/MainLayout';
import SidebarNavigation from '../components/content/SidebarNavigation';
import '../styles/Content.css';
import '../styles/Preview.css';

const PreviewPage = () => {
  const [content, setContent] = useState('');
  const [title, setTitle] = useState('Aperçu du contenu');
  const [moduleId, setModuleId] = useState(null);

  useEffect(() => {
    // Écouter les messages de la fenêtre parent (admin)
    const handleMessage = (event) => {
      // Sécurité : vérifier l'origine si nécessaire
      // if (event.origin !== window.location.origin) return;

      if (event.data.type === 'PREVIEW_UPDATE') {
        setContent(event.data.content || '');
        if (event.data.title) {
          setTitle(event.data.title);
        }
        if (event.data.moduleId !== undefined) {
          setModuleId(event.data.moduleId);
        }
      }
    };

    window.addEventListener('message', handleMessage);

    // Notifier la fenêtre parent que la preview est prête
    if (window.opener) {
      window.opener.postMessage({ type: 'PREVIEW_READY' }, window.location.origin);
    }

    return () => {
      window.removeEventListener('message', handleMessage);
    };
  }, []);

  return (
    <MainLayout>
      {/* Même structure que ChapitrePage (page-with-sidebar / page-content /
          theory-container) : la preview partage ainsi automatiquement toute
          largeur et tout comportement responsive avec la vraie page, sans
          CSS dédié à maintenir en double. */}
      <div className="page-with-sidebar">
        <SidebarNavigation currentModuleId={moduleId} />

        <div className="page-content">
          <div className="preview-live-banner">
            🔍 Aperçu en direct — se met à jour automatiquement pendant l'édition
          </div>

          <div className="chapitre-page-header">
            <h1 className="chapitre-page-title">{title}</h1>
          </div>

          <div className="content-section">
            <div className="theory-container">
              {content ? (
                <div className="theory-content" dangerouslySetInnerHTML={{ __html: content }} />
              ) : (
                <div className="preview-empty">
                  <p>En attente de contenu...</p>
                  <p className="text-muted">Le contenu s'affichera ici au fur et à mesure de votre édition</p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </MainLayout>
  );
};

export default PreviewPage;
