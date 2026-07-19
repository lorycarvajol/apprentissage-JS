import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { getModules } from '../services/contentService';
import { getGamificationSummary } from '../services/gamificationService';
import api from '../services/api';
import MainLayout from '../components/layout/MainLayout';
import '../styles/Dashboard.css';

const DashboardPage = () => {
  const { user } = useAuth();
  const [modules, setModules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({ total_points: 0, exercices_completed: 0, time_spent_minutes: 0 });
  const [streak, setStreak] = useState({ current: 0, longest: 0 });

  useEffect(() => {
    fetchModules();
    fetchStats();
    fetchGamification();
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

  const fetchStats = async () => {
    try {
      const response = await api.get('/dashboard/stats');
      setStats(response.data);
    } catch (error) {
      console.error('Erreur lors du chargement des statistiques:', error);
    }
  };

  const fetchGamification = async () => {
    try {
      const response = await getGamificationSummary();
      setStreak(response.streak || { current: 0, longest: 0 });
    } catch (error) {
      console.error('Erreur lors du chargement de la gamification:', error);
    }
  };

  const formatTimeSpent = (minutes) => {
    if (minutes < 60) return `${minutes}min`;
    return `${Math.floor(minutes / 60)}h${String(minutes % 60).padStart(2, '0')}`;
  };

  return (
    <MainLayout>
      <div className="dashboard-page">
        {/* Hero — rendu comme un bloc JSDoc */}
        <section className="dashboard-hero">
          <div className="hero-file-tab">dashboard.js</div>
          <div className="hero-content">
            <p className="hero-comment-line">/**</p>
            <p className="hero-comment-line hero-greeting">
              {' '}* Bonjour, <strong>{user?.first_name || user?.username}</strong> 👋
            </p>
            <p className="hero-comment-line">
              {' '}* <span className="hero-tag">@objectif</span> Reprenons où vous en étiez.
            </p>
            <p className="hero-comment-line"> */</p>
          </div>
        </section>

        {/* Stats — présentées comme les propriétés d'une classe, avec leur visibilité */}
        <section className="dashboard-properties">
          <span className="dashboard-properties-eyebrow">class Apprenant</span>

          <div className="property-row">
            <span className="property-visibility is-public">+</span>
            <span className="property-name">modulesDisponibles</span>
            <span className="property-value">{modules.length}</span>
          </div>

          <div className="property-row">
            <span className="property-visibility is-public">+</span>
            <span className="property-name">exercicesCompletes</span>
            <span className="property-value">{stats.exercices_completed}</span>
          </div>

          <div className="property-row">
            <span className="property-visibility is-public">+</span>
            <span className="property-name">pointsTotal</span>
            <span className="property-value">{stats.total_points}</span>
          </div>

          <div className="property-row">
            <span className="property-visibility is-protected">#</span>
            <span className="property-name">streakActuel</span>
            <span className="property-value">
              {streak.current} jour{streak.current > 1 ? 's' : ''}
            </span>
          </div>

          <div className="property-row">
            <span className="property-visibility is-private">-</span>
            <span className="property-name">tempsApprentissage</span>
            <span className="property-value">{formatTimeSpent(stats.time_spent_minutes)}</span>
          </div>
        </section>

        {/* Modules — rendus comme des boîtes de classe UML */}
        <section className="dashboard-section">
          <div className="section-header">
            <div>
              <span className="section-header-eyebrow">import {'{'} modules {'}'} from './app.js';</span>
              <h2>Commencez votre apprentissage</h2>
            </div>
            <Link to="/modules" className="btn-view-all">
              voir_tout() →
            </Link>
          </div>

          {loading ? (
            <div className="loading-state">
              <div className="spinner"></div>
              <p>// Chargement des modules...</p>
            </div>
          ) : modules.length > 0 ? (
            <div className="modules-preview">
              {modules.slice(0, 3).map((module) => (
                <Link
                  key={module.id}
                  to={`/modules/${module.id}`}
                  className="module-preview-card"
                >
                  <div className="module-preview-header">
                    <span className="module-preview-keyword">class</span>
                    <span className="module-preview-badge">Module{String(module.order_index).padStart(2, '0')}</span>
                  </div>
                  <div className="module-preview-body">
                    <h3 className="module-preview-title">{module.title}</h3>
                    <p className="module-preview-description">
                      {module.description || 'Découvrez ce module de formation'}
                    </p>
                    <div className="module-preview-footer">
                      <span className="preview-link-text">+commencer(): void</span>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          ) : (
            <div className="empty-state">
              <p>// Aucun module disponible pour le moment</p>
            </div>
          )}
        </section>
      </div>
    </MainLayout>
  );
};

export default DashboardPage;
