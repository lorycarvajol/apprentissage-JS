import { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import ThemeToggle from '../common/ThemeToggle';
import '../../styles/Layout.css';

const Header = () => {
  const { user, logout } = useAuth();
  const location = useLocation();
  const navigate = useNavigate();
  const [menuOpen, setMenuOpen] = useState(false);

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const isActive = (path) => {
    return location.pathname === path || location.pathname.startsWith(path + '/');
  };

  return (
    <header className="main-header">
      <div className="header-container">
        {/* Logo / Brand */}
        <Link to="/dashboard" className="header-brand">
          <span className="brand-icon brand-icon-text" aria-hidden="true">JS</span>
          <span className="brand-text">Apprentissage JavaScript</span>
        </Link>

        {/* Navigation */}
        <nav className={`header-nav ${menuOpen ? 'nav-open' : ''}`}>
          <Link
            to="/dashboard"
            className={`nav-link ${isActive('/dashboard') ? 'nav-link-active' : ''}`}
            onClick={() => setMenuOpen(false)}
          >
            <span className="nav-icon">🏠</span>
            Accueil
          </Link>
          <Link
            to="/modules"
            className={`nav-link ${isActive('/modules') || isActive('/chapitres') ? 'nav-link-active' : ''}`}
            onClick={() => setMenuOpen(false)}
          >
            <span className="nav-icon">📖</span>
            Modules
          </Link>
          <Link
            to="/trophies"
            className={`nav-link ${isActive('/trophies') ? 'nav-link-active' : ''}`}
            onClick={() => setMenuOpen(false)}
          >
            <span className="nav-icon">🏆</span>
            Trophées
          </Link>
          {user?.role === 'admin' && (
            <Link
              to="/admin"
              className={`nav-link ${isActive('/admin') ? 'nav-link-active' : ''}`}
              onClick={() => setMenuOpen(false)}
            >
              <span className="nav-icon">🛠️</span>
              Admin
            </Link>
          )}
        </nav>

        {/* User Menu */}
        <div className="header-user">
          <ThemeToggle />
          {user && (
            <>
              <Link to="/compte" className="user-info" title="Mon compte">
                <span className="user-avatar">{user.username.charAt(0).toUpperCase()}</span>
                <span className="user-name">{user.username}</span>
              </Link>
              <button onClick={handleLogout} className="btn-logout">
                Déconnexion
              </button>
            </>
          )}
        </div>

        {/* Mobile menu toggle */}
        <button
          className="menu-toggle"
          onClick={() => setMenuOpen(!menuOpen)}
          aria-label="Toggle menu"
        >
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
    </header>
  );
};

export default Header;
