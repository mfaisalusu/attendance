import { masterApi } from '../../infrastructure/api/masterApi.js';

// Simple in-memory cache for the session
const cache = {};

async function fetchMaster(key, apiFn) {
  if (cache[key]) return cache[key];
  const res = await apiFn();
  if (res?.ok) {
    cache[key] = res.data.data ?? [];
  }
  return cache[key] ?? [];
}

export const masterService = {
  getDepartments: () => fetchMaster('departments', masterApi.departments),
  getCourses:     () => fetchMaster('courses',     masterApi.courses),
  getClasses:     () => fetchMaster('classes',     masterApi.classes),
  getSemesters:   () => fetchMaster('semesters',   masterApi.semesters),
};
