import { getToken, clearAuth } from '../auth/authStore.js';

/**
 * Base URL backend — diambil dari env variable VITE_API_BASE.
 * - Development : kosong (''), URL tetap relatif → Vite proxy yang handle
 * - Production  : 'https://api.yourdomain.com' → absolute URL ke backend
 */
const API_BASE = import.meta.env.VITE_API_BASE ?? '';

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

  const fullUrl = `${API_BASE}${url}`;

  let response;
  try {
    response = await fetch(fullUrl, { ...fetchOptions, headers });
  } catch {
    // Network error — server tidak bisa dicapai sama sekali
    return { ok: false, status: 0, data: { message: 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.' } };
  }

  if (response.status === 401 && !skipAuthRedirect) {
    clearAuth();
    window.location.href = '/login';
    return;
  }

  // Guard: response bisa bukan JSON saat server error (500, 502, 503, dll.)
  let data;
  const contentType = response.headers.get('Content-Type') ?? '';
  if (contentType.includes('application/json')) {
    try {
      data = await response.json();
    } catch {
      data = { message: 'Respons server tidak valid.' };
    }
  } else {
    // Server mengembalikan HTML (halaman error hosting) atau teks biasa
    data = { message: `Server error (${response.status}). Silakan coba beberapa saat lagi.` };
  }

  return { ok: response.ok, status: response.status, data };
}

export const http = {
  get:    (url)                       => request(url, { method: 'GET' }),
  post:   (url, body, opts = {})      => request(url, { method: 'POST',   body: JSON.stringify(body), ...opts }),
  put:    (url, body, opts = {})      => request(url, { method: 'PUT',    body: JSON.stringify(body), ...opts }),
  delete: (url)                       => request(url, { method: 'DELETE' }),
};
