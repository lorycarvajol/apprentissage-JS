import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import PasswordInput from '../components/common/PasswordInput';
import '../styles/Auth.css';

const RegisterPage = () => {
  const [formData, setFormData] = useState({
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    first_name: '',
    last_name: ''
  });
  const [isLoading, setIsLoading] = useState(false);
  const [errors, setErrors] = useState({});
  const [passwordStrength, setPasswordStrength] = useState(0);
  const [strengthMessage, setStrengthMessage] = useState('');
  const [registered, setRegistered] = useState(null);
  const [consentAccepted, setConsentAccepted] = useState(false);

  const { register } = useAuth();

  // Vérifier la force du mot de passe en temps réel
  useEffect(() => {
    const checkPasswordStrength = async () => {
      if (formData.password.length > 0) {
        try {
          const response = await api.post('/auth/check-password-strength', {
            password: formData.password
          });
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
  }, [formData.password]);

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

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    // Effacer l'erreur du champ modifié
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setIsLoading(true);

    const result = await register(formData);

    if (result.success) {
      setRegistered(result);
    } else {
      setErrors(result.errors);
    }

    setIsLoading(false);
  };

  if (registered) {
    return (
      <div className="auth-container">
        <div className="auth-card">
          <div className="auth-header">
            <h1>Vérifiez votre email</h1>
            <p>{registered.message}</p>
          </div>

          <div className="alert alert-success">
            Un lien de vérification a été envoyé à votre adresse email. Cliquez dessus pour activer votre compte avant de vous connecter.
            {registered.verificationUrl && (
              <div style={{ marginTop: '10px', fontSize: '12px' }}>
                <strong>Mode développement:</strong>
                <br />
                <Link to={registered.verificationUrl.replace(/^.*\/verify-email/, '/verify-email')}>
                  Cliquez ici pour vérifier votre email
                </Link>
              </div>
            )}
          </div>

          <div className="auth-footer">
            <p>
              <Link to="/login">Retour à la connexion</Link>
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="auth-header">
          <h1>Inscription</h1>
          <p>Créez votre compte pour commencer à apprendre</p>
        </div>

        {errors.general && (
          <div className="alert alert-error">
            {errors.general}
          </div>
        )}

        <form onSubmit={handleSubmit} className="auth-form">
          <div className="form-row">
            <div className="form-group">
              <label htmlFor="first_name">Prénom</label>
              <input
                type="text"
                id="first_name"
                name="first_name"
                value={formData.first_name}
                onChange={handleChange}
                placeholder="Votre prénom"
                disabled={isLoading}
              />
            </div>

            <div className="form-group">
              <label htmlFor="last_name">Nom</label>
              <input
                type="text"
                id="last_name"
                name="last_name"
                value={formData.last_name}
                onChange={handleChange}
                placeholder="Votre nom"
                disabled={isLoading}
              />
            </div>
          </div>

          <div className="form-group">
            <label htmlFor="username">Nom d'utilisateur *</label>
            <input
              type="text"
              id="username"
              name="username"
              value={formData.username}
              onChange={handleChange}
              placeholder="Choisissez un nom d'utilisateur"
              required
              disabled={isLoading}
              className={errors.username ? 'error' : ''}
            />
            {errors.username && (
              <span className="error-message">{errors.username}</span>
            )}
          </div>

          <div className="form-group">
            <label htmlFor="email">Email *</label>
            <input
              type="email"
              id="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              placeholder="votre@email.com"
              required
              disabled={isLoading}
              className={errors.email ? 'error' : ''}
            />
            {errors.email && (
              <span className="error-message">{errors.email}</span>
            )}
          </div>

          <div className="form-group">
            <label htmlFor="password">Mot de passe *</label>
            <PasswordInput
              id="password"
              name="password"
              value={formData.password}
              onChange={handleChange}
              placeholder="Minimum 8 caractères, majuscules, chiffres"
              required
              disabled={isLoading}
              className={errors.password ? 'error' : ''}
            />
            {formData.password && (
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
            {errors.password && (
              <span className="error-message">{errors.password}</span>
            )}
          </div>

          <div className="form-group">
            <label htmlFor="password_confirmation">Confirmer le mot de passe *</label>
            <PasswordInput
              id="password_confirmation"
              name="password_confirmation"
              value={formData.password_confirmation}
              onChange={handleChange}
              placeholder="Confirmez votre mot de passe"
              required
              disabled={isLoading}
              className={errors.password_confirmation ? 'error' : ''}
            />
            {errors.password_confirmation && (
              <span className="error-message">{errors.password_confirmation}</span>
            )}
          </div>

          <div className="form-group">
            <label className="checkbox-label">
              <input
                type="checkbox"
                checked={consentAccepted}
                onChange={(e) => setConsentAccepted(e.target.checked)}
                disabled={isLoading}
                required
              />
              <span>
                J'accepte les <Link to="/cgu" target="_blank">CGU</Link> et la{' '}
                <Link to="/politique-confidentialite" target="_blank">politique de confidentialité</Link>
              </span>
            </label>
          </div>

          <button
            type="submit"
            className="btn btn-primary btn-block"
            disabled={isLoading || !consentAccepted}
          >
            {isLoading ? 'Inscription...' : 'S\'inscrire'}
          </button>
        </form>

        <div className="auth-footer">
          <p>
            Déjà un compte ?{' '}
            <Link to="/login">Se connecter</Link>
          </p>
        </div>
      </div>
    </div>
  );
};

export default RegisterPage;
