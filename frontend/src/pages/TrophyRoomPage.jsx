import { useState, useEffect } from 'react';
import MainLayout from '../components/layout/MainLayout';
import { getGamificationSummary } from '../services/gamificationService';
import '../styles/Trophies.css';

const CATEGORY_GLYPH = {
  progression: '⚜',
  streak: '🔥',
  achievement: '✦',
  special: '✧',
  grade: '♛',
};

const HERALDRY_LEGEND = [
  { category: 'progression', swatch: 'var(--tr-vert)', label: 'Vert — Progression' },
  { category: 'streak', swatch: 'var(--tr-tenne)', label: 'Tenné — Séries' },
  { category: 'achievement', swatch: 'var(--tr-or)', label: 'Or — Accomplissement' },
  { category: 'special', swatch: 'var(--tr-purpure)', label: 'Purpure — Spécial' },
  { category: 'grade', swatch: 'var(--tr-gules)', label: 'Gueules — Grades' },
];

const TrophyRoomPage = () => {
  const [badges, setBadges] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchGamification();
  }, []);

  const fetchGamification = async () => {
    try {
      const response = await getGamificationSummary();
      setBadges(response.badges || []);
    } catch (error) {
      console.error('Erreur lors du chargement des badges:', error);
    } finally {
      setLoading(false);
    }
  };

  const earnedCount = badges.filter((badge) => badge.earned).length;

  return (
    <MainLayout>
      <div className="trophy-room-page">
        <section className="trophy-room-hero">
          <div className="trophy-emblem">♛</div>
          <h1>Salle des trophées</h1>
          {!loading && (
            <p className="trophy-room-subtitle">
              {earnedCount} / {badges.length} titres scellés
            </p>
          )}
        </section>

        {loading ? (
          <div className="loading-state">
            <div className="spinner"></div>
            <p>On ranime les braises de la salle des sceaux...</p>
          </div>
        ) : badges.length > 0 ? (
          <>
            <div className="seal-grid">
              {badges.map((badge, index) => (
                <div
                  key={badge.id}
                  className={`seal seal-category-${badge.category} ${badge.earned ? 'seal-earned' : 'seal-locked'}`}
                  style={{ '--seal-delay': `${index * 0.04}s` }}
                >
                  <div className="seal-medallion">
                    {badge.earned ? CATEGORY_GLYPH[badge.category] || '✦' : ''}
                  </div>

                  {badge.category === 'grade' ? (
                    <h3 className="seal-ribbon">
                      {badge.earned ? badge.name : 'Rang scellé'}
                    </h3>
                  ) : (
                    <h3 className="seal-name">
                      {badge.earned ? badge.name : 'Sceau vierge'}
                    </h3>
                  )}

                  <p className="seal-description">
                    {badge.earned ? badge.description : "Ce sceau n'a pas encore été frappé."}
                  </p>
                </div>
              ))}
            </div>

            <div className="trophy-legend">
              {HERALDRY_LEGEND.map((item) => (
                <span className="trophy-legend-item" key={item.category}>
                  <span className="trophy-legend-swatch" style={{ background: item.swatch }}></span>
                  {item.label}
                </span>
              ))}
            </div>
          </>
        ) : (
          <div className="empty-state">
            <p>Le forgeron n'a encore coulé aucun sceau.</p>
          </div>
        )}
      </div>
    </MainLayout>
  );
};

export default TrophyRoomPage;
