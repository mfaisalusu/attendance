<script>
  export let currentPath = '/';

  const navItems = [
    { path: '/dashboard',        label: 'Dashboard',       icon: '🏠',  exact: true  },
    { path: '/students',         label: 'Mahasiswa',       icon: '👥',  exact: false },
    { path: '/attendance',       label: 'Absensi',         icon: '✅',  exact: true  },
    { path: '/attendance/recap', label: 'Rekap Absensi',   icon: '📊',  exact: false },
    { path: '/master',           label: 'Master Data',     icon: '🗂️',  exact: false },
  ];

  function isActive(item) {
    if (item.exact) return currentPath === item.path;
    return currentPath.startsWith(item.path);
  }
</script>

<aside class="sidebar">
  <div class="sidebar-brand">
    <span class="brand-icon">📋</span>
    <span class="brand-name">Absensi</span>
  </div>

  <nav class="sidebar-nav" aria-label="Menu utama">
    {#each navItems as item}
      <a
        href={item.path}
        class="nav-item"
        class:active={isActive(item)}
        aria-current={isActive(item) ? 'page' : undefined}
      >
        <span class="nav-icon" aria-hidden="true">{item.icon}</span>
        <span class="nav-label">{item.label}</span>
        {#if isActive(item)}
          <span class="active-dot" aria-hidden="true"></span>
        {/if}
      </a>
    {/each}
  </nav>
</aside>

<style>
  .sidebar {
    position: fixed; top: 0; left: 0; bottom: 0;
    width: var(--sidebar-w); background: var(--gray-900);
    display: flex; flex-direction: column; z-index: 100;
    overflow-y: auto;
  }
  .sidebar-brand {
    display: flex; align-items: center; gap: 10px;
    padding: 20px 16px; color: var(--white); font-weight: 700; font-size: 1.1rem;
    border-bottom: 1px solid rgba(255,255,255,.08);
  }
  .brand-icon { font-size: 1.4rem; }
  .sidebar-nav { padding: 12px 0; flex: 1; }

  .nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px; color: var(--gray-400);
    font-size: .875rem; font-weight: 500;
    transition: background .15s, color .15s, border-color .15s;
    text-decoration: none;
    border-left: 3px solid transparent;
    position: relative;
  }
  .nav-item:hover {
    background: rgba(255,255,255,.06);
    color: var(--white);
    text-decoration: none;
  }
  .nav-item.active {
    background: rgba(59, 130, 246, .18);
    color: var(--white);
    border-left-color: var(--primary);
    font-weight: 600;
  }

  .nav-icon { font-size: 1rem; width: 20px; text-align: center; flex-shrink: 0; }
  .nav-label { flex: 1; }

  /* Dot indikator di kanan */
  .active-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--primary);
    flex-shrink: 0;
  }

  @media (max-width: 768px) {
    .sidebar { display: none; }
  }
</style>
