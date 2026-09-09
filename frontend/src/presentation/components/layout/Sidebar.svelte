<script>
  export let currentPath = '/';

  const navItems = [
    { path: '/dashboard',        label: 'Dashboard',       icon: '🏠' },
    { path: '/students',         label: 'Mahasiswa',       icon: '👥' },
    { path: '/attendance',       label: 'Absensi',         icon: '✅' },
    { path: '/attendance/recap', label: 'Rekap Absensi',   icon: '📊' },
  ];

  function isActive(path) {
    if (path === '/dashboard') return currentPath === '/dashboard';
    return currentPath.startsWith(path);
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
        class:active={isActive(item.path)}
        aria-current={isActive(item.path) ? 'page' : undefined}
      >
        <span class="nav-icon" aria-hidden="true">{item.icon}</span>
        <span>{item.label}</span>
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
    transition: background .15s, color .15s;
    text-decoration: none; border-radius: 0;
  }
  .nav-item:hover { background: rgba(255,255,255,.06); color: var(--white); text-decoration: none; }
  .nav-item.active { background: var(--primary); color: var(--white); }
  .nav-icon { font-size: 1rem; width: 20px; text-align: center; }

  @media (max-width: 768px) {
    .sidebar { display: none; }
  }
</style>
