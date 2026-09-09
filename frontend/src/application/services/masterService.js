import { masterApi } from '../../infrastructure/api/masterApi.js';

// Simple in-memory cache for the session (read-only data)
const cache = {};

function invalidate(key) {
  delete cache[key];
}

async function fetchMaster(key, apiFn) {
  if (cache[key]) return cache[key];
  const res = await apiFn();
  if (res?.ok) {
    cache[key] = res.data.data ?? [];
  }
  return cache[key] ?? [];
}

export const masterService = {
  // ------------------------------------------------------------------
  // Read (cached)
  // ------------------------------------------------------------------
  getYears:       () => fetchMaster('years',       masterApi.years),
  getDepartments: () => fetchMaster('departments', masterApi.departments),
  getCourses:     () => fetchMaster('courses',     masterApi.courses),
  getClasses:     () => fetchMaster('classes',     masterApi.classes),
  getSemesters:   () => fetchMaster('semesters',   masterApi.semesters),

  // ------------------------------------------------------------------
  // Departments CRUD
  // ------------------------------------------------------------------
  async createDepartment(data) {
    const res = await masterApi.createDepartment(data);
    if (res?.ok) invalidate('departments');
    return res;
  },
  async updateDepartment(id, data) {
    const res = await masterApi.updateDepartment(id, data);
    if (res?.ok) invalidate('departments');
    return res;
  },
  async deleteDepartment(id) {
    const res = await masterApi.deleteDepartment(id);
    if (res?.ok) invalidate('departments');
    return res;
  },

  // ------------------------------------------------------------------
  // Courses CRUD
  // ------------------------------------------------------------------
  async createCourse(data) {
    const res = await masterApi.createCourse(data);
    if (res?.ok) invalidate('courses');
    return res;
  },
  async updateCourse(id, data) {
    const res = await masterApi.updateCourse(id, data);
    if (res?.ok) invalidate('courses');
    return res;
  },
  async deleteCourse(id) {
    const res = await masterApi.deleteCourse(id);
    if (res?.ok) invalidate('courses');
    return res;
  },

  // ------------------------------------------------------------------
  // Classes CRUD
  // ------------------------------------------------------------------
  async createClass(data) {
    const res = await masterApi.createClass(data);
    if (res?.ok) invalidate('classes');
    return res;
  },
  async updateClass(id, data) {
    const res = await masterApi.updateClass(id, data);
    if (res?.ok) invalidate('classes');
    return res;
  },
  async deleteClass(id) {
    const res = await masterApi.deleteClass(id);
    if (res?.ok) invalidate('classes');
    return res;
  },
};
