import { createContext, useContext, useState, useEffect } from 'react';
import api, { setAccessToken } from '../services/api';

const AuthContext = createContext(null);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Au chargement, on tente un refresh silencieux : s'il existe un cookie de
  // refresh token httpOnly encore valide, on récupère un nouvel access token
  // sans redemander les identifiants.
  useEffect(() => {
    const bootstrapAuth = async () => {
      try {
        const response = await api.post('/auth/refresh');
        setAccessToken(response.data.token);
        setUser(response.data.user);
      } catch {
        setAccessToken(null);
      } finally {
        setLoading(false);
      }
    };

    bootstrapAuth();
  }, []);

  const login = async (identifier, password, rememberMe = false) => {
    setError(null);

    try {
      const response = await api.post('/auth/login', {
        identifier,
        password,
        remember_me: rememberMe,
      });

      const { user, token } = response.data;

      setAccessToken(token);
      setUser(user);

      return { success: true };
    } catch (err) {
      const errorMessage = err.response?.data?.error || 'Erreur de connexion';
      return {
        success: false,
        error: errorMessage,
        remainingAttempts: err.response?.data?.remaining_attempts ?? null,
        emailNotVerified: err.response?.data?.email_not_verified === true,
      };
    }
  };

  const register = async (userData) => {
    setError(null);

    try {
      const response = await api.post('/auth/register', userData);

      return {
        success: true,
        message: response.data.message,
        verificationUrl: response.data.verification_url ?? null,
      };
    } catch (err) {
      const errors = err.response?.data?.errors || { general: 'Erreur lors de l\'inscription' };
      setError(errors);
      return { success: false, errors };
    }
  };

  const logout = async () => {
    try {
      await api.post('/auth/logout');
    } catch {
      // On nettoie l'état client même si la révocation serveur échoue
    }
    setAccessToken(null);
    setUser(null);
    setError(null);
  };

  const value = {
    user,
    loading,
    error,
    login,
    register,
    logout,
    isAuthenticated: !!user
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};
