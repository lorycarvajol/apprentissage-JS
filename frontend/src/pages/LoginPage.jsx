import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import PasswordInput from '../components/common/PasswordInput';
import '../styles/Auth.css';

const LoginPage = () => {
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [rememberMe, setRememberMe] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const [remainingAttempts, setRemainingAttempts] = useState(null);
  const [emailNotVerified, setEmailNotVerified] = useState(false);
  const [resendMessage, setResendMessage] = useState('');

  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setRemainingAttempts(null);
    setEmailNotVerified(false);
    setResendMessage('');
    setIsLoading(true);

    const result = await login(identifier, password, rememberMe);

    if (result.success) {
      navigate('/dashboard');
    } else {
      setError(result.error);
      setRemainingAttempts(result.remainingAttempts);
      setEmailNotVerified(result.emailNotVerified);
    }

    setIsLoading(false);
  };

  const handleResendVerification = async () => {
    try {
      const response = await api.post('/auth/resend-verification', { email: identifier });
      setResendMessage(response.data.message);
    } catch {
      setResendMessage('Une erreur est survenue');
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="auth-header">
          <h1>Connexion</h1>
          <p>Connectez-vous pour accéder à vos cours</p>
        </div>

        {error && (
          <div className="alert alert-error">
            {error}
            {typeof remainingAttempts === 'number' && (
              <div style={{ marginTop: '6px', fontSize: '13px' }}>
                Tentatives restantes avant blocage : {remainingAttempts}
              </div>
            )}
            {emailNotVerified && (
              <div style={{ marginTop: '6px', fontSize: '13px' }}>
                <button
                  type="button"
                  onClick={handleResendVerification}
                  className="forgot-link"
                  style={{ background: 'none', border: 'none', padding: 0, cursor: 'pointer', textDecoration: 'underline' }}
                >
                  Renvoyer l'email de vérification
                </button>
              </div>
            )}
          </div>
        )}

        {resendMessage && (
          <div className="alert alert-success">
            {resendMessage}
          </div>
        )}

        <form onSubmit={handleSubmit} className="auth-form">
          <div className="form-group">
            <label htmlFor="identifier">Email ou nom d'utilisateur</label>
            <input
              type="text"
              id="identifier"
              value={identifier}
              onChange={(e) => setIdentifier(e.target.value)}
              placeholder="Entrez votre email ou nom d'utilisateur"
              required
              disabled={isLoading}
            />
          </div>

          <div className="form-group">
            <label htmlFor="password">Mot de passe</label>
            <PasswordInput
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Entrez votre mot de passe"
              required
              disabled={isLoading}
            />
          </div>

          <div className="form-options">
            <label className="checkbox-label">
              <input
                type="checkbox"
                checked={rememberMe}
                onChange={(e) => setRememberMe(e.target.checked)}
                disabled={isLoading}
              />
              <span>Se souvenir de moi</span>
            </label>
            <Link to="/forgot-password" className="forgot-link">
              Mot de passe oublié ?
            </Link>
          </div>

          <button
            type="submit"
            className="btn btn-primary btn-block"
            disabled={isLoading}
          >
            {isLoading ? 'Connexion...' : 'Se connecter'}
          </button>
        </form>

        <div className="auth-footer">
          <p>
            Pas encore de compte ?{' '}
            <Link to="/register">S'inscrire</Link>
          </p>
        </div>
      </div>
    </div>
  );
};

export default LoginPage;
