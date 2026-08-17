import { useState, useEffect, useMemo } from 'react';
import MainLayout from '../components/layout/MainLayout';
import { getGamificationSummary } from '../services/gamificationService';
import '../styles/Trophies.css';

/**
 * Glyphes gravés dans la cire. Tous monochromes, volontairement : « 🔥 » avait
 * beau dire « série » plus clairement, il s'affiche en émoji couleur et cassait
 * l'illusion du sceau frappé au milieu de quatre glyphes noirs. La catégorie
 * est de toute façon portée par la teinture, le glyphe ne fait que la redire.
 */
const CATEGORY_GLYPH = {
  progression: '⚜',
  streak: '✶',
  achievement: '✦',
  special: '✧',
  grade: '♛',
};

/**
 * Deux dimensions, lisibles indépendamment :
 *   - la teinture (couleur de la cire) dit CE QUI fait gagner le titre ;
 *   - la sertissure (bordure du sceau) dit ce qu'il rapporte, donc sa rareté.
 *
 * Le nom courant de la couleur vient en premier, le terme héraldique ensuite :
 * « gueules » ou « tenné » servent le thème mais n'apprennent rien à qui ne les
 * connaît pas, et la légende doit d'abord se lire.
 *
 * `sens` décrit ce qu'il faut FAIRE, pas comment la catégorie s'appelle. Les
 * libellés précédents — « Accomplissements », « Titres spéciaux » — ne faisaient
 * que traduire le nom technique de la catégorie : ils occupaient la place d'une
 * explication sans en donner une. Chaque libellé ci-dessous est tiré des
 * condition_type réellement semés dans schema.sql :
 *   progression  complete_chapter x5, complete_module x2, complete_all_modules
 *   streak       streak_days x4
 *   achievement  first_try_success x3, points_total x2
 *   special      time_of_day x2, late_success
 *   grade        points_total x5, par paliers croissants
 * Ajouter un badge d'une nature nouvelle suppose de revoir le libellé concerné.
 */
const TEINTURES = [
  { category: 'progression', label: 'Vert (sinople)', sens: 'Chapitres et modules achevés' },
  { category: 'streak', label: 'Orangé (tenné)', sens: "Jours d'affilée" },
  { category: 'achievement', label: 'Or', sens: 'Sans-faute et points amassés' },
  { category: 'special', label: 'Violet (pourpre)', sens: 'Heures indues, obstination' },
  { category: 'grade', label: 'Rouge (gueules)', sens: 'Rang dans la hiérarchie' },
];

/**
 * La rareté est déduite de `points_reward`, déjà renvoyé par l'API pour tous les
 * badges, obtenus ou non — rien à changer côté serveur. Ce champ est la seule
 * mesure de difficulté que le modèle porte déjà : un titre qui rapporte 500
 * points est, par construction, plus dur qu'un titre à 25.
 *
 * L'échelle des sertissures reprend celle d'une chancellerie médiévale, où
 * l'importance d'un acte se lisait à la matière de son sceau bien avant sa
 * couleur : cire nue pour l'ordinaire, filet gravé, double filet, sertissure de
 * métal, et bulle d'or pendante pour les actes solennels.
 *
 * `part` est le nombre de sceaux du degré, et il n'est pas décoratif : c'est lui
 * qui donne les rangées du losange (voir RANGEES). Les seuils sont calés sur les
 * 25 badges semés par schema.sql pour produire exactement 1/3/5/7/9. Ajouter un
 * badge sans revoir ces deux tableaux casserait la figure.
 *
 * Ordre décroissant : le premier seuil atteint gagne.
 */
const RARETES = [
  { id: 'souverain', seuil: 500, part: 1, label: 'Souverain', matiere: "bulle d'or" },
  { id: 'tres-rare', seuil: 250, part: 3, label: 'Très rare', matiere: "sertissure d'argent" },
  { id: 'rare', seuil: 125, part: 5, label: 'Rare', matiere: 'double filet' },
  { id: 'peu-commun', seuil: 60, part: 7, label: 'Peu commun', matiere: 'filet gravé' },
  { id: 'commun', seuil: 0, part: 9, label: 'Commun', matiere: 'cire simple' },
];

/**
 * Le losange, de la pointe haute à la pointe basse. Somme = 25.
 *
 * Les sceaux étant triés par rareté décroissante, chaque rangée du haut est
 * exactement un degré : 1 souverain à la pointe, puis 3 très rares, 5 rares,
 * 7 peu communs, et les 9 communs qui remplissent les trois dernières rangées.
 * La figure n'est donc pas un habillage posé sur la grille — elle EST la
 * pyramide de rareté : plus on monte, plus c'est rare, et plus c'est étroit.
 */
const RANGEES = [1, 3, 5, 7, 5, 3, 1];

const rareteDe = (points) =>
  RARETES.find((r) => (points ?? 0) >= r.seuil) || RARETES[RARETES.length - 1];

const rangDe = (points) => RARETES.findIndex((r) => r.id === rareteDe(points).id);

const TrophyRoomPage = () => {
  const [badges, setBadges] = useState([]);
  const [loading, setLoading] = useState(true);
  const [apercu, setApercu] = useState(null);

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
      </div>
    </MainLayout>
  );
};

export default TrophyRoomPage;
