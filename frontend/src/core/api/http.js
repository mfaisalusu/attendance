import { getToken, clearAuth } from '../auth/authStore.js';

/**
 * Central HTTP client.  Automatically attaches the Bearer token and
 * handles 401 by clearing auth state.
 *
 * Options:
 *   skipAuthRedirect: boolean — jika true, 401 tidak redirect ke /login
 *                               melainkan mengembalikan response data seperti biasa.
 *                               Digunakan untuk endpoint auth (login, verify-2fa).
 */
async function request(url, options = {}) {
  const { skipAuthRedirect = false, ...fetchOptions } = options;

  const token = getToken();
  const headers = {
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...fetchOptions.headers,
  };

  const response = await fetch(url, { ...fetchOptions, headers });

  if (response.status === 401 && !skipAuthRedirect) {
    clearAuth();
    window.location.href = '/login';
    return;
  }

  const data = await response.json();
  return { ok: response.ok, status: response.status, data };
}

export const http = {
  get:    (url)                       => request(url, { method: 'GET' }),
  post:   (url, body, opts = {})      => request(url, { method: 'POST',   body: JSON.stringify(body), ...opts }),
  put:    (url, body, opts = {})      => request(url, { method: 'PUT',    body: JSON.stringify(body), ...opts }),
  delete: (url)                       => request(url, { method: 'DELETE' }),
};
