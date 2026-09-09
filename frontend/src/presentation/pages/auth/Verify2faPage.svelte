<script>
  import { authService } from '../../../application/services/authService.js';

  export let navigate;

  const email = sessionStorage.getItem('pending_email') ?? '';
  let token = '';
  let loading = false;
  let error = '';

  if (!email) navigate('/login');

  async function handleSubmit(e) {
    e.preventDefault();
    error = '';
    loading = true;

    try {
      const res = await authService.verify2fa(email, token);
      if (res?.ok) {
        sessionStorage.removeItem('pending_email');
        navigate('/dashboard');
      } else {
        error = res?.data?.message ?? 'Kode verifikasi tidak valid.';
      }
    } catch {
      error = 'Gagal terhubung ke server.';
    } finally {
      loading = false;
    }
  }
</script>

<svelte:head><title>Verifikasi OTP — Absensi</title></svelte:head>

<div class="auth-wrap">
  <div class="auth-card card">
    <div class="auth-brand">
      <span style="font-size:2rem">✉️</span>
      <h1>Verifikasi Email</h1>
      <p>Masukkan kode 6 digit yang dikirim ke</p>
      <strong>{email}</strong>
    </div>

    {#if error}
      <div class="alert alert-error" role="alert">{error}</div>
    {/if}

    <form on:submit={handleSubmit} novalidate>
      <div class="form-group">
        <label for="token">Kode Verifikasi</label>
        <input
          id="token"
          type="text"
          inputmode="numeric"
          pattern="[0-9]{6}"
          maxlength="6"
          class="form-control otp-input"
          bind:value={token}
          placeholder="123456"
          required
          autocomplete="one-time-code"
        />
        <small style="color:var(--gray-500);font-size:.8rem">Kode berlaku selama 5 menit.</small>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%" disabled={loading}>
        {loading ? 'Memverifikasi...' : 'Verifikasi'}
      </button>
    </form>

    <p class="auth-footer">
      <a href="/login">← Kembali ke Login</a>
    </p>
  </div>
</div>

<style>
  .auth-wrap {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 16px;
  }
  .auth-card { width: 100%; max-width: 400px; }
  .auth-brand { text-align: center; margin-bottom: 24px; }
  .auth-brand h1 { font-size: 1.25rem; font-weight: 700; margin: 8px 0 4px; }
  .auth-brand p  { color: var(--gray-500); font-size: .875rem; }
  .auth-brand strong { color: var(--gray-700); }
  .otp-input { text-align: center; font-size: 1.5rem; letter-spacing: 8px; font-weight: 700; }
  .auth-footer { text-align: center; margin-top: 16px; font-size: .875rem; }
</style>
