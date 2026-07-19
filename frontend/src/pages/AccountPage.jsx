import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import MainLayout from '../components/layout/MainLayout';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import '../styles/Account.css';

const AccountPage = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const [isExporting, setIsExporting] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [confirmText, setConfirmText] = useState('');
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [error, setError] = useState('');

  const handleExport = async () => {
    setIsExporting(true);
    setError('');

    try {
      const response = await api.get('/auth/me/export');
      const blob = new Blob([JSON.stringify(response.data, null, 2)], {
        type: 'application/json',
      });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `mes-donnees-${user.username}.json`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    } catch {
      setError("Une erreur est survenue lors de l'export de vos données.");
    } finally {
      setIsExporting(false);
    }
  };

  const handleDelete = async () => {
    if (confirmText !== user.username) {
      return;
    }

    setIsDeleting(true);
    setError('');

    try {
      await api.delete('/auth/me');
      await logout();
      navigate('/login');
    } catch {
      setError('Une erreur est survenue lors de la suppression de votre compte.');
      setIsDeleting(false);
    }
  };

  if (!user) {
    return null;
  }

  return (
    <MainLayout>
      <div className="account-page">
        <div className="account-container">
          <h1>Mon compte</h1>

          <section className="account-section">
            <h2>Informations</h2>
            <span className="account-properties-eyebrow">class Utilisateur</span>
            <div className="account-properties">
              <div className="account-property-row">
                <span className="account-property-visibility is-public">+</span>
                <span className="account-property-name">username</span>
                <span className="account-property-value">{user.username}</span>
              </div>
              <div className="account-property-row">
                <span className="account-property-visibility is-private">-</span>
                <span className="account-property-name">email</span>
                <span className="account-property-value">{user.email}</span>
              </div>
              <div className="account-property-row">
                <span className="account-property-visibility is-protected">#</span>
                <span className="account-property-name">role</span>
                <span className="account-property-value">
                  {user.role === 'admin' ? 'Administrateur' : 'Étudiant'}
                </span>
              </div>
            </div>
          </section>

          {error && <div className="alert alert-error">{error}</div>}

          <section className="account-section">
            <h2>Vos données (RGPD)</h2>
            <p className="account-section-description">
              Conformément au RGPD, vous pouvez télécharger l'ensemble de vos données
              personnelles ou supprimer définitivement votre compte.
            </p>

            <div className="account-action">
              <div>
                <h3>Télécharger mes données</h3>
                <p>Export complet au format JSON de vos informations, votre progression et vos badges.</p>
              </div>
              <button
                type="button"
                className="btn btn-secondary"
                onClick={handleExport}
                disabled={isExporting}
              >
                {isExporting ? 'Export...' : 'Télécharger'}
              </button>
            </div>

            <div className="account-action account-action-danger">
              <div>
                <h3>Supprimer mon compte</h3>
                <p>Action irréversible : votre compte, votre progression et vos badges seront définitivement supprimés.</p>
              </div>
              {!showDeleteConfirm ? (
                <button
                  type="button"
                  className="btn btn-danger"
                  onClick={() => setShowDeleteConfirm(true)}
                >
                  Supprimer mon compte
                </button>
              ) : null}
            </div>

            {showDeleteConfirm && (
              <div className="account-delete-confirm">
                <p>
                  Pour confirmer, tapez votre nom d'utilisateur (<strong>{user.username}</strong>) ci-dessous :
                </p>
                <input
                  type="text"
                  value={confirmText}
                  onChange={(e) => setConfirmText(e.target.value)}
                  placeholder={user.username}
                  disabled={isDeleting}
                />
                <div className="account-delete-confirm-actions">
                  <button
                    type="button"
                    className="btn btn-secondary"
                    onClick={() => {
                      setShowDeleteConfirm(false);
                      setConfirmText('');
                    }}
                    disabled={isDeleting}
                  >
                    Annuler
                  </button>
                  <button
                    type="button"
                    className="btn btn-danger"
                    onClick={handleDelete}
                    disabled={confirmText !== user.username || isDeleting}
                  >
                    {isDeleting ? 'Suppression...' : 'Confirmer la suppression'}
                  </button>
                </div>
              </div>
            )}
          </section>
        </div>
      </div>
    </MainLayout>
  );
};

export default AccountPage;
