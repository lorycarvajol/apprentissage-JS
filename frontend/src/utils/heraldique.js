/**
 * Vocabulaire héraldique de la salle des trophées, partagé entre la page des
 * sceaux et la carte de révélation.
 *
 * Ces tables vivaient dans TrophyRoomPage.jsx tant que cette page était seule à
 * les lire. La carte de révélation en a besoin aussi — elle explique un sceau,
 * elle doit donc nommer sa teinture et sa sertissure exactement comme la légende
 * le fait. Les dupliquer aurait garanti qu'elles divergent au premier ajout de
 * badge.
 */

/**
 * Glyphes gravés dans la cire. Tous monochromes, volontairement : « 🔥 » avait
 * beau dire « série » plus clairement, il s'affiche en émoji couleur et cassait
 * l'illusion du sceau frappé au milieu de quatre glyphes noirs.
 */
export const CATEGORY_GLYPH = {
  progression: '⚜',
  streak: '✶',
  achievement: '✦',
  special: '✧',
  grade: '♛',
};

/**
 * La teinture dit CE QUI fait gagner le titre. Le nom courant de la couleur
 * vient en premier, le terme héraldique ensuite : « gueules » ou « tenné »
 * servent le thème mais n'apprennent rien à qui ne les connaît pas.
 *
 * `sens` décrit ce qu'il faut FAIRE, pas comment la catégorie s'appelle. Chaque
 * libellé est tiré des condition_type réellement semés dans schema.sql :
 *   progression  complete_chapter x5, complete_module x2, complete_all_modules
 *   streak       streak_days x4
 *   achievement  first_try_success x3, points_total x2
 *   special      time_of_day x2, late_success
 *   grade        points_total x5, par paliers croissants
 * Ajouter un badge d'une nature nouvelle suppose de revoir le libellé concerné.
 */
export const TEINTURES = [
  { category: 'progression', label: 'Vert (sinople)', sens: 'Chapitres et modules achevés' },
  { category: 'streak', label: 'Orangé (tenné)', sens: "Jours d'affilée" },
  { category: 'achievement', label: 'Or', sens: 'Sans-faute et points amassés' },
  { category: 'special', label: 'Violet (pourpre)', sens: 'Heures indues, obstination' },
  { category: 'grade', label: 'Rouge (gueules)', sens: 'Rang dans la hiérarchie' },
];

/**
 * La sertissure dit ce que le titre rapporte, donc sa rareté. Elle est déduite
 * de `points_reward`, déjà renvoyé par l'API pour tous les badges, obtenus ou
 * non — c'est la seule mesure de difficulté que le modèle porte déjà.
 *
 * L'échelle reprend celle d'une chancellerie médiévale, où l'importance d'un
 * acte se lisait à la matière de son sceau bien avant sa couleur.
 *
 * `part` est le nombre de sceaux du degré, et il n'est pas décoratif : c'est lui
 * qui donne les rangées du losange (voir RANGEES). Les seuils sont calés sur les
 * 25 badges semés par schema.sql pour produire exactement 1/3/5/7/9. Ajouter un
 * badge sans revoir ces deux tableaux casserait la figure.
 *
 * Ordre décroissant : le premier seuil atteint gagne.
 */
export const RARETES = [
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
export const RANGEES = [1, 3, 5, 7, 5, 3, 1];

export const rareteDe = (points) =>
  RARETES.find((r) => (points ?? 0) >= r.seuil) || RARETES[RARETES.length - 1];

export const rangDe = (points) => RARETES.findIndex((r) => r.id === rareteDe(points).id);

export const teintureDe = (category) =>
  TEINTURES.find((t) => t.category === category) || TEINTURES[0];
