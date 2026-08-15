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

// La cire vierge n'est pas une teinture héraldique, mais c'est la couleur
// réellement majoritaire à l'écran tant que peu de titres sont obtenus : elle
// mérite donc d'être expliquée plutôt que laissée sans clé de lecture.
const BLANK_SEAL_LEGEND = {
  category: '__blank',
  swatch: 'var(--tr-wax-blank)',
  label: 'Cire vierge — titre non obtenu',
};

/**
 * La légende ne doit décrire que les couleurs effectivement visibles dans la
 * grille. Un sceau non obtenu reste volontairement vierge (voir Trophies.css,
 * « c'est le mystère ») : afficher malgré tout les cinq teintures reviendrait à
 * donner la clé de couleurs absentes de la page — et, sur un compte neuf où
 * aucun titre n'est encore scellé, à légender cinq couleurs alors que tout est
 * gris. Les teintures apparaissent donc au fur et à mesure qu'elles sont
 * débloquées, ce qui sert aussi la progressivité voulue par le thème.
 */
const buildLegend = (badges) => {
  const earnedCategories = new Set(
    badges.filter((badge) => badge.earned).map((badge) => badge.category)
  );

  const legend = HERALDRY_LEGEND.filter((item) => earnedCategories.has(item.category));

  if (badges.some((badge) => !badge.earned)) {
    legend.push(BLANK_SEAL_LEGEND);
  }

  return legend;
};

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
  const legend = buildLegend(badges);

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

            {legend.length > 0 && (
              <div className="trophy-legend">
                {legend.map((item) => (
                  <span className="trophy-legend-item" key={item.category}>
                    <span
                      className="trophy-legend-swatch"
                      style={{ background: item.swatch }}
                    ></span>
                    {item.label}
                  </span>
                ))}
              </div>
            )}
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
