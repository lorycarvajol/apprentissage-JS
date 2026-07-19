import api from './api';

/**
 * Service pour gérer les appels API liés au contenu pédagogique
 * (modules, chapitres, théories, exercices)
 */

// ================================================================
// MODULES
// ================================================================

/**
 * Récupérer tous les modules
 * @param {boolean} publishedOnly - Récupérer uniquement les modules publiés
 * @returns {Promise}
 */
export const getModules = async (publishedOnly = true) => {
  const response = await api.get(`/modules?published=${publishedOnly}`);
  return response.data;
};

/**
 * Récupérer un module par son ID
 * @param {number} id - ID du module
 * @returns {Promise}
 */
export const getModule = async (id) => {
  const response = await api.get(`/modules/${id}`);
  return response.data;
};

/**
 * Récupérer un module avec ses chapitres
 * @param {number} id - ID du module
 * @returns {Promise}
 */
export const getModuleWithChapitres = async (id) => {
  const response = await api.get(`/modules/${id}/chapitres`);
  return response.data;
};

/**
 * Créer un nouveau module (admin only)
 * @param {object} moduleData - Données du module
 * @returns {Promise}
 */
export const createModule = async (moduleData) => {
  const response = await api.post('/modules', moduleData);
  return response.data;
};

/**
 * Mettre à jour un module (admin only)
 * @param {number} id - ID du module
 * @param {object} moduleData - Données à mettre à jour
 * @returns {Promise}
 */
export const updateModule = async (id, moduleData) => {
  const response = await api.put(`/modules/${id}`, moduleData);
  return response.data;
};

/**
 * Supprimer un module (admin only)
 * @param {number} id - ID du module
 * @returns {Promise}
 */
export const deleteModule = async (id) => {
  const response = await api.delete(`/modules/${id}`);
  return response.data;
};

// ================================================================
// CHAPITRES
// ================================================================

/**
 * Récupérer tous les chapitres
 * @param {boolean} publishedOnly - Récupérer uniquement les chapitres publiés
 * @returns {Promise}
 */
export const getChapitres = async (publishedOnly = true) => {
  const response = await api.get(`/chapitres?published=${publishedOnly}`);
  return response.data;
};

/**
 * Récupérer un chapitre par son ID
 * @param {number} id - ID du chapitre
 * @returns {Promise}
 */
export const getChapitre = async (id) => {
  const response = await api.get(`/chapitres/${id}`);
  return response.data;
};

/**
 * Récupérer un chapitre avec ses théories
 * @param {number} id - ID du chapitre
 * @returns {Promise}
 */
export const getChapitreWithTheories = async (id) => {
  const response = await api.get(`/chapitres/${id}/theories`);
  return response.data;
};

/**
 * Récupérer un chapitre avec ses exercices
 * @param {number} id - ID du chapitre
 * @returns {Promise}
 */
export const getChapitreWithExercices = async (id) => {
  const response = await api.get(`/chapitres/${id}/exercices`);
  return response.data;
};

/**
 * Récupérer un chapitre avec tout son contenu (théories + exercices)
 * @param {number} id - ID du chapitre
 * @returns {Promise}
 */
export const getChapitreWithContent = async (id) => {
  const response = await api.get(`/chapitres/${id}/content`);
  return response.data;
};

/**
 * Créer un nouveau chapitre (admin only)
 * @param {object} chapitreData - Données du chapitre
 * @returns {Promise}
 */
export const createChapitre = async (chapitreData) => {
  const response = await api.post('/chapitres', chapitreData);
  return response.data;
};

/**
 * Mettre à jour un chapitre (admin only)
 * @param {number} id - ID du chapitre
 * @param {object} chapitreData - Données à mettre à jour
 * @returns {Promise}
 */
export const updateChapitre = async (id, chapitreData) => {
  const response = await api.put(`/chapitres/${id}`, chapitreData);
  return response.data;
};

/**
 * Supprimer un chapitre (admin only)
 * @param {number} id - ID du chapitre
 * @returns {Promise}
 */
export const deleteChapitre = async (id) => {
  const response = await api.delete(`/chapitres/${id}`);
  return response.data;
};

// ================================================================
// THEORIES
// ================================================================

/**
 * Récupérer toutes les théories
 * @returns {Promise}
 */
export const getTheories = async () => {
  const response = await api.get('/theories');
  return response.data;
};

/**
 * Récupérer une théorie par son ID
 * @param {number} id - ID de la théorie
 * @returns {Promise}
 */
export const getTheorie = async (id) => {
  const response = await api.get(`/theories/${id}`);
  return response.data;
};

/**
 * Créer une nouvelle théorie (admin only)
 * @param {object} theorieData - Données de la théorie
 * @returns {Promise}
 */
export const createTheorie = async (theorieData) => {
  const response = await api.post('/theories', theorieData);
  return response.data;
};

/**
 * Mettre à jour une théorie (admin only)
 * @param {number} id - ID de la théorie
 * @param {object} theorieData - Données à mettre à jour
 * @returns {Promise}
 */
export const updateTheorie = async (id, theorieData) => {
  const response = await api.put(`/theories/${id}`, theorieData);
  return response.data;
};

/**
 * Supprimer une théorie (admin only)
 * @param {number} id - ID de la théorie
 * @returns {Promise}
 */
export const deleteTheorie = async (id) => {
  const response = await api.delete(`/theories/${id}`);
  return response.data;
};

/**
 * Marquer une théorie comme lue par l'utilisateur courant
 * @param {number} id - ID de la théorie
 * @param {number} timeSpent - Temps passé en secondes
 * @returns {Promise}
 */
export const markTheorieAsRead = async (id, timeSpent = 0) => {
  const response = await api.post(`/theories/${id}/read`, { time_spent: timeSpent });
  return response.data;
};

// ================================================================
// EXERCICES
// ================================================================

/**
 * Récupérer tous les exercices
 * @returns {Promise}
 */
export const getExercices = async () => {
  const response = await api.get('/exercices');
  return response.data;
};

/**
 * Récupérer un exercice par son ID
 * @param {number} id - ID de l'exercice
 * @returns {Promise}
 */
export const getExercice = async (id) => {
  const response = await api.get(`/exercices/${id}`);
  return response.data;
};

/**
 * Créer un nouvel exercice (admin only)
 * @param {object} exerciceData - Données de l'exercice
 * @returns {Promise}
 */
export const createExercice = async (exerciceData) => {
  const response = await api.post('/exercices', exerciceData);
  return response.data;
};

/**
 * Mettre à jour un exercice (admin only)
 * @param {number} id - ID de l'exercice
 * @param {object} exerciceData - Données à mettre à jour
 * @returns {Promise}
 */
export const updateExercice = async (id, exerciceData) => {
  const response = await api.put(`/exercices/${id}`, exerciceData);
  return response.data;
};

/**
 * Supprimer un exercice (admin only)
 * @param {number} id - ID de l'exercice
 * @returns {Promise}
 */
export const deleteExercice = async (id) => {
  const response = await api.delete(`/exercices/${id}`);
  return response.data;
};

export default {
  // Modules
  getModules,
  getModule,
  getModuleWithChapitres,
  createModule,
  updateModule,
  deleteModule,

  // Chapitres
  getChapitres,
  getChapitre,
  getChapitreWithTheories,
  getChapitreWithExercices,
  getChapitreWithContent,
  createChapitre,
  updateChapitre,
  deleteChapitre,

  // Theories
  getTheories,
  getTheorie,
  createTheorie,
  updateTheorie,
  deleteTheorie,
  markTheorieAsRead,

  // Exercices
  getExercices,
  getExercice,
  createExercice,
  updateExercice,
  deleteExercice
};
