import { useState, useEffect } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import api from '../services/api';
import '../styles/Auth.css';

const VerifyEmailPage = () => {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');

  const [status, setStatus] = useState('loading'); // loading | success | error
  const [message, setMessage] = useState('');

  useEffect(() => {
    if (!token) {
      setStatus('error');
      setMessage('Lien de vérification invalide');
      return;
    }

    api.post('/auth/verify-email', { token })
      .then((response) => {
        setStatus('success');
        setMessage(response.data.message);
      })
      .catch((err) => {
        setStatus('error');
        setMessage(err.response?.data?.error || 'Une erreur est survenue');
      });
  }, [token]);

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="auth-header">
          <h1>Vérification de l'email</h1>
        </div>

        {status === 'loading' && <p>Vérification en cours...</p>}

        {status === 'success' && (
          <div className="alert alert-success">{message}</div>
        )}

        {status === 'error' && (
          <div className="alert alert-error">{message}</div>
        )}

        <div className="auth-footer">
          <p>
            <Link to="/login">Retour à la connexion</Link>
          </p>
        </div>
      </div>
    </div>
  );
};

export default VerifyEmailPage;
