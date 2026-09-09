import { http } from '../../core/api/http.js';
import { ENDPOINTS } from '../../core/constants/api.js';

export const masterApi = {
  departments: () => http.get(ENDPOINTS.departments),
  courses:     () => http.get(ENDPOINTS.courses),
  classes:     () => http.get(ENDPOINTS.classes),
  semesters:   () => http.get(ENDPOINTS.semesters),
};
