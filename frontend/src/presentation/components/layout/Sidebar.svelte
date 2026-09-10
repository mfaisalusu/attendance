<script>
  export let currentPath = '/';

  const navItems = [
    { path: '/dashboard',        label: 'Dashboard',     icon: 'dashboard', exact: true  },
    { path: '/students',         label: 'Mahasiswa',     icon: 'students',  exact: false },
    { path: '/attendance',       label: 'Absensi',       icon: 'attendance',exact: true  },
    { path: '/attendance/recap', label: 'Rekap Absensi', icon: 'recap',     exact: false },
    { path: '/master',           label: 'Master Data',   icon: 'master',    exact: false },
  ];

  function isActive(item) {
    if (item.exact) return currentPath === item.path;
    return currentPath.startsWith(item.path);
  }
</script>

<aside class="sidebar">
  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="brand-logo">
      <svg width="28" height="28" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect width="40" height="40" rx="10" fill="url(#sbGrad)"/>
        <path d="M11 13h18M11 20h12M11 27h14" stroke="#0a2218" stroke-width="2.8" stroke-linecap="round"/>
        <circle cx="30" cy="27" r="5" fill="#0a2218" opacity=".75"/>
        <path d="M27.8 27l1.8 1.8L32 25" stroke="#a7f3d0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <defs>
          <linearGradient id="sbGrad" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
            <stop stop-color="#6ee7b7"/>
            <stop offset="1" stop-color="#34d399"/>
          </linearGradient>
        </defs>
      </svg>
    </div>
    <div class="brand-text">
      <span class="brand-name">Absensi</span>
      <span class="brand-sub">Sistem Kehadiran</span>
    </div>
  </div>

  <!-- Nav label -->
  <div class="nav-section-label">Menu Utama</div>

  <!-- Navigation -->
  <nav class="sidebar-nav" aria-label="Menu utama">
    {#each navItems as item}
      <a
        href={item.path}
        class="nav-item"
        class:active={isActive(item)}
        aria-current={isActive(item) ? 'page' : undefined}
      >
        <span class="nav-icon" aria-hidden="true">
          {#if item.icon === 'dashboard'}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
              <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
            </svg>
          {:else if item.icon === 'students'}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          {:else if item.icon === 'attendance'}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <path d="M9 11l3 3L22 4"/>
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
          {:else if item.icon === 'recap'}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
              <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
          {:else if item.icon === 'master'}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <ellipse cx="12" cy="5" rx="9" ry="3"/>
              <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
              <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
            </svg>
          {/if}
        </span>
        <span class="nav-label">{item.label}</span>
        {#if isActive(item)}
          <span class="active-indicator" aria-hidden="true"></span>
        {/if}
      </a>
    {/each}
  </nav>

  <!-- Bottom decoration -->
  <div class="sidebar-footer">
    <div class="sidebar-version">v1.0.0</div>
  </div>
</aside>

<style>
  .sidebar {
    position: fixed; top: 0; left: 0; bottom: 0;
    width: var(--sidebar-w);
    background: #0d1a0f;
    display: flex; flex-direction: column;
    z-index: 100; overflow-y: auto;
    border-right: 1px solid rgba(110,231,183,.08);
  }

  /* scrollbar */
  .sidebar::-webkit-scrollbar { width: 4px; }
  .sidebar::-webkit-scrollbar-track { background: transparent; }
  .sidebar::-webkit-scrollbar-thumb { background: rgba(110,231,183,.15); border-radius: 2px; }

  /* ── Brand ── */
  .sidebar-brand {
    display: flex; align-items: center; gap: 11px;
    padding: 20px 18px 18px;
    border-bottom: 1px solid rgba(110,231,183,.08);
  }
  .brand-logo {
    flex-shrink: 0;
    filter: drop-shadow(0 2px 8px rgba(110,231,183,.3));
  }
  .brand-text {
    display: flex; flex-direction: column; gap: 1px;
  }
  .brand-name {
    font-size: .95rem; font-weight: 800;
    color: #f0fdf4; letter-spacing: -.01em;
  }
  .brand-sub {
    font-size: .65rem; font-weight: 500;
    color: #6ee7b7; opacity: .6;
    text-transform: uppercase; letter-spacing: .08em;
  }

  /* ── Section label ── */
  .nav-section-label {
    padding: 18px 18px 6px;
    font-size: .65rem; font-weight: 700;
    color: rgba(110,231,183,.35);
    text-transform: uppercase; letter-spacing: .1em;
  }

  /* ── Nav ── */
  .sidebar-nav { padding: 4px 10px; flex: 1; }

  .nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    color: rgba(167,243,208,.55);
    font-size: .855rem; font-weight: 500;
    transition: background .15s, color .15s;
    text-decoration: none;
    margin-bottom: 2px;
    position: relative;
  }
  .nav-item:hover {
    background: rgba(110,231,183,.07);
    color: #a7f3d0;
    text-decoration: none;
  }
  .nav-item.active {
    background: rgba(110,231,183,.12);
    color: #6ee7b7;
    font-weight: 700;
  }

  .nav-icon {
    width: 20px; display: flex; align-items: center;
    justify-content: center; flex-shrink: 0;
    opacity: .8;
  }
  .nav-item.active .nav-icon { opacity: 1; }
  .nav-label { flex: 1; }

  /* right-side active dot */
  .active-indicator {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #34d399;
    box-shadow: 0 0 6px rgba(52,211,153,.6);
    flex-shrink: 0;
  }

  /* ── Footer ── */
  .sidebar-footer {
    padding: 16px 18px;
    border-top: 1px solid rgba(110,231,183,.06);
    margin-top: auto;
  }
  .sidebar-version {
    font-size: .68rem;
    color: rgba(110,231,183,.25);
    font-weight: 500;
  }

  @media (max-width: 768px) { .sidebar { display: none; } }
</style>
