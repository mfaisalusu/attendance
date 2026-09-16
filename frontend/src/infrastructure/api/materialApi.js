import { http } from '../../core/api/http.js';
import { ENDPOINTS } from '../../core/constants/api.js';
import { getToken } from '../../core/auth/authStore.js';

function buildQuery(params) {
  const q = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== null && v !== undefined && v !== '') q.set(k, v);
  });
  const str = q.toString();
  return str ? `?${str}` : '';
}

export const materialApi = {
  list: (params = {}) => http.get(`${ENDPOINTS.materials}${buildQuery(params)}`),
  
  create: (formData) => http.post(ENDPOINTS.materials, formData),
  
  delete: (id) => http.delete(ENDPOINTS.material(id)),
  
  download: (id) => {
    const token = getToken();
    return `${ENDPOINTS.materialDownload(id)}?token=${token}`;
  },
  
  view: (id) => {
    const token = getToken();
    return `${ENDPOINTS.materialView(id)}?token=${token}`;
  },

  content: (id) => {
    const token = getToken();
    return `${ENDPOINTS.materialContent(id)}?token=${token}`;
  },
};