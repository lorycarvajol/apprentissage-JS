import axios from 'axios';

// L'access token JWT est gardé en mémoire (jamais en localStorage/sessionStorage)
// pour limiter son exposition en cas de faille XSS. Le refresh token, lui, vit
// uniquement dans un cookie httpOnly posé par le backend (inaccessible en JS).
let accessToken = null;

export const setAccessToken = (token) => {
  accessToken = token;
};

export const getAccessToken = () => accessToken;

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
  // Nécessaire pour que le cookie httpOnly du refresh token soit envoyé/reçu
  withCredentials: true,
});

api.interceptors.request.use(
  (config) => {
    if (accessToken) {
      config.headers.Authorization = `Bearer ${accessToken}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Une seule requête de refresh à la fois, même si plusieurs appels échouent en 401 en parallèle
let refreshPromise = null;

const requestRefresh = () => {
  if (!refreshPromise) {
    refreshPromise = api
      .post('/auth/refresh')
      .then((response) => {
        setAccessToken(response.data.token);
        return response.data.token;
      })
      .finally(() => {
        refreshPromise = null;
      });
  }
  return refreshPromise;
};

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const { config, response } = error;
    const isAuthEndpoint = config?.url?.startsWith('/auth/login')
      || config?.url?.startsWith('/auth/refresh')
      || config?.url?.startsWith('/auth/register');

    if (response?.status === 401 && !config._retry && !isAuthEndpoint) {
      config._retry = true;
      try {
        const newToken = await requestRefresh();
        config.headers.Authorization = `Bearer ${newToken}`;
        return api(config);
      } catch (refreshError) {
        setAccessToken(null);
        window.location.href = '/login';
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);

export default api;
