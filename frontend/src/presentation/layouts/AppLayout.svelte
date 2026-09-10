<script>
  import Sidebar from '../components/layout/Sidebar.svelte';
  import Topbar  from '../components/layout/Topbar.svelte';

  export let currentPath = '/dashboard';

  let sidebarOpen = false;
  function toggleSidebar() { sidebarOpen = !sidebarOpen; }
  function closeSidebar()  { sidebarOpen = false; }
</script>

<Sidebar {currentPath} {sidebarOpen} {closeSidebar} />
<Topbar {toggleSidebar} />

<main class="main-content">
  <slot />
</main>

<style>
  .main-content {
    margin-left: var(--sidebar-w);
    margin-top: var(--topbar-h);
    padding: 24px;
    min-height: calc(100vh - var(--topbar-h));
  }
  @media (max-width: 768px) {
    .main-content { margin-left: 0; padding: 16px; }
  }
</style>
