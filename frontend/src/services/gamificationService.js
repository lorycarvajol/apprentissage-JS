import api from './api';

/**
 * Service pour gérer les appels API liés à la gamification
 * (badges, streaks)
 */

/**
 * Récupérer le résumé de gamification de l'utilisateur courant
 * (badges obtenus/verrouillés + streak)
 * @returns {Promise}
 */
export const getGamificationSummary = async () => {
  const response = await api.get('/gamification/summary');
  return response.data;
};

export default {
  getGamificationSummary,
};
