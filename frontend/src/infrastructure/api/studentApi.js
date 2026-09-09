import { http } from '../../core/api/http.js';
import { ENDPOINTS } from '../../core/constants/api.js';

function buildQuery(params) {
  const q = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== null && v !== undefined && v !== '') q.set(k, v);
  });
  const str = q.toString();
  return str ? `?${str}` : '';
}

export const studentApi = {
  list:   (params = {}) => http.get(`${ENDPOINTS.students}${buildQuery(params)}`),
  get:    (id)          => http.get(ENDPOINTS.student(id)),
  create: (data)        => http.post(ENDPOINTS.students, data),
  update: (id, data)    => http.put(ENDPOINTS.student(id), data),
  delete: (id)          => http.delete(ENDPOINTS.student(id)),
};
