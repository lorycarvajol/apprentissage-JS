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

/**
 * Deux dimensions, lisibles indépendamment :
 *   - la teinture (couleur de la cire) dit CE QUE le titre récompense ;
 *   - la sertissure (bordure du sceau) dit COMBIEN il est difficile.
 *
 * Le nom courant de la couleur vient en premier, le terme héraldique ensuite :
 * « gueules » ou « tenné » servent le thème mais n'apprennent rien à qui ne les
 * connaît pas, et la légende doit d'abord se lire.
 */
const TEINTURES = [
  { category: 'progression', swatch: 'var(--tr-vert)', label: 'Vert (sinople)', sens: 'Progression' },
  { category: 'streak', swatch: 'var(--tr-tenne)', label: 'Orangé (tenné)', sens: 'Séries' },
  { category: 'achievement', swatch: 'var(--tr-or)', label: 'Or', sens: 'Accomplissements' },
  { category: 'special', swatch: 'var(--tr-purpure)', label: 'Violet (pourpre)', sens: 'Titres spéciaux' },
  { category: 'grade', swatch: 'var(--tr-gules)', label: 'Rouge (gueules)', sens: 'Grades' },
];

/**
 * La rareté est déduite de `points_reward`, déjà renvoyé par l'API pour tous les
 * badges, obtenus ou non — rien à changer côté serveur. Ce champ est la seule
 * mesure de difficulté que le modèle porte déjà : un badge qui rapporte 500
 * points est, par construction, plus dur qu'un badge à 25.
 *
 * L'échelle des sertissures reprend celle d'une chancellerie médiévale, où
 * l'importance d'un acte se lisait à la matière du sceau bien plus qu'à sa
 * couleur : cire nue pour l'ordinaire, filet gravé, double filet, sertissure de
 * métal, et bulle d'or pendante pour les actes solennels.
 *
 * Ordre décroissant : le premier seuil atteint gagne.
 */
const RARETES = [
  { id: 'souverain', seuil: 400, label: 'Souverain', matiere: "bulle d'or" },
  { id: 'tres-rare', seuil: 250, label: 'Très rare', matiere: "sertissure d'argent" },
  { id: 'rare', seuil: 125, label: 'Rare', matiere: 'double filet' },
  { id: 'peu-commun', seuil: 60, label: 'Peu commun', matiere: 'filet gravé' },
  { id: 'commun', seuil: 0, label: 'Commun', matiere: 'cire simple' },
];

const rareteDe = (points) =>
  RARETES.find((r) => (points ?? 0) >= r.seuil) || RARETES[RARETES.length - 1];

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
              {/* « frappés », et non « scellés » : c'est le mot déjà employé sur
                  les sceaux non obtenus (« Ce sceau n'a pas encore été frappé »),
                  et « scellé » se lit spontanément comme « verrouillé » — soit
                  l'inverse de ce que ce compteur mesure. */}
              {earnedCount} titres frappés sur {badges.length}
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
            {/*
              La légende est placée AVANT la grille, et complète.

              Elle était auparavant en fin de page et limitée aux catégories déjà
              obtenues, pour ne pas donner la clé de couleurs absentes de l'écran.
              L'intention se défendait, l'effet non : on traversait toute la
              grille avant de rencontrer la clé, et les couleurs qu'on ne
              comprenait pas étaient précisément celles qui n'étaient pas
              légendées. Rien n'est divulgué au passage — le nom et la condition
              d'un sceau non obtenu restent masqués côté API.
            */}
            <div className="trophy-legend">
              <div className="trophy-legend-group">
                <h2 className="trophy-legend-title">Teinture — ce que le sceau récompense</h2>
                <ul className="trophy-legend-list">
                  {TEINTURES.map((item) => (
                    <li className="trophy-legend-item" key={item.category}>
                      <span
                        className="trophy-legend-swatch"
                        style={{ background: item.swatch }}
                        aria-hidden="true"
                      ></span>
                      <span className="trophy-legend-label">{item.label}</span>
                      <span className="trophy-legend-sense">{item.sens}</span>
                    </li>
                  ))}
                </ul>
              </div>

              <div className="trophy-legend-group">
                <h2 className="trophy-legend-title">Sertissure — rareté du titre</h2>
                <ul className="trophy-legend-list">
                  {RARETES.slice().reverse().map((item) => (
                    <li className="trophy-legend-item" key={item.id}>
                      <span
                        className={`trophy-legend-bezel seal-rarity-${item.id}`}
                        aria-hidden="true"
                      ></span>
                      <span className="trophy-legend-label">{item.label}</span>
                      <span className="trophy-legend-sense">{item.matiere}</span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>

            <div className="seal-grid">
              {badges.map((badge, index) => {
                const rarete = rareteDe(badge.points_reward);

                return (
                  <div
                    key={badge.id}
                    className={`seal seal-category-${badge.category} seal-rarity-${rarete.id} ${
                      badge.earned ? 'seal-earned' : 'seal-locked'
                    }`}
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

                    {/* La rareté s'affiche même sur un sceau non obtenu : elle ne
                        révèle ni le nom ni la condition, mais elle donne à la
                        grille la variété qui lui manquait — sans elle, tout ce
                        qui reste à conquérir est un mur de disques identiques. */}
                    <p className="seal-rarity-label">{rarete.label}</p>

                    <p className="seal-description">
                      {badge.earned ? badge.description : "Ce sceau n'a pas encore été frappé."}
                    </p>
                  </div>
                );
              })}
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
