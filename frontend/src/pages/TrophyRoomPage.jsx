import { useState, useEffect, useMemo } from 'react';
import MainLayout from '../components/layout/MainLayout';
import SceauRevelation from '../components/trophies/SceauRevelation';
import { getGamificationSummary } from '../services/gamificationService';
import { useTrophees } from '../contexts/TropheesContext';
import {
  CATEGORY_GLYPH,
  TEINTURES,
  RARETES,
  RANGEES,
  rareteDe,
  rangDe,
} from '../utils/heraldique';
import '../styles/Trophies.css';

const TrophyRoomPage = () => {
  const [badges, setBadges] = useState([]);
  const [loading, setLoading] = useState(true);
  const [apercu, setApercu] = useState(null);
  // File de la carte de révélation. Vide = carte fermée.
  const [carte, setCarte] = useState({ sceaux: [], mode: 'revelation' });
  const { nonVus, marquerVus, rafraichir } = useTrophees();

  useEffect(() => {
    fetchGamification();
  }, []);

  /**
   * Les titres décrochés mais pas encore regardés se dévoilent à l'arrivée sur
   * la salle — c'est la contrepartie de la lueur dans l'en-tête : elle promet
   * quelque chose, la page doit le tenir.
   *
   * `marquerVus` est appelé ici, à l'ouverture, et non à la fermeture de la
   * carte : un utilisateur qui ferme au premier titre a quand même été averti,
   * et rouvrir la même fanfare à chaque visite serait pénible. Les sceaux
   * restent consultables au clic.
   */
  useEffect(() => {
    if (loading || nonVus.length === 0) return;
    setCarte({ sceaux: nonVus, mode: 'revelation' });
    marquerVus(nonVus.map((b) => b.id));
  }, [loading, nonVus, marquerVus]);

  const fetchGamification = async () => {
    try {
      const response = await getGamificationSummary();
      setBadges(response.badges || []);
      // L'en-tête et la page lisent la même collection : la rafraîchir ici
      // évite que la lueur survive à la visite qui l'a résolue.
      rafraichir();
    } catch (error) {
      console.error('Erreur lors du chargement des badges:', error);
    } finally {
      setLoading(false);
    }
  };

  /**
   * Tri par rareté, et surtout PAS par « obtenu d'abord » comme le renvoie
   * l'API : la position d'un sceau doit être stable. Une salle des trophées
   * dont les sceaux changent de place à chaque titre décroché ne se mémorise
   * pas, et le losange perdrait sa lecture par rangée. `id` départage, pour que
   * l'ordre soit le même d'une visite à l'autre.
   */
  const ordonnes = useMemo(
    () =>
      [...badges].sort(
        (a, b) =>
          rangDe(a.points_reward) - rangDe(b.points_reward) ||
          (b.points_reward ?? 0) - (a.points_reward ?? 0) ||
          a.id - b.id
      ),
    [badges]
  );

  // Découpe en rangées. Une collection qui ne ferait pas exactement 25 ne casse
  // rien : les rangées se remplissent dans l'ordre et le reliquat forme une
  // dernière rangée libre.
  const rangees = useMemo(() => {
    const out = [];
    let i = 0;
    for (const taille of RANGEES) {
      if (i >= ordonnes.length) break;
      out.push(ordonnes.slice(i, i + taille));
      i += taille;
    }
    if (i < ordonnes.length) out.push(ordonnes.slice(i));
    return out;
  }, [ordonnes]);

  const earnedCount = badges.filter((badge) => badge.earned).length;

  const detail = apercu
    ? {
        titre: apercu.earned ? apercu.name : 'Sceau vierge',
        rarete: rareteDe(apercu.points_reward),
        texte: apercu.earned ? apercu.description : "Ce sceau n'a pas encore été frappé.",
        earned: apercu.earned,
      }
    : null;

  return (
    <MainLayout>
      <div className="trophy-room-page">
        <section className="trophy-room-hero">
          {/* Emblème en ligne avec le titre plutôt qu'empilé au-dessus : la
              bannière occupait trois blocs verticaux et repoussait le losange
              sous la ligne de flottaison. */}
          <div className="trophy-room-crest">
            <span className="trophy-emblem" aria-hidden="true">♛</span>
            <h1>Salle des trophées</h1>
          </div>
          {!loading && (
            <p className="trophy-room-subtitle">
              {/* « frappés », et non « scellés » : c'est le mot déjà employé sur
                  les sceaux non obtenus, et « scellé » se lit spontanément
                  comme « verrouillé » — soit l'inverse de ce que ce compteur
                  mesure. */}
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
              Les deux légendes encadrent le losange, une par dimension. Elles
              étaient auparavant en fin de page et limitées aux catégories déjà
              obtenues : on traversait toute la grille avant de rencontrer la
              clé, et les couleurs qu'on ne comprenait pas étaient précisément
              celles qui n'étaient pas légendées. Rien n'est divulgué au
              passage — le nom et la condition d'un sceau non obtenu ne quittent
              pas le serveur.
            */}
            <div className="trophy-hall">
              <aside className="trophy-legend trophy-legend-left">
                <h2 className="trophy-legend-title">Teinture</h2>
                <p className="trophy-legend-intro">ce que le sceau récompense</p>
                <ul className="trophy-legend-list">
                  {TEINTURES.map((item) => (
                    <li className="trophy-legend-item" key={item.category}>
                      <span
                        className={`trophy-legend-swatch seal-category-${item.category}`}
                        aria-hidden="true"
                      ></span>
                      <span className="trophy-legend-text">
                        <span className="trophy-legend-label">{item.label}</span>
                        <span className="trophy-legend-sense">{item.sens}</span>
                      </span>
                    </li>
                  ))}
                </ul>
              </aside>

              <div className="trophy-lozenge-wrap">
                <div className="seal-lozenge">
                  {rangees.map((rangee, r) => (
                    <div className="seal-row" key={r}>
                      {rangee.map((badge, index) => {
                        const rarete = rareteDe(badge.points_reward);
                        const nom = badge.earned ? badge.name : 'Sceau vierge';

                        return (
                          <button
                            type="button"
                            key={badge.id}
                            className={`seal seal-category-${badge.category} seal-rarity-${rarete.id} ${
                              badge.earned ? 'seal-earned' : 'seal-locked'
                            }`}
                            style={{ '--seal-delay': `${(r * 5 + index) * 0.035}s` }}
                            onMouseEnter={() => setApercu(badge)}
                            onMouseLeave={() => setApercu(null)}
                            onFocus={() => setApercu(badge)}
                            onBlur={() => setApercu(null)}
                            onClick={() =>
                              badge.earned && setCarte({ sceaux: [badge], mode: 'consultation' })
                            }
                            disabled={!badge.earned}
                            aria-label={`${nom}, ${rarete.label}. ${
                              badge.earned ? badge.description : "Ce sceau n'a pas encore été frappé."
                            }`}
                          >
                            <span className="seal-medallion" aria-hidden="true">
                              {badge.earned ? CATEGORY_GLYPH[badge.category] || '✦' : ''}
                            </span>
                          </button>
                        );
                      })}
                    </div>
                  ))}
                </div>

                {/*
                  Un losange de 25 sceaux ne laisse pas la place d'écrire nom et
                  description sous chaque médaillon — à sept de large, les
                  libellés se chevaucheraient. Le détail est donc reporté dans ce
                  panneau, alimenté par le survol ET par le focus clavier, et sa
                  hauteur est réservée en permanence pour que la figure ne se
                  déplace pas quand il se remplit.
                */}
                <div className="seal-detail" aria-live="polite">
                  {detail ? (
                    <>
                      <p className="seal-detail-title">
                        {detail.titre}
                        <span className="seal-detail-rarity">{detail.rarete.label}</span>
                      </p>
                      <p className="seal-detail-text">{detail.texte}</p>
                    </>
                  ) : (
                    <p className="seal-detail-hint">
                      Survolez un sceau pour lire son titre.
                    </p>
                  )}
                </div>
              </div>

              <aside className="trophy-legend trophy-legend-right">
                <h2 className="trophy-legend-title">Sertissure</h2>
                {/* Énoncé de la règle exacte, et non « ce qu'il a coûté » : la
                    rareté est déduite de points_reward, c'est-à-dire de ce que
                    le titre rapporte. Le coût n'en est qu'une inférence — la
                    formulation précédente prenait l'effet pour la cause. */}
                <p className="trophy-legend-intro">plus il rapporte, plus il est rare</p>
                <ul className="trophy-legend-list">
                  {RARETES.slice().reverse().map((item) => (
                    <li className="trophy-legend-item" key={item.id}>
                      <span
                        className={`trophy-legend-bezel seal-rarity-${item.id}`}
                        aria-hidden="true"
                      ></span>
                      <span className="trophy-legend-text">
                        <span className="trophy-legend-label">{item.label}</span>
                        <span className="trophy-legend-sense">{item.matiere}</span>
                      </span>
                    </li>
                  ))}
                </ul>
              </aside>
            </div>
          </>
        ) : (
          <div className="empty-state">
            <p>Le forgeron n'a encore coulé aucun sceau.</p>
          </div>
        )}

        {carte.sceaux.length > 0 && (
          <SceauRevelation
            sceaux={carte.sceaux}
            mode={carte.mode}
            onClose={() => setCarte({ sceaux: [], mode: 'revelation' })}
          />
        )}
      </div>
    </MainLayout>
  );
};

export default TrophyRoomPage;
