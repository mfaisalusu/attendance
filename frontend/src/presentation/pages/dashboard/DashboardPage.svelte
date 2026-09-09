<script>
  import { onMount } from 'svelte';
  import AppLayout from '../../layouts/AppLayout.svelte';
  import Spinner   from '../../components/common/Spinner.svelte';
  import { dashboardApi } from '../../../infrastructure/api/dashboardApi.js';
  import { formatDate } from '../../../core/utils/format.js';
  import { getUser } from '../../../core/auth/authStore.js';

  const user = getUser();
  let stats = null;
  let loading = true;
  let error = '';

  onMount(async () => {
    const res = await dashboardApi.stats();
    if (res?.ok) {
      stats = res.data.data;
    } else {
      error = 'Gagal memuat data dashboard.';
    }
    loading = false;
  });
</script>

<svelte:head><title>Dashboard — Absensi</title></svelte:head>

<AppLayout currentPath="/dashboard">
  <div class="page-header">
    <h1>Dashboard</h1>
    <span style="color:var(--gray-500);font-size:.875rem">
      {stats ? formatDate(stats.today) : ''}
    </span>
  </div>

  {#if user}
    <p style="color:var(--gray-600);margin-bottom:24px;font-size:.95rem">
      Selamat datang, <strong>{user.name}</strong> 👋
    </p>
  {/if}

  {#if loading}
    <Spinner />
  {:else if error}
    <div class="alert alert-error" role="alert">{error}</div>
  {:else if stats}
    <div class="stats-grid" style="margin-bottom:32px">
      <div class="stat-card primary">
        <span class="stat-value">{stats.total_mahasiswa}</span>
        <span class="stat-label">Total Mahasiswa</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{stats.total_absen_hari_ini}</span>
        <span class="stat-label">Diabsen Hari Ini</span>
      </div>
      <div class="stat-card success">
        <span class="stat-value">{stats.hadir_hari_ini}</span>
        <span class="stat-label">Hadir Hari Ini</span>
      </div>
      <div class="stat-card warning">
        <span class="stat-value">{stats.izin_hari_ini}</span>
        <span class="stat-label">Izin Hari Ini</span>
      </div>
      <div class="stat-card warning">
        <span class="stat-value">{stats.sakit_hari_ini}</span>
        <span class="stat-label">Sakit Hari Ini</span>
      </div>
      <div class="stat-card danger">
        <span class="stat-value">{stats.alpha_hari_ini}</span>
        <span class="stat-label">Alpha Hari Ini</span>
      </div>
    </div>

    <div class="card">
      <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px">Akses Cepat</h2>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        {#each stats.shortcuts as s}
          <a href={s.path} class="btn btn-primary">{s.label}</a>
        {/each}
      </div>
    </div>
  {/if}
</AppLayout>
