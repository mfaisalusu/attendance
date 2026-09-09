import { http } from '../../core/api/http.js';
import { ENDPOINTS } from '../../core/constants/api.js';

export const masterApi = {
  // Read
  years:       () => http.get(ENDPOINTS.years),
  departments: () => http.get(ENDPOINTS.departments),
  courses:     () => http.get(ENDPOINTS.courses),
  classes:     () => http.get(ENDPOINTS.classes),
  semesters:   () => http.get(ENDPOINTS.semesters),

  // Departments CRUD
  createDepartment: (data)     => http.post(ENDPOINTS.departmentStore,       data),
  updateDepartment: (id, data) => http.put(ENDPOINTS.departmentUpdate(id),   data),
  deleteDepartment: (id)       => http.delete(ENDPOINTS.departmentDestroy(id)),

  // Courses CRUD
  createCourse: (data)     => http.post(ENDPOINTS.courseStore,       data),
  updateCourse: (id, data) => http.put(ENDPOINTS.courseUpdate(id),   data),
  deleteCourse: (id)       => http.delete(ENDPOINTS.courseDestroy(id)),

  // Classes CRUD
  createClass: (data)     => http.post(ENDPOINTS.classStore,       data),
  updateClass: (id, data) => http.put(ENDPOINTS.classUpdate(id),   data),
  deleteClass: (id)       => http.delete(ENDPOINTS.classDestroy(id)),
};
