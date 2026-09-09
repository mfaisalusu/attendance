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
</script>

<header class="topbar">
  <div class="topbar-left">
    <!-- Mobile menu placeholder -->
    <span class="topbar-title">Aplikasi Absensi Mahasiswa</span>
  </div>
  <div class="topbar-right">
    {#if user}
      <span class="user-name" aria-label="Nama pengguna">👤 {user.name}</span>
    {/if}
    <button class="btn btn-secondary btn-sm" on:click={handleLogout} disabled={loggingOut}>
      {loggingOut ? 'Keluar...' : 'Logout'}
    </button>
  </div>
</header>

<style>
  .topbar {
    position: fixed; top: 0; left: var(--sidebar-w); right: 0;
    height: var(--topbar-h); background: var(--white);
    border-bottom: 1px solid var(--gray-200);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 24px; z-index: 99; box-shadow: var(--shadow);
  }
  .topbar-title { font-weight: 600; font-size: .9rem; color: var(--gray-600); }
  .topbar-right { display: flex; align-items: center; gap: 16px; }
  .user-name { font-size: .875rem; color: var(--gray-700); font-weight: 500; }

  @media (max-width: 768px) {
    .topbar { left: 0; }
  }
</style>
