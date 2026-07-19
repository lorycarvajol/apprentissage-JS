import { Link } from 'react-router-dom';

const Footer = () => {
  return (
    <footer className="main-footer">
      <div className="footer-container">
        <span className="footer-copyright">
          © {new Date().getFullYear()} Plateforme d'apprentissage JavaScript
        </span>
        <nav className="footer-links">
          <Link to="/mentions-legales">Mentions légales</Link>
          <Link to="/cgu">CGU</Link>
          <Link to="/politique-confidentialite">Politique de confidentialité</Link>
        </nav>
      </div>
    </footer>
  );
};

export default Footer;
