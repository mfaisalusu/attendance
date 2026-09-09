import { getToken, clearAuth } from '../auth/authStore.js';

/**
 * Central HTTP client.  Automatically attaches the Bearer token and
 * handles 401 by clearing auth state.
 */
async function request(url, options = {}) {
  const token = getToken();
  const headers = {
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  };

  const response = await fetch(url, { ...options, headers });

  if (response.status === 401) {
    clearAuth();
    window.location.href = '/login';
    return;
  }

  const data = await response.json();
  return { ok: response.ok, status: response.status, data };
}

export const http = {
  get:    (url)          => request(url, { method: 'GET' }),
  post:   (url, body)    => request(url, { method: 'POST',   body: JSON.stringify(body) }),
  put:    (url, body)    => request(url, { method: 'PUT',    body: JSON.stringify(body) }),
  delete: (url)          => request(url, { method: 'DELETE' }),
};
