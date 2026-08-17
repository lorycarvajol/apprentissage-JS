import { createContext, useContext, useState, useEffect, useCallback, useMemo, useRef } from 'react';
import { getGamificationSummary } from '../services/gamificationService';
import { useAuth } from './AuthContext';

/**
 * Suit les titres décrochés mais pas encore regardés.
 *
 * Le besoin : quand un sceau est frappé, l'onglet « Trophées » doit s'allumer,
 * et d'autant plus fort que plusieurs titres attendent. L'information n'existe
 * nulle part côté serveur — `user_badges` sait quand un badge a été obtenu, pas
 * s'il a été vu. Plutôt que d'ajouter une colonne `seen_at` et l'aller-retour
 * d'API qui va avec, la liste des sceaux déjà regardés est gardée dans le
 * navigateur.
 *
 * Ce choix a une conséquence assumée : la mémoire est par navigateur. Ouvrir la
 * plateforme sur une autre machine remontre les titres comme neufs. Pour une
 * fanfare de découverte, c'est bénin — et c'est même le comportement souhaitable
 * quand on se connecte ailleurs pour la première fois.
 */

const CLE_VUS = 'apprentissage-js.sceaux-vus';

const TropheesContext = createContext(null);

const lireVus = () => {
  try {
    const brut = localStorage.getItem(CLE_VUS);
    const liste = brut ? JSON.parse(brut) : null;
    return Array.isArray(liste) ? liste : null;
  } catch {
    // Stockage indisponible (navigation privée verrouillée, quota) : on
    // dégrade en « tout est déjà vu », c'est-à-dire pas de fanfare, plutôt que
    // de faire échouer le rendu de l'en-tête sur toutes les pages.
    return null;
  }
};

const ecrireVus = (ids) => {
  try {
    localStorage.setItem(CLE_VUS, JSON.stringify(ids));
  } catch {
    /* voir lireVus() */
  }
};

export const TropheesProvider = ({ children }) => {
  const { user } = useAuth();
  const [badges, setBadges] = useState([]);
  const [vus, setVus] = useState(() => lireVus() ?? []);
  const amorce = useRef(lireVus() !== null);

  const rafraichir = useCallback(async () => {
    if (!user) return;
    try {
      const reponse = await getGamificationSummary();
      const liste = reponse.badges || [];
      setBadges(liste);

      /**
       * Premier amorçage sur ce navigateur : tout ce qui est déjà acquis est
       * marqué comme vu. Sans ça, la mise en service de cette fonctionnalité —
       * ou une simple connexion depuis une autre machine — déclencherait une
       * révélation pour l'intégralité de la collection, ce qui transformerait
       * une fanfare en corvée. La lueur ne doit signaler que du nouveau.
       */
      if (!amorce.current) {
        const acquis = liste.filter((b) => b.earned).map((b) => b.id);
        amorce.current = true;
        setVus(acquis);
        ecrireVus(acquis);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des trophées:', error);
    }
  }, [user]);

  useEffect(() => {
    if (user) {
      rafraichir();
    } else {
      setBadges([]);
    }
  }, [user, rafraichir]);

  const nonVus = useMemo(
    () => badges.filter((b) => b.earned && !vus.includes(b.id)),
    [badges, vus]
  );

  const marquerVus = useCallback((ids) => {
    setVus((precedents) => {
      const fusion = Array.from(new Set([...precedents, ...ids]));
      ecrireVus(fusion);
      return fusion;
    });
  }, []);

  /**
   * Intensité de la lueur, et non le nombre brut : l'en-tête n'a pas à compter,
   * il a à alerter. Trois paliers suffisent à faire sentir « un titre » contre
   * « plusieurs » contre « beaucoup », là où une échelle continue produirait des
   * variations que personne ne perçoit.
   */
  const intensite = useMemo(() => {
    if (nonVus.length === 0) return 0;
    if (nonVus.length === 1) return 1;
    if (nonVus.length <= 3) return 2;
    return 3;
  }, [nonVus.length]);

  const valeur = useMemo(
    () => ({ badges, nonVus, intensite, rafraichir, marquerVus }),
    [badges, nonVus, intensite, rafraichir, marquerVus]
  );

  return <TropheesContext.Provider value={valeur}>{children}</TropheesContext.Provider>;
};

export const useTrophees = () => {
  const contexte = useContext(TropheesContext);
  if (!contexte) {
    throw new Error('useTrophees doit être utilisé dans un TropheesProvider');
  }
  return contexte;
};
