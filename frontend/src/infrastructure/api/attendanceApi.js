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

export const attendanceApi = {
  list:   (params = {}) => http.get(`${ENDPOINTS.attendance}${buildQuery(params)}`),
  save:   (data)        => http.post(ENDPOINTS.attendance, data),
  update: (id, data)    => http.put(ENDPOINTS.attendanceById(id), data),
  recap:  (params = {}) => http.get(`${ENDPOINTS.attendanceRecap}${buildQuery(params)}`),
};
