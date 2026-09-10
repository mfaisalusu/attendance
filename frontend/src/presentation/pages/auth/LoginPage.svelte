<script>
  import { authService } from '../../../application/services/authService.js';

  export let navigate;

  let email = '';
  let password = '';
  let loading = false;
  let error = '';
  let showPassword = false;

  async function handleSubmit(e) {
    e.preventDefault();
    error = '';
    loading = true;

    try {
      const res = await authService.login(email, password);

      if (res?.ok) {
        if (res.devBypass) {
          navigate('/dashboard');
        } else {
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
  <!-- Animated background blobs -->
  <div class="bg-blob blob-1"></div>
  <div class="bg-blob blob-2"></div>
  <div class="bg-blob blob-3"></div>

  <!-- Decorative grid -->
  <div class="bg-grid"></div>

  <div class="auth-container">
    <!-- Left panel — branding -->
    <div class="brand-panel">
      <div class="brand-logo">
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="40" height="40" rx="12" fill="url(#logoGrad)"/>
          <path d="M12 14h16M12 20h10M12 26h13" stroke="#0d1117" stroke-width="2.5" stroke-linecap="round"/>
          <circle cx="29" cy="26" r="4" fill="#0d1117" opacity=".7"/>
          <path d="M27 26l1.5 1.5L31 24" stroke="#a8f0c6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          <defs>
            <linearGradient id="logoGrad" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
              <stop stop-color="#6ee7b7"/>
              <stop offset="1" stop-color="#34d399"/>
            </linearGradient>
          </defs>
        </svg>
      </div>
      <h1 class="brand-title">Sistem Absensi<br/>Mahasiswa</h1>
      <p class="brand-desc">Platform manajemen kehadiran mahasiswa yang cerdas dan efisien untuk perguruan tinggi modern.</p>
      <div class="brand-stats">
        <div class="stat-item">
          <span class="stat-number">99%</span>
          <span class="stat-label">Akurasi Data</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
          <span class="stat-number">24/7</span>
          <span class="stat-label">Real-time</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
          <span class="stat-number">Aman</span>
          <span class="stat-label">2FA Aktif</span>
        </div>
      </div>
    </div>

    <!-- Right panel — form -->
    <div class="form-panel">
      <div class="form-header">
        <div class="form-badge">
          <span class="badge-dot"></span>
          Sistem Aktif
        </div>
        <h2>Selamat Datang</h2>
        <p>Masuk ke akun Anda untuk melanjutkan</p>
      </div>

      {#if error}
        <div class="alert-error" role="alert">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          {error}
        </div>
      {/if}

      <form on:submit={handleSubmit} novalidate>
        <div class="field">
          <label for="email">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
            </svg>
            Email
          </label>
          <div class="input-wrap">
            <input
              id="email"
              type="email"
              bind:value={email}
              placeholder="dosen@kampus.ac.id"
              required
              autocomplete="email"
            />
          </div>
        </div>

        <div class="field">
          <label for="password">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            Password
          </label>
          <div class="input-wrap">
            {#if showPassword}
              <input
                id="password"
                type="text"
                bind:value={password}
                placeholder="••••••••"
                required
                autocomplete="current-password"
              />
            {:else}
              <input
                id="password"
                type="password"
                bind:value={password}
                placeholder="••••••••"
                required
                autocomplete="current-password"
              />
            {/if}
            <button
              type="button"
              class="eye-btn"
              on:click={() => showPassword = !showPassword}
              aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
            >
              {#if showPassword}
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              {:else}
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
              {/if}
            </button>
          </div>
        </div>

        <button type="submit" class="submit-btn" disabled={loading}>
          {#if loading}
            <span class="btn-spinner"></span>
            Memproses...
          {:else}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Masuk Sekarang
          {/if}
        </button>
      </form>

      <p class="form-footer">
        Belum punya akun?
        <a href="/register">Daftar sekarang</a>
      </p>
    </div>
  </div>
</div>

<style>
  /* ===== WRAP ===== */
  .auth-wrap {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #0d1117;
    position: relative;
    overflow: hidden;
    padding: 24px 16px;
  }

  /* ===== ANIMATED BACKGROUND BLOBS ===== */
  .bg-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.18;
    animation: blobFloat 8s ease-in-out infinite alternate;
    pointer-events: none;
  }
  .blob-1 {
    width: 500px; height: 500px;
    background: radial-gradient(circle, #6ee7b7, #34d399);
    top: -120px; left: -120px;
    animation-delay: 0s;
  }
  .blob-2 {
    width: 400px; height: 400px;
    background: radial-gradient(circle, #a7f3d0, #6ee7b7);
    bottom: -100px; right: -80px;
    animation-delay: -3s;
  }
  .blob-3 {
    width: 300px; height: 300px;
    background: radial-gradient(circle, #34d399, #059669);
    top: 50%; right: 30%;
    animation-delay: -5s;
    opacity: 0.1;
  }
  @keyframes blobFloat {
    0%   { transform: translate(0, 0) scale(1); }
    100% { transform: translate(30px, 40px) scale(1.08); }
  }

  /* ===== GRID OVERLAY ===== */
  .bg-grid {
    position: absolute;
    inset: 0;
    background-image:
      linear-gradient(rgba(110,231,183,.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(110,231,183,.04) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
  }

  /* ===== CONTAINER ===== */
  .auth-container {
    position: relative;
    z-index: 1;
    display: flex;
    width: 100%;
    max-width: 900px;
    min-height: 520px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow:
      0 0 0 1px rgba(110,231,183,.12),
      0 24px 64px rgba(0,0,0,.5),
      0 8px 24px rgba(0,0,0,.3);
  }

  /* ===== BRAND PANEL (LEFT) ===== */
  .brand-panel {
    flex: 0 0 42%;
    background: linear-gradient(155deg, #0a2218 0%, #0d2e1e 40%, #0a1a10 100%);
    padding: 48px 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow: hidden;
    border-right: 1px solid rgba(110,231,183,.1);
  }
  .brand-panel::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(110,231,183,.12), transparent 70%);
    pointer-events: none;
  }
  .brand-panel::after {
    content: '';
    position: absolute;
    bottom: -40px; left: -40px;
    width: 160px; height: 160px;
    background: radial-gradient(circle, rgba(52,211,153,.08), transparent 70%);
    pointer-events: none;
  }

  .brand-logo {
    margin-bottom: 28px;
  }

  .brand-title {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1.25;
    color: #f0fdf4;
    margin-bottom: 14px;
    letter-spacing: -0.02em;
  }

  .brand-desc {
    font-size: .875rem;
    line-height: 1.7;
    color: #86efac;
    opacity: .75;
    margin-bottom: 36px;
  }

  .brand-stats {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 18px 20px;
    background: rgba(110,231,183,.06);
    border: 1px solid rgba(110,231,183,.12);
    border-radius: 12px;
    backdrop-filter: blur(8px);
  }

  .stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
  }
  .stat-number {
    font-size: .95rem;
    font-weight: 700;
    color: #6ee7b7;
  }
  .stat-label {
    font-size: .7rem;
    color: #86efac;
    opacity: .6;
    margin-top: 2px;
    text-align: center;
  }
  .stat-divider {
    width: 1px;
    height: 28px;
    background: rgba(110,231,183,.15);
  }

  /* ===== FORM PANEL (RIGHT) ===== */
  .form-panel {
    flex: 1;
    background: #111827;
    padding: 48px 44px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  /* ===== FORM HEADER ===== */
  .form-header {
    margin-bottom: 32px;
  }

  .form-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    background: rgba(110,231,183,.08);
    border: 1px solid rgba(110,231,183,.2);
    font-size: .72rem;
    font-weight: 600;
    color: #6ee7b7;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 16px;
  }
  .badge-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #34d399;
    animation: pulse 2s ease-in-out infinite;
  }
  @keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: .5; transform: scale(.8); }
  }

  .form-header h2 {
    font-size: 1.6rem;
    font-weight: 700;
    color: #f9fafb;
    margin-bottom: 6px;
    letter-spacing: -0.02em;
  }
  .form-header p {
    font-size: .875rem;
    color: #6b7280;
  }

  /* ===== ALERT ===== */
  .alert-error {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 11px 14px;
    border-radius: 10px;
    background: rgba(239,68,68,.1);
    border: 1px solid rgba(239,68,68,.25);
    color: #fca5a5;
    font-size: .8rem;
    margin-bottom: 20px;
  }

  /* ===== FIELDS ===== */
  .field {
    margin-bottom: 20px;
  }
  .field label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .78rem;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 8px;
  }
  .field label svg {
    color: #6ee7b7;
    flex-shrink: 0;
  }

  .input-wrap {
    position: relative;
  }
  .input-wrap input {
    width: 100%;
    padding: 12px 16px;
    background: #1a2332;
    border: 1px solid rgba(110,231,183,.12);
    border-radius: 10px;
    font-size: .9rem;
    color: #f9fafb;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
    font-family: inherit;
  }
  .input-wrap input::placeholder {
    color: #374151;
  }
  .input-wrap input:focus {
    border-color: #6ee7b7;
    background: #1e2d3f;
    box-shadow: 0 0 0 3px rgba(110,231,183,.12);
  }

  /* password toggle */
  .input-wrap input[type="password"],
  .input-wrap input[type="text"] {
    padding-right: 44px;
  }
  .eye-btn {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: #4b5563;
    transition: color .2s;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
  }
  .eye-btn:hover { color: #6ee7b7; }

  /* ===== SUBMIT BUTTON ===== */
  .submit-btn {
    width: 100%;
    padding: 13px 20px;
    margin-top: 8px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #34d399 0%, #059669 100%);
    color: #0a2218;
    font-size: .92rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: transform .15s, box-shadow .15s, opacity .15s;
    box-shadow: 0 4px 20px rgba(52,211,153,.3);
    letter-spacing: .01em;
    font-family: inherit;
  }
  .submit-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 8px 28px rgba(52,211,153,.4);
  }
  .submit-btn:active:not(:disabled) {
    transform: translateY(0);
  }
  .submit-btn:disabled {
    opacity: .6;
    cursor: not-allowed;
  }

  .btn-spinner {
    width: 15px; height: 15px;
    border: 2px solid rgba(10,34,24,.3);
    border-top-color: #0a2218;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    flex-shrink: 0;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ===== FOOTER ===== */
  .form-footer {
    text-align: center;
    margin-top: 24px;
    font-size: .83rem;
    color: #4b5563;
  }
  .form-footer a {
    color: #6ee7b7;
    font-weight: 600;
    text-decoration: none;
    transition: color .2s;
  }
  .form-footer a:hover {
    color: #a7f3d0;
    text-decoration: underline;
  }

  /* ===== RESPONSIVE ===== */
  @media (max-width: 680px) {
    .auth-container {
      flex-direction: column;
      max-width: 440px;
    }
    .brand-panel {
      flex: none;
      padding: 32px 28px 24px;
      border-right: none;
      border-bottom: 1px solid rgba(110,231,183,.1);
    }
    .brand-title { font-size: 1.4rem; }
    .brand-desc  { display: none; }
    .form-panel  { padding: 32px 28px; }
  }
</style>
