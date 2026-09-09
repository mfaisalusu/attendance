import { http } from '../../core/api/http.js';
import { ENDPOINTS } from '../../core/constants/api.js';

export const dashboardApi = {
  stats: () => http.get(ENDPOINTS.dashboard),
};
