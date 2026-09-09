<script>
  import { authService } from '../../../application/services/authService.js';

  export let navigate;

  let email = '';
  let password = '';
  let loading = false;
  let error = '';

  async function handleSubmit(e) {
    e.preventDefault();
    error = '';
    loading = true;

    try {
      const res = await authService.login(email, password);

      if (res?.ok) {
        if (res.devBypass) {
          // Development mode: OTP dilewati, langsung ke dashboard
          navigate('/dashboard');
        } else {
          // Production mode: perlu verifikasi OTP
          sessionStorage.setItem('pending_email', email);
          navigate('/verify-2fa');
        }
      } else {
        error = res?.data?.message ?? 'Login gagal. Periksa email dan password.';
      }
    } catch {
      error = 'Gagal terhubung ke server.';
    } finally {
      loading = false;
    }
  }
</script>

<svelte:head><title>Login — Absensi</title></svelte:head>

<div class="auth-wrap">
  <div class="auth-card card">
    <div class="auth-brand">
      <span style="font-size:2rem">📋</span>
      <h1>Absensi Mahasiswa</h1>
      <p>Masuk ke akun Anda</p>
    </div>

    {#if error}
      <div class="alert alert-error" role="alert">{error}</div>
    {/if}

    <form on:submit={handleSubmit} novalidate>
      <div class="form-group">
        <label for="email">Email</label>
        <input id="email" type="email" class="form-control" bind:value={email}
          placeholder="dosen@kampus.ac.id" required autocomplete="email" />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" type="password" class="form-control" bind:value={password}
          placeholder="••••••••" required autocomplete="current-password" />
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%" disabled={loading}>
        {loading ? 'Memproses...' : 'Login'}
      </button>
    </form>

    <p class="auth-footer">
      Belum punya akun? <a href="/register">Daftar sekarang</a>
    </p>
  </div>
</div>

<style>
  .auth-wrap {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 16px;
  }
  .auth-card { width: 100%; max-width: 420px; }
  .auth-brand { text-align: center; margin-bottom: 24px; }
  .auth-brand h1 { font-size: 1.25rem; font-weight: 700; margin: 8px 0 4px; }
  .auth-brand p { color: var(--gray-500); font-size: .875rem; }
  .auth-footer { text-align: center; margin-top: 16px; font-size: .875rem; color: var(--gray-500); }
</style>
