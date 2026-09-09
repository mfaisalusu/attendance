import { http } from '../../core/api/http.js';
import { ENDPOINTS } from '../../core/constants/api.js';

export const authApi = {
  register: (data)       => http.post(ENDPOINTS.register,  data),
  login:    (data)       => http.post(ENDPOINTS.login,     data),
  verify2fa:(data)       => http.post(ENDPOINTS.verify2fa, data),
  logout:   ()           => http.post(ENDPOINTS.logout,    {}),
  me:       ()           => http.get(ENDPOINTS.me),
};
