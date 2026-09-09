import { http } from '../../core/api/http.js';
import { ENDPOINTS } from '../../core/constants/api.js';

// Opsi untuk endpoint auth — 401 tidak redirect, agar error message dari
// backend (kredensial salah, kode OTP tidak valid, dll) bisa tampil di UI.
const authOpts = { skipAuthRedirect: true };

export const authApi = {
  register: (data)       => http.post(ENDPOINTS.register,  data, authOpts),
  login:    (data)       => http.post(ENDPOINTS.login,     data, authOpts),
  verify2fa:(data)       => http.post(ENDPOINTS.verify2fa, data, authOpts),
  logout:   ()           => http.post(ENDPOINTS.logout,    {}),
  me:       ()           => http.get(ENDPOINTS.me),
};
