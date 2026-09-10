<script>
  import { authService } from '../../../application/services/authService.js';

  export let navigate;

  let name = '', email = '', password = '', passwordConfirmation = '';
  let loading = false;
  let error = '';
  let fieldErrors = {};
  let success = false;
  let showPassword = false;
  let showPasswordConfirmation = false;

  async function handleSubmit(e) {
    e.preventDefault();
    error = '';
    fieldErrors = {};
    loading = true;

    try {
      const res = await authService.register(name, email, password, passwordConfirmation);
      if (res?.ok) {
        success = true;
      } else {
        error = res?.data?.message ?? 'Pendaftaran gagal.';
        fieldErrors = res?.data?.errors ?? {};
      }
    } catch {
      error = 'Gagal terhubung ke server.';
    } finally {
      loading = false;
    }
  }
</script>

<svelte:head><title>Daftar — Absensi</title></svelte:head>

<div class="auth-wrap">
  <!-- Animated background blobs -->
  <div class="bg-blob blob-1"></div>
  <div class="bg-blob blob-2"></div>
  <div class="bg-blob blob-3"></div>
  <div class="bg-grid"></div>

  <div class="auth-container">
    <!-- Left panel — branding -->
    <div class="brand-panel">
      <div class="brand-logo">
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="40" height="40" rx="12" fill="url(#logoGradR)"/>
          <path d="M12 14h16M12 20h10M12 26h13" stroke="#0d1117" stroke-width="2.5" stroke-linecap="round"/>
          <circle cx="29" cy="26" r="4" fill="#0d1117" opacity=".7"/>
          <path d="M27 26l1.5 1.5L31 24" stroke="#a8f0c6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          <defs>
            <linearGradient id="logoGradR" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
              <stop stop-color="#6ee7b7"/>
              <stop offset="1" stop-color="#34d399"/>
            </linearGradient>
          </defs>
        </svg>
      </div>
      <h1 class="brand-title">Bergabung<br/>Bersama Kami</h1>
      <p class="brand-desc">Buat akun dosen dan mulai kelola kehadiran mahasiswa dengan lebih mudah dan terstruktur.</p>

      <ul class="feature-list">
        <li>
          <span class="feature-icon">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </span>
          Rekap absensi otomatis per periode
        </li>
        <li>
          <span class="feature-icon">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </span>
          Verifikasi dua faktor untuk keamanan
        </li>
        <li>
          <span class="feature-icon">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </span>
          Dashboard statistik real-time
        </li>
        <li>
          <span class="feature-icon">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </span>
          Manajemen data mahasiswa lengkap
        </li>
      </ul>
    </div>

    <!-- Right panel — form -->
    <div class="form-panel">
      {#if success}
        <div class="success-state">
          <div class="success-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <h2>Akun Berhasil Dibuat!</h2>
          <p>Selamat, akun Anda telah terdaftar. Silakan masuk untuk mulai menggunakan sistem.</p>
          <a href="/login" class="submit-btn" style="text-decoration:none; display:inline-flex; width:auto; padding: 12px 28px; margin-top: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Masuk Sekarang
          </a>
        </div>
      {:else}
        <div class="form-header">
          <div class="form-badge">
            <span class="badge-dot"></span>
            Registrasi Dosen
          </div>
          <h2>Buat Akun Baru</h2>
          <p>Lengkapi data berikut untuk mendaftar</p>
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
          <!-- Nama -->
          <div class="field" class:has-error={fieldErrors.name}>
            <label for="name">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
              </svg>
              Nama Lengkap
            </label>
            <div class="input-wrap">
              <input
                id="name" type="text"
                bind:value={name}
                placeholder="Dr. Budi Santoso"
                required autocomplete="name"
                class:invalid={fieldErrors.name}
              />
            </div>
            {#if fieldErrors.name}<span class="field-error">{fieldErrors.name[0]}</span>{/if}
          </div>

          <!-- Email -->
          <div class="field" class:has-error={fieldErrors.email}>
            <label for="email">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
              </svg>
              Email
            </label>
            <div class="input-wrap">
              <input
                id="email" type="email"
                bind:value={email}
                placeholder="dosen@kampus.ac.id"
                required autocomplete="email"
                class:invalid={fieldErrors.email}
              />
            </div>
            {#if fieldErrors.email}<span class="field-error">{fieldErrors.email[0]}</span>{/if}
          </div>

          <!-- Password -->
          <div class="field" class:has-error={fieldErrors.password}>
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
                  placeholder="Minimal 8 karakter"
                  required autocomplete="new-password"
                  class:invalid={fieldErrors.password}
                />
              {:else}
                <input
                  id="password"
                  type="password"
                  bind:value={password}
                  placeholder="Minimal 8 karakter"
                  required autocomplete="new-password"
                  class:invalid={fieldErrors.password}
                />
              {/if}
              <button type="button" class="eye-btn"
                on:click={() => showPassword = !showPassword}
                aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}>
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
            {#if fieldErrors.password}<span class="field-error">{fieldErrors.password[0]}</span>{/if}
          </div>

          <!-- Konfirmasi Password -->
          <div class="field">
            <label for="password_confirmation">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              Konfirmasi Password
            </label>
            <div class="input-wrap">
              {#if showPasswordConfirmation}
                <input
                  id="password_confirmation"
                  type="text"
                  bind:value={passwordConfirmation}
                  placeholder="Ulangi password"
                  required autocomplete="new-password"
                />
              {:else}
                <input
                  id="password_confirmation"
                  type="password"
                  bind:value={passwordConfirmation}
                  placeholder="Ulangi password"
                  required autocomplete="new-password"
                />
              {/if}
              <button type="button" class="eye-btn"
                on:click={() => showPasswordConfirmation = !showPasswordConfirmation}
                aria-label={showPasswordConfirmation ? 'Sembunyikan' : 'Tampilkan'}>
                {#if showPasswordConfirmation}
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
              Mendaftar...
            {:else}
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
              </svg>
              Buat Akun
            {/if}
          </button>
        </form>

        <p class="form-footer">
          Sudah punya akun?
          <a href="/login">Masuk di sini</a>
        </p>
      {/if}
    </div>
  </div>
</div>

<style>
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

  .bg-grid {
    position: absolute;
    inset: 0;
    background-image:
      linear-gradient(rgba(110,231,183,.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(110,231,183,.04) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
  }

  .auth-container {
    position: relative;
    z-index: 1;
    display: flex;
    width: 100%;
    max-width: 900px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow:
      0 0 0 1px rgba(110,231,183,.12),
      0 24px 64px rgba(0,0,0,.5),
      0 8px 24px rgba(0,0,0,.3);
  }

  /* ===== BRAND PANEL ===== */
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

  .brand-logo { margin-bottom: 28px; }

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
    margin-bottom: 28px;
  }

  .feature-list {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .feature-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: .825rem;
    color: #86efac;
    opacity: .8;
  }
  .feature-icon {
    width: 20px; height: 20px;
    border-radius: 50%;
    background: rgba(110,231,183,.15);
    border: 1px solid rgba(110,231,183,.25);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #6ee7b7;
  }

  /* ===== FORM PANEL ===== */
  .form-panel {
    flex: 1;
    background: #111827;
    padding: 48px 44px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  /* ===== SUCCESS STATE ===== */
  .success-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 12px;
    padding: 20px 0;
  }
  .success-icon {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(52,211,153,.2), rgba(110,231,183,.1));
    border: 2px solid rgba(110,231,183,.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6ee7b7;
    margin-bottom: 8px;
  }
  .success-state h2 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #f9fafb;
  }
  .success-state p {
    font-size: .875rem;
    color: #6b7280;
    max-width: 280px;
    line-height: 1.6;
  }

  /* ===== FORM HEADER ===== */
  .form-header { margin-bottom: 24px; }

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
    margin-bottom: 14px;
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
    font-size: 1.5rem;
    font-weight: 700;
    color: #f9fafb;
    margin-bottom: 4px;
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
    margin-bottom: 16px;
  }

  /* ===== FIELDS ===== */
  .field {
    margin-bottom: 16px;
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
    margin-bottom: 6px;
  }
  .field label svg { color: #6ee7b7; flex-shrink: 0; }

  .input-wrap { position: relative; }
  .input-wrap input {
    width: 100%;
    padding: 11px 16px;
    background: #1a2332;
    border: 1px solid rgba(110,231,183,.12);
    border-radius: 10px;
    font-size: .875rem;
    color: #f9fafb;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
    font-family: inherit;
  }
  .input-wrap input::placeholder { color: #374151; }
  .input-wrap input:focus {
    border-color: #6ee7b7;
    background: #1e2d3f;
    box-shadow: 0 0 0 3px rgba(110,231,183,.12);
  }
  .input-wrap input.invalid {
    border-color: rgba(239,68,68,.5);
  }
  .input-wrap input[type="password"],
  .input-wrap input[type="text"] { padding-right: 44px; }

  .field-error {
    display: block;
    margin-top: 4px;
    font-size: .75rem;
    color: #f87171;
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
    border-radius: 4px;
  }
  .eye-btn:hover { color: #6ee7b7; }

  /* ===== SUBMIT BUTTON ===== */
  .submit-btn {
    width: 100%;
    padding: 13px 20px;
    margin-top: 4px;
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
  .submit-btn:active:not(:disabled) { transform: translateY(0); }
  .submit-btn:disabled { opacity: .6; cursor: not-allowed; }

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
    margin-top: 20px;
    font-size: .83rem;
    color: #4b5563;
  }
  .form-footer a {
    color: #6ee7b7;
    font-weight: 600;
    text-decoration: none;
    transition: color .2s;
  }
  .form-footer a:hover { color: #a7f3d0; text-decoration: underline; }

  /* ===== RESPONSIVE ===== */
  @media (max-width: 680px) {
    .auth-container {
      flex-direction: column;
      max-width: 440px;
    }
    .brand-panel {
      flex: none;
      padding: 28px 24px 20px;
      border-right: none;
      border-bottom: 1px solid rgba(110,231,183,.1);
    }
    .brand-title { font-size: 1.3rem; }
    .brand-desc  { display: none; }
    .form-panel  { padding: 28px 24px; }
  }
</style>
