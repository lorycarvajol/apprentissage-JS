import { useState, useEffect } from 'react';
import { useNavigate, useSearchParams, Link } from 'react-router-dom';
import api from '../services/api';
import PasswordInput from '../components/common/PasswordInput';
import '../styles/Auth.css';

const ResetPasswordPage = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const token = searchParams.get('token');

  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [passwordStrength, setPasswordStrength] = useState(0);
  const [strengthMessage, setStrengthMessage] = useState('');

  useEffect(() => {
    if (!token) {
      navigate('/forgot-password');
    }
  }, [token, navigate]);

  // Vérifier la force du mot de passe en temps réel
  useEffect(() => {
    const checkPasswordStrength = async () => {
      if (password.length > 0) {
        try {
          const response = await api.post('/auth/check-password-strength', { password });
          setPasswordStrength(response.data.strength);
          setStrengthMessage(response.data.message);
        } catch (err) {
          console.error('Error checking password strength:', err);
        }
      } else {
        setPasswordStrength(0);
        setStrengthMessage('');
      }
    };

    const debounce = setTimeout(() => {
      checkPasswordStrength();
    }, 300);

    return () => clearTimeout(debounce);
  }, [password]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setIsLoading(true);

    if (password !== passwordConfirmation) {
      setError('Les mots de passe ne correspondent pas');
      setIsLoading(false);
      return;
    }

    try {
      const response = await api.post('/auth/reset-password', {
        token,
        password
      });

      setSuccess(true);
      setTimeout(() => {
        navigate('/login');
      }, 2000);
    } catch (err) {
      setError(err.response?.data?.error || err.response?.data?.errors?.password || 'Une erreur est survenue');
    } finally {
      setIsLoading(false);
    }
  };

  const getStrengthColor = () => {
    if (passwordStrength < 40) return '#e74c3c';
    if (passwordStrength < 70) return '#f39c12';
    return '#27ae60';
  };

  const getStrengthLabel = () => {
    if (passwordStrength < 40) return 'Faible';
    if (passwordStrength < 70) return 'Moyen';
    return 'Fort';
  };

  if (success) {
    return (
      <div className="auth-container">
        <div className="auth-card">
          <div className="auth-header">
            <h1>✓ Mot de passe réinitialisé</h1>
            <p>Votre mot de passe a été réinitialisé avec succès</p>
          </div>
          <div className="alert alert-success">
            Redirection vers la page de connexion...
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="auth-header">
          <h1>Réinitialiser le mot de passe</h1>
          <p>Choisissez un nouveau mot de passe sécurisé</p>
        </div>

        {error && (
          <div className="alert alert-error">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="auth-form">
          <div className="form-group">
            <label htmlFor="password">Nouveau mot de passe</label>
            <PasswordInput
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Minimum 8 caractères, majuscules, chiffres"
              required
              disabled={isLoading}
            />
            {password && (
              <div className="password-strength">
                <div className="strength-bar">
                  <div
                    className="strength-fill"
                    style={{
                      width: `${passwordStrength}%`,
                      backgroundColor: getStrengthColor()
                    }}
                  ></div>
                </div>
                <span className="strength-label" style={{ color: getStrengthColor() }}>
                  {getStrengthLabel()} - {strengthMessage}
                </span>
              </div>
            )}
          </div>

          <div className="form-group">
            <label htmlFor="passwordConfirmation">Confirmer le mot de passe</label>
            <PasswordInput
              id="passwordConfirmation"
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              placeholder="Confirmez votre mot de passe"
              required
              disabled={isLoading}
            />
          </div>

          <button
            type="submit"
            className="btn btn-primary btn-block"
            disabled={isLoading || passwordStrength < 40}
          >
            {isLoading ? 'Réinitialisation...' : 'Réinitialiser le mot de passe'}
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

export default ResetPasswordPage;
