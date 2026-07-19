import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';

// React Router ne réinitialise jamais le scroll lors d'une navigation
// client-side : sans ce composant, cliquer sur "module suivant" en bas de
// page laisse l'utilisateur en bas de la nouvelle page au lieu de l'ouvrir
// depuis le haut.
const ScrollToTop = () => {
  const { pathname } = useLocation();

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [pathname]);

  return null;
};

export default ScrollToTop;
