<script>
  import { onMount } from 'svelte';
  import { isAuthenticated } from '../core/auth/authStore.js';

  // Pages
  import LoginPage      from '../presentation/pages/auth/LoginPage.svelte';
  import RegisterPage   from '../presentation/pages/auth/RegisterPage.svelte';
  import Verify2faPage  from '../presentation/pages/auth/Verify2faPage.svelte';
  import DashboardPage  from '../presentation/pages/dashboard/DashboardPage.svelte';
  import StudentsPage   from '../presentation/pages/students/StudentsPage.svelte';
  import AttendancePage from '../presentation/pages/attendance/AttendancePage.svelte';
  import RecapPage      from '../presentation/pages/attendance/RecapPage.svelte';

  // Public routes (no auth needed)
  const publicRoutes = ['/login', '/register', '/verify-2fa'];

  let currentPath = window.location.pathname;

  function navigate(path) {
    window.history.pushState({}, '', path);
    currentPath = path;
  }

  // Handle browser back/forward
  onMount(() => {
    const handler = () => { currentPath = window.location.pathname; };
    window.addEventListener('popstate', handler);
    return () => window.removeEventListener('popstate', handler);
  });

  // Intercept all <a> clicks for SPA navigation
  onMount(() => {
    const handler = (e) => {
      const a = e.target.closest('a[href]');
      if (!a) return;
      const href = a.getAttribute('href');
      if (!href || href.startsWith('http') || href.startsWith('mailto')) return;
      e.preventDefault();
      navigate(href);
    };
    document.addEventListener('click', handler);
    return () => document.removeEventListener('click', handler);
  });

  // Auth guard
  $: {
    const authed = isAuthenticated();
    const isPublic = publicRoutes.some(p => currentPath.startsWith(p));

    if (!authed && !isPublic) {
      navigate('/login');
    } else if (authed && (currentPath === '/login' || currentPath === '/register' || currentPath === '/')) {
      navigate('/dashboard');
    }
  }

  // Normalise trailing slash
  $: normPath = currentPath.replace(/\/$/, '') || '/dashboard';
</script>

{#if normPath === '/login'}
  <LoginPage {navigate} />
{:else if normPath === '/register'}
  <RegisterPage {navigate} />
{:else if normPath === '/verify-2fa'}
  <Verify2faPage {navigate} />
{:else if normPath === '/dashboard'}
  <DashboardPage />
{:else if normPath === '/students' || normPath.startsWith('/students')}
  <StudentsPage />
{:else if normPath === '/attendance/recap'}
  <RecapPage />
{:else if normPath === '/attendance'}
  <AttendancePage />
{:else}
  <!-- 404 fallback -->
  <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;gap:16px">
    <span style="font-size:4rem">404</span>
    <p style="color:var(--gray-500)">Halaman tidak ditemukan.</p>
    <a href="/dashboard" class="btn btn-primary">Kembali ke Dashboard</a>
  </div>
{/if}
