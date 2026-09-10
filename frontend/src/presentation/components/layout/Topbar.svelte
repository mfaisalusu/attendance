<script>
  import { getUser } from '../../../core/auth/authStore.js';
  import { authService } from '../../../application/services/authService.js';

  const user = getUser();
  let loggingOut = false;

  async function handleLogout() {
    loggingOut = true;
    await authService.logout();
    window.location.href = '/login';
  }

  // Get initials from name
  function getInitials(name = '') {
    return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
  }
</script>

<header class="topbar">
  <div class="topbar-left">
    <div class="topbar-breadcrumb">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--green-pastel);flex-shrink:0">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      <span class="topbar-title">Sistem Absensi Mahasiswa</span>
    </div>
  </div>

  <div class="topbar-right">
    {#if user}
      <div class="user-pill">
        <div class="user-avatar">
          {getInitials(user.name)}
        </div>
        <div class="user-info">
          <span class="user-name">{user.name}</span>
          <span class="user-role">Dosen</span>
        </div>
      </div>
    {/if}

    <button class="logout-btn" on:click={handleLogout} disabled={loggingOut} aria-label="Logout">
      {#if loggingOut}
        <span class="logout-spinner"></span>
      {:else}
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
      {/if}
      <span>{loggingOut ? 'Keluar...' : 'Logout'}</span>
    </button>
  </div>
</header>

<style>
  .topbar {
    position: fixed; top: 0; left: var(--sidebar-w); right: 0;
    height: var(--topbar-h);
    background: #0d1a0f;
    border-bottom: 1px solid rgba(110,231,183,.08);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 24px; z-index: 99;
    backdrop-filter: blur(8px);
  }

  /* ── Left ── */
  .topbar-left { display: flex; align-items: center; }
  .topbar-breadcrumb {
    display: flex; align-items: center; gap: 8px;
  }
  .topbar-title {
    font-size: .875rem; font-weight: 600;
    color: rgba(167,243,208,.6);
    letter-spacing: .01em;
  }

  /* ── Right ── */
  .topbar-right { display: flex; align-items: center; gap: 12px; }

  /* User pill */
  .user-pill {
    display: flex; align-items: center; gap: 10px;
    padding: 5px 12px 5px 6px;
    background: rgba(110,231,183,.06);
    border: 1px solid rgba(110,231,183,.1);
    border-radius: 999px;
  }
  .user-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    background: linear-gradient(135deg, #34d399, #059669);
    color: #0a2218;
    font-size: .72rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 0 8px rgba(52,211,153,.3);
  }
  .user-info {
    display: flex; flex-direction: column; gap: 1px;
    line-height: 1;
  }
  .user-name {
    font-size: .8rem; font-weight: 600;
    color: #d1fae5;
    white-space: nowrap;
    max-width: 140px; overflow: hidden; text-overflow: ellipsis;
  }
  .user-role {
    font-size: .65rem; color: rgba(110,231,183,.45);
    text-transform: uppercase; letter-spacing: .06em;
  }

  /* Logout button */
  .logout-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 7px 14px;
    background: rgba(248,113,113,.08);
    border: 1px solid rgba(248,113,113,.18);
    border-radius: 8px;
    color: #fca5a5;
    font-size: .8rem; font-weight: 600;
    cursor: pointer; font-family: inherit;
    transition: background .15s, box-shadow .15s;
  }
  .logout-btn:hover:not(:disabled) {
    background: rgba(248,113,113,.15);
    box-shadow: 0 2px 12px rgba(248,113,113,.15);
  }
  .logout-btn:disabled { opacity: .5; cursor: not-allowed; }

  .logout-spinner {
    width: 13px; height: 13px;
    border: 2px solid rgba(252,165,165,.3);
    border-top-color: #fca5a5;
    border-radius: 50%;
    animation: spin .6s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  @media (max-width: 768px) {
    .topbar { left: 0; padding: 0 16px; }
    .user-info { display: none; }
    .topbar-title { display: none; }
  }
</style>
