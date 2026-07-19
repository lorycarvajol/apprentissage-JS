import api from './api';

// ===== MODULES =====
export const createModule = async (data) => {
  const response = await api.post('/modules', data);
  return response.data;
};

export const updateModule = async (id, data) => {
  const response = await api.put(`/modules/${id}`, data);
  return response.data;
};

export const deleteModule = async (id) => {
  const response = await api.delete(`/modules/${id}`);
  return response.data;
};

// ===== CHAPITRES =====
export const createChapitre = async (data) => {
  const response = await api.post('/chapitres', data);
  return response.data;
};

export const updateChapitre = async (id, data) => {
  const response = await api.put(`/chapitres/${id}`, data);
  return response.data;
};

export const deleteChapitre = async (id) => {
  const response = await api.delete(`/chapitres/${id}`);
  return response.data;
};

// ===== THEORIES =====
export const createTheorie = async (data) => {
  const response = await api.post('/theories', data);
  return response.data;
};

export const updateTheorie = async (id, data) => {
  const response = await api.put(`/theories/${id}`, data);
  return response.data;
};

export const deleteTheorie = async (id) => {
  const response = await api.delete(`/theories/${id}`);
  return response.data;
};

// ===== EXERCICES =====
export const createExercice = async (data) => {
  const response = await api.post('/exercices', data);
  return response.data;
};

export const updateExercice = async (id, data) => {
  const response = await api.put(`/exercices/${id}`, data);
  return response.data;
};

export const deleteExercice = async (id) => {
  const response = await api.delete(`/exercices/${id}`);
  return response.data;
};
