<script>
  import { authService } from '../../../application/services/authService.js';

  export let navigate;

  const email = sessionStorage.getItem('pending_email') ?? '';
  let token = '';
  let loading = false;
  let error = '';

  if (!email) navigate('/login');

  // Auto-advance: strip non-digits, cap at 6
  function handleInput(e) {
    token = e.target.value.replace(/\D/g, '').slice(0, 6);
  }

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
        token = '';
      }
    } catch {
      error = 'Gagal terhubung ke server.';
    } finally {
      loading = false;
    }
  }

  // Mask email for display: d****@kampus.ac.id
  function maskEmail(addr) {
    if (!addr) return '';
    const [local, domain] = addr.split('@');
    if (!domain) return addr;
    const visible = local.slice(0, 2);
    return `${visible}${'*'.repeat(Math.max(2, local.length - 2))}@${domain}`;
  }
</script>

<svelte:head><title>Verifikasi OTP — Absensi</title></svelte:head>

<div class="auth-wrap">
  <!-- Animated background blobs -->
  <div class="bg-blob blob-1"></div>
  <div class="bg-blob blob-2"></div>
  <div class="bg-blob blob-3"></div>
  <div class="bg-grid"></div>

  <div class="auth-container">
    <!-- Left panel — visual -->
    <div class="brand-panel">
      <!-- Decorative OTP visual -->
      <div class="otp-visual">
        <div class="otp-ring ring-outer">
          <div class="otp-ring ring-inner">
            <div class="otp-icon-wrap">
              <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
              </svg>
            </div>
          </div>
        </div>
        <!-- Orbiting dots -->
        <div class="orbit-dot dot-1"></div>
        <div class="orbit-dot dot-2"></div>
        <div class="orbit-dot dot-3"></div>
      </div>

      <h1 class="brand-title">Verifikasi<br/>Identitas Anda</h1>
      <p class="brand-desc">
        Kami mengirim kode rahasia 6 digit ke email Anda untuk memastikan keamanan akun.
      </p>

      <div class="info-card">
        <div class="info-row">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
          <span>Kode berlaku selama <strong>5 menit</strong></span>
        </div>
        <div class="info-row">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <span>Jangan bagikan kode kepada siapapun</span>
        </div>
        <div class="info-row">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.42 11 19.79 19.79 0 0 1 1.21 2.35 2 2 0 0 1 3.22.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 7.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
          </svg>
          <span>Cek folder <strong>Spam</strong> jika tidak masuk</span>
        </div>
      </div>
    </div>

    <!-- Right panel — form -->
    <div class="form-panel">
      <div class="form-header">
        <div class="form-badge">
          <span class="badge-dot"></span>
          Two-Factor Auth
        </div>
        <h2>Masukkan Kode OTP</h2>
        <p>
          Kode dikirim ke
          <span class="email-chip">{maskEmail(email)}</span>
        </p>
      </div>

      {#if error}
        <div class="alert-error" role="alert">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          {error}
        </div>
      {/if}

      <form on:submit={handleSubmit} novalidate>
        <div class="otp-field">
          <label for="token">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            Kode Verifikasi
          </label>
          <div class="otp-input-wrap">
            <input
              id="token"
              type="text"
              inputmode="numeric"
              pattern="[0-9]{6}"
              maxlength="6"
              bind:value={token}
              on:input={handleInput}
              placeholder="• • • • • •"
              required
              autocomplete="one-time-code"
              class:filled={token.length === 6}
            />
            <!-- Progress bar -->
            <div class="otp-progress">
              <div class="otp-progress-fill" style="width: {(token.length / 6) * 100}%"></div>
            </div>
            <!-- Digit counter -->
            <span class="digit-counter" class:complete={token.length === 6}>
              {token.length}/6
            </span>
          </div>
        </div>

        <button type="submit" class="submit-btn" disabled={loading || token.length !== 6}>
          {#if loading}
            <span class="btn-spinner"></span>
            Memverifikasi...
          {:else}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            Verifikasi & Masuk
          {/if}
        </button>
      </form>

      <!-- Divider -->
      <div class="divider">
        <span>atau</span>
      </div>

      <p class="form-footer">
        <a href="/login" class="back-link">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
          Kembali ke halaman login
        </a>
      </p>
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

  /* ===== BACKGROUND ===== */
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

  /* ===== CONTAINER ===== */
  .auth-container {
    position: relative;
    z-index: 1;
    display: flex;
    width: 100%;
    max-width: 860px;
    min-height: 500px;
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
    align-items: flex-start;
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

  /* ===== OTP VISUAL ===== */
  .otp-visual {
    position: relative;
    width: 120px;
    height: 120px;
    margin-bottom: 32px;
  }

  .otp-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .ring-outer {
    background: rgba(110,231,183,.06);
    border: 1px solid rgba(110,231,183,.15);
    animation: ringPulse 3s ease-in-out infinite;
  }
  .ring-inner {
    inset: 14px;
    background: rgba(110,231,183,.1);
    border: 1px solid rgba(110,231,183,.25);
  }
  .otp-icon-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6ee7b7;
  }
  @keyframes ringPulse {
    0%, 100% { transform: scale(1);    opacity: 1; }
    50%       { transform: scale(1.04); opacity: .8; }
  }

  /* Orbiting dots */
  .orbit-dot {
    position: absolute;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #34d399;
  }
  .dot-1 {
    top: 0; left: 50%;
    transform: translateX(-50%);
    animation: orbitFade 3s ease-in-out infinite;
    animation-delay: 0s;
  }
  .dot-2 {
    bottom: 8px; right: 4px;
    animation: orbitFade 3s ease-in-out infinite;
    animation-delay: -1s;
    background: #6ee7b7;
    width: 6px; height: 6px;
  }
  .dot-3 {
    bottom: 8px; left: 4px;
    animation: orbitFade 3s ease-in-out infinite;
    animation-delay: -2s;
    background: #a7f3d0;
    width: 5px; height: 5px;
  }
  @keyframes orbitFade {
    0%, 100% { opacity: .3; transform: scale(.8) translateX(-50%); }
    50%       { opacity: 1;  transform: scale(1)   translateX(-50%); }
  }
  .dot-2, .dot-3 { animation-name: orbitFade2; }
  @keyframes orbitFade2 {
    0%, 100% { opacity: .3; transform: scale(.8); }
    50%       { opacity: 1;  transform: scale(1); }
  }

  .brand-title {
    font-size: 1.7rem;
    font-weight: 700;
    line-height: 1.25;
    color: #f0fdf4;
    margin-bottom: 12px;
    letter-spacing: -0.02em;
  }

  .brand-desc {
    font-size: .875rem;
    line-height: 1.7;
    color: #86efac;
    opacity: .75;
    margin-bottom: 28px;
  }

  .info-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 16px 18px;
    background: rgba(110,231,183,.05);
    border: 1px solid rgba(110,231,183,.1);
    border-radius: 12px;
    width: 100%;
  }
  .info-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: .8rem;
    color: #6ee7b7;
    opacity: .75;
  }
  .info-row svg { flex-shrink: 0; opacity: .8; }
  .info-row strong { color: #a7f3d0; opacity: 1; }

  /* ===== FORM PANEL ===== */
  .form-panel {
    flex: 1;
    background: #111827;
    padding: 48px 44px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  /* ===== FORM HEADER ===== */
  .form-header { margin-bottom: 28px; }

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
    font-size: 1.55rem;
    font-weight: 700;
    color: #f9fafb;
    margin-bottom: 8px;
    letter-spacing: -0.02em;
  }
  .form-header p {
    font-size: .875rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .email-chip {
    display: inline-block;
    padding: 2px 10px;
    background: rgba(110,231,183,.08);
    border: 1px solid rgba(110,231,183,.2);
    border-radius: 999px;
    color: #6ee7b7;
    font-size: .78rem;
    font-weight: 600;
    font-family: 'Courier New', monospace;
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

  /* ===== OTP FIELD ===== */
  .otp-field { margin-bottom: 24px; }

  .otp-field label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .78rem;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 10px;
  }
  .otp-field label svg { color: #6ee7b7; }

  .otp-input-wrap { position: relative; }

  .otp-input-wrap input {
    width: 100%;
    padding: 18px 52px 18px 24px;
    background: #1a2332;
    border: 1px solid rgba(110,231,183,.12);
    border-radius: 12px;
    font-size: 2rem;
    font-weight: 700;
    color: #f9fafb;
    letter-spacing: .5em;
    text-align: center;
    outline: none;
    font-family: 'Courier New', monospace;
    transition: border-color .2s, box-shadow .2s, background .2s;
    caret-color: #6ee7b7;
  }
  .otp-input-wrap input::placeholder {
    color: rgba(110,231,183,.15);
    letter-spacing: .3em;
  }
  .otp-input-wrap input:focus {
    border-color: #6ee7b7;
    background: #1e2d3f;
    box-shadow: 0 0 0 3px rgba(110,231,183,.12);
  }
  .otp-input-wrap input.filled {
    border-color: rgba(52,211,153,.5);
    color: #6ee7b7;
  }

  /* Progress bar */
  .otp-progress {
    height: 3px;
    background: rgba(110,231,183,.08);
    border-radius: 0 0 12px 12px;
    overflow: hidden;
    margin-top: -3px;
  }
  .otp-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #34d399, #6ee7b7);
    border-radius: 2px;
    transition: width .2s ease;
  }

  /* Digit counter */
  .digit-counter {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: .72rem;
    font-weight: 600;
    color: #374151;
    font-family: inherit;
    transition: color .2s;
  }
  .digit-counter.complete { color: #34d399; }

  /* ===== SUBMIT BUTTON ===== */
  .submit-btn {
    width: 100%;
    padding: 14px 20px;
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
  .submit-btn:disabled { opacity: .45; cursor: not-allowed; }

  .btn-spinner {
    width: 15px; height: 15px;
    border: 2px solid rgba(10,34,24,.3);
    border-top-color: #0a2218;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    flex-shrink: 0;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ===== DIVIDER ===== */
  .divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 20px 0 0;
    color: #374151;
    font-size: .75rem;
  }
  .divider::before,
  .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,.06);
  }

  /* ===== FOOTER ===== */
  .form-footer {
    text-align: center;
    margin-top: 16px;
  }
  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: .83rem;
    color: #4b5563;
    text-decoration: none;
    transition: color .2s;
  }
  .back-link:hover { color: #6ee7b7; text-decoration: none; }

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
      align-items: center;
      text-align: center;
    }
    .info-card   { display: none; }
    .brand-desc  { display: none; }
    .otp-visual  { margin: 0 auto 24px; }
    .form-panel  { padding: 28px 24px; }
  }
</style>
