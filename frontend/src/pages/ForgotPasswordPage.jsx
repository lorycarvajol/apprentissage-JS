import { useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import '../styles/Auth.css';

const ForgotPasswordPage = () => {
  const [email, setEmail] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [resetToken, setResetToken] = useState(''); // Pour debug en mode dev

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setMessage('');
    setIsLoading(true);

    try {
      const response = await api.post('/auth/forgot-password', { email });

      setMessage(response.data.message);

      // En mode développement, afficher le token
      if (response.data.token) {
        setResetToken(response.data.token);
      }
    } catch (err) {
      setError(err.response?.data?.error || 'Une erreur est survenue');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="auth-header">
          <h1>Mot de passe oublié</h1>
          <p>Entrez votre email pour recevoir un lien de réinitialisation</p>
        </div>

        {message && (
          <div className="alert alert-success">
            {message}
            {resetToken && (
              <div style={{ marginTop: '10px', fontSize: '12px' }}>
                <strong>Mode développement:</strong>
                <br />
                <Link to={`/reset-password?token=${resetToken}`}>
                  Cliquez ici pour réinitialiser votre mot de passe
                </Link>
              </div>
            )}
          </div>
        )}

        {error && (
          <div className="alert alert-error">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="auth-form">
          <div className="form-group">
            <label htmlFor="email">Adresse email</label>
            <input
              type="email"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="votre@email.com"
              required
              disabled={isLoading}
            />
          </div>

          <button
            type="submit"
            className="btn btn-primary btn-block"
            disabled={isLoading}
          >
            {isLoading ? 'Envoi...' : 'Envoyer le lien'}
          </button>
        </form>

        <div className="auth-footer">
          <p>
            <Link to="/login">Retour à la connexion</Link>
          </p>
        </div>
      </div>
    </div>
  );
};

export default ForgotPasswordPage;
