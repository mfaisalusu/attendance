import { authApi } from '../../infrastructure/api/authApi.js';
import { setAuth, clearAuth } from '../../core/auth/authStore.js';

export const authService = {
  async register(name, email, password, passwordConfirmation) {
    const res = await authApi.register({
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
    });
    return res;
  },

  async login(email, password) {
    const res = await authApi.login({ email, password });

    // Development mode: backend langsung kembalikan token tanpa OTP
    if (res?.ok && res.data?.data?.authenticated && res.data?.data?.token) {
      setAuth(res.data.data.token, res.data.data.user);
      return { ...res, devBypass: true };
    }

    return res;
  },

  async verify2fa(email, token) {
    const res = await authApi.verify2fa({ email, token });
    if (res?.ok && res.data?.data) {
      setAuth(res.data.data.token, res.data.data.user);
    }
    return res;
  },

  async logout() {
    try {
      await authApi.logout();
    } finally {
      clearAuth();
    }
  },
};
