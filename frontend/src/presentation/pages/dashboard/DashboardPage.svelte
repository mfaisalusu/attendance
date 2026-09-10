<script>
  import { onMount } from 'svelte';
  import AppLayout from '../../layouts/AppLayout.svelte';
  import { dashboardApi } from '../../../infrastructure/api/dashboardApi.js';
  import { formatDate } from '../../../core/utils/format.js';
  import { getUser } from '../../../core/auth/authStore.js';

  const user = getUser();
  let stats  = null;
  let loading = true;
  let error   = '';

  onMount(async () => {
    const res = await dashboardApi.stats();
    if (res?.ok) {
      stats = res.data.data;
    } else {
      error = 'Gagal memuat data dashboard.';
    }
    loading = false;
  });

  // Greeting based on hour
  function getGreeting() {
    const h = new Date().getHours();
    if (h < 11) return 'Selamat Pagi';
    if (h < 15) return 'Selamat Siang';
    if (h < 18) return 'Selamat Sore';
    return 'Selamat Malam';
  }

  // Attendance rate calculation
  $: attendanceRate = stats
    ? stats.total_mahasiswa > 0
      ? Math.round((stats.hadir_hari_ini / stats.total_mahasiswa) * 100)
      : 0
    : 0;
</script>

<svelte:head><title>Dashboard — Absensi</title></svelte:head>

<AppLayout currentPath="/dashboard">

  <!-- ── Page Header ── -->
  <div class="dash-header">
    <div class="dash-header-left">
      <div class="dash-greeting">
        <span class="greeting-badge">
          <span class="greeting-dot"></span>
          {getGreeting()}
        </span>
        <h1>
          {user ? user.name : 'Pengguna'}
          <span class="wave">👋</span>
        </h1>
        <p class="dash-subtitle">
          {#if stats}
            {formatDate(stats.today)}
          {:else}
            Memuat data hari ini...
          {/if}
        </p>
      </div>
    </div>
    <div class="dash-header-right">
      <a href="/attendance" class="quick-action-btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M12 5v14M5 12l7 7 7-7"/>
        </svg>
        Input Absensi
      </a>
    </div>
  </div>

  <!-- ── Loading ── -->
  {#if loading}
    <div class="dash-loading">
      <div class="dash-spinner"></div>
      <span>Memuat data...</span>
    </div>

  <!-- ── Error ── -->
  {:else if error}
    <div class="alert alert-error" role="alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      {error}
    </div>

  <!-- ── Content ── -->
  {:else if stats}

    <!-- Stat cards -->
    <div class="stats-grid">

      <div class="stat-card stat-total">
        <div class="stat-icon-wrap">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div class="stat-body">
          <span class="stat-value">{stats.total_mahasiswa}</span>
          <span class="stat-label">Total Mahasiswa</span>
        </div>
        <div class="stat-trend stat-trend-neutral">Terdaftar</div>
      </div>

      <div class="stat-card stat-absen">
        <div class="stat-icon-wrap">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M9 11l3 3L22 4"/>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
          </svg>
        </div>
        <div class="stat-body">
          <span class="stat-value">{stats.total_absen_hari_ini}</span>
          <span class="stat-label">Diabsen Hari Ini</span>
        </div>
        <div class="stat-trend stat-trend-info">Hari ini</div>
      </div>

      <div class="stat-card stat-hadir">
        <div class="stat-icon-wrap">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <div class="stat-body">
          <span class="stat-value">{stats.hadir_hari_ini}</span>
          <span class="stat-label">Hadir</span>
        </div>
        <div class="stat-trend stat-trend-success">
          {attendanceRate}% kehadiran
        </div>
      </div>

      <div class="stat-card stat-izin">
        <div class="stat-icon-wrap">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
          </svg>
        </div>
        <div class="stat-body">
          <span class="stat-value">{stats.izin_hari_ini}</span>
          <span class="stat-label">Izin</span>
        </div>
        <div class="stat-trend stat-trend-warning">Hari ini</div>
      </div>

      <div class="stat-card stat-sakit">
        <div class="stat-icon-wrap">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
          </svg>
        </div>
        <div class="stat-body">
          <span class="stat-value">{stats.sakit_hari_ini}</span>
          <span class="stat-label">Sakit</span>
        </div>
        <div class="stat-trend stat-trend-warning">Hari ini</div>
      </div>

      <div class="stat-card stat-alpha">
        <div class="stat-icon-wrap">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
          </svg>
        </div>
        <div class="stat-body">
          <span class="stat-value">{stats.alpha_hari_ini}</span>
          <span class="stat-label">Alpha</span>
        </div>
        <div class="stat-trend stat-trend-danger">Tidak hadir</div>
      </div>

    </div>

    <!-- ── Attendance rate bar ── -->
    <div class="rate-card">
      <div class="rate-header">
        <div class="rate-title">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
          </svg>
          Tingkat Kehadiran Hari Ini
        </div>
        <span class="rate-pct">{attendanceRate}%</span>
      </div>
      <div class="rate-bar-track">
        <div class="rate-bar-fill" style="width:{attendanceRate}%"></div>
      </div>
      <div class="rate-legend">
        <span class="legend-item legend-hadir">
          <span class="legend-dot"></span>Hadir {stats.hadir_hari_ini}
        </span>
        <span class="legend-item legend-izin">
          <span class="legend-dot"></span>Izin {stats.izin_hari_ini}
        </span>
        <span class="legend-item legend-sakit">
          <span class="legend-dot"></span>Sakit {stats.sakit_hari_ini}
        </span>
        <span class="legend-item legend-alpha">
          <span class="legend-dot"></span>Alpha {stats.alpha_hari_ini}
        </span>
      </div>
    </div>

    <!-- ── Quick access ── -->
    <div class="quick-section">
      <div class="section-header">
        <h2>Akses Cepat</h2>
        <span class="section-sub">Navigasi ke fitur utama</span>
      </div>
      <div class="shortcuts-grid">
        {#each stats.shortcuts as s}
          <a href={s.path} class="shortcut-card">
            <div class="shortcut-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
              </svg>
            </div>
            <span>{s.label}</span>
          </a>
        {/each}
      </div>
    </div>

  {/if}

</AppLayout>

<style>
  /* ── Header ── */
  .dash-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 28px; gap: 16px; flex-wrap: wrap;
  }

  .greeting-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 11px; border-radius: 999px;
    background: rgba(110,231,183,.08);
    border: 1px solid rgba(110,231,183,.18);
    font-size: .7rem; font-weight: 700;
    color: #6ee7b7; text-transform: uppercase; letter-spacing: .07em;
    margin-bottom: 10px;
  }
  .greeting-dot {
    width: 5px; height: 5px; border-radius: 50%;
    background: #34d399;
    animation: pulse 2s ease-in-out infinite;
  }
  @keyframes pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.5; transform:scale(.8); }
  }

  .dash-header-left h1 {
    font-size: 1.6rem; font-weight: 800;
    color: #f9fafb; letter-spacing: -.03em;
    display: flex; align-items: center; gap: 8px; margin-bottom: 4px;
  }
  .wave { font-size: 1.4rem; }
  .dash-subtitle { font-size: .83rem; color: #6b7280; }

  .quick-action-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 18px;
    background: linear-gradient(135deg, #34d399, #059669);
    color: #0a2218; font-size: .85rem; font-weight: 700;
    border-radius: 10px; text-decoration: none;
    box-shadow: 0 4px 16px rgba(52,211,153,.25);
    transition: transform .15s, box-shadow .15s;
    white-space: nowrap;
  }
  .quick-action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 24px rgba(52,211,153,.35);
    text-decoration: none; color: #0a2218;
  }

  /* ── Loading ── */
  .dash-loading {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 12px; padding: 80px 24px;
    color: #4b5563; font-size: .875rem;
  }
  .dash-spinner {
    width: 36px; height: 36px;
    border: 3px solid rgba(110,231,183,.1);
    border-top-color: #6ee7b7;
    border-radius: 50%;
    animation: spin .7s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ── Stats Grid ── */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
  }

  .stat-card {
    background: #161f2e;
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 14px;
    padding: 18px 20px;
    display: flex; flex-direction: column; gap: 10px;
    position: relative; overflow: hidden;
    transition: transform .2s, box-shadow .2s, border-color .2s;
  }
  .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 28px rgba(0,0,0,.35);
  }
  /* top accent bar */
  .stat-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 2px;
    border-radius: 14px 14px 0 0;
    opacity: .7;
  }
  .stat-total::before  { background: linear-gradient(90deg,#6ee7b7,#34d399); }
  .stat-absen::before  { background: linear-gradient(90deg,#60a5fa,#3b82f6); }
  .stat-hadir::before  { background: linear-gradient(90deg,#34d399,#059669); }
  .stat-izin::before   { background: linear-gradient(90deg,#fbbf24,#f59e0b); }
  .stat-sakit::before  { background: linear-gradient(90deg,#fb923c,#ea580c); }
  .stat-alpha::before  { background: linear-gradient(90deg,#f87171,#ef4444); }

  /* glow on hover */
  .stat-total:hover  { border-color: rgba(110,231,183,.2);  box-shadow: 0 8px 28px rgba(110,231,183,.08); }
  .stat-absen:hover  { border-color: rgba(96,165,250,.2);   box-shadow: 0 8px 28px rgba(96,165,250,.08); }
  .stat-hadir:hover  { border-color: rgba(52,211,153,.25);  box-shadow: 0 8px 28px rgba(52,211,153,.1); }
  .stat-izin:hover   { border-color: rgba(251,191,36,.2);   box-shadow: 0 8px 28px rgba(251,191,36,.08); }
  .stat-sakit:hover  { border-color: rgba(251,146,60,.2);   box-shadow: 0 8px 28px rgba(251,146,60,.08); }
  .stat-alpha:hover  { border-color: rgba(248,113,113,.2);  box-shadow: 0 8px 28px rgba(248,113,113,.08); }

  .stat-icon-wrap {
    width: 36px; height: 36px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .stat-total  .stat-icon-wrap { background: rgba(110,231,183,.1); color: #6ee7b7; }
  .stat-absen  .stat-icon-wrap { background: rgba(96,165,250,.1);  color: #60a5fa; }
  .stat-hadir  .stat-icon-wrap { background: rgba(52,211,153,.1);  color: #34d399; }
  .stat-izin   .stat-icon-wrap { background: rgba(251,191,36,.1);  color: #fbbf24; }
  .stat-sakit  .stat-icon-wrap { background: rgba(251,146,60,.1);  color: #fb923c; }
  .stat-alpha  .stat-icon-wrap { background: rgba(248,113,113,.1); color: #f87171; }

  .stat-body {
    display: flex; flex-direction: column; gap: 3px;
  }
  .stat-value {
    font-size: 2rem; font-weight: 800;
    color: #f9fafb; line-height: 1; letter-spacing: -.03em;
  }
  .stat-label {
    font-size: .72rem; font-weight: 600;
    color: #6b7280; text-transform: uppercase; letter-spacing: .05em;
  }

  .stat-trend {
    display: inline-flex; align-items: center;
    font-size: .7rem; font-weight: 600;
    padding: 2px 8px; border-radius: 999px; width: fit-content;
  }
  .stat-trend-neutral { background: rgba(110,231,183,.08); color: #6ee7b7; }
  .stat-trend-info    { background: rgba(96,165,250,.1);   color: #60a5fa; }
  .stat-trend-success { background: rgba(52,211,153,.12);  color: #34d399; }
  .stat-trend-warning { background: rgba(251,191,36,.1);   color: #fbbf24; }
  .stat-trend-danger  { background: rgba(248,113,113,.1);  color: #f87171; }

  /* ── Rate Card ── */
  .rate-card {
    background: #161f2e;
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 20px;
  }

  .rate-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
  }
  .rate-title {
    display: flex; align-items: center; gap: 7px;
    font-size: .85rem; font-weight: 700; color: #d1fae5;
  }
  .rate-title svg { color: #6ee7b7; }
  .rate-pct {
    font-size: 1.4rem; font-weight: 800;
    color: #6ee7b7; letter-spacing: -.02em;
  }

  .rate-bar-track {
    height: 8px;
    background: rgba(255,255,255,.05);
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 14px;
  }
  .rate-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #34d399, #6ee7b7);
    border-radius: 999px;
    transition: width .6s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 0 10px rgba(52,211,153,.4);
  }

  .rate-legend {
    display: flex; gap: 16px; flex-wrap: wrap;
  }
  .legend-item {
    display: flex; align-items: center; gap: 5px;
    font-size: .75rem; font-weight: 500; color: #6b7280;
  }
  .legend-dot {
    width: 8px; height: 8px; border-radius: 2px; flex-shrink: 0;
  }
  .legend-hadir .legend-dot { background: #34d399; }
  .legend-izin  .legend-dot { background: #fbbf24; }
  .legend-sakit .legend-dot { background: #fb923c; }
  .legend-alpha .legend-dot { background: #f87171; }

  /* ── Quick Section ── */
  .quick-section { }

  .section-header {
    display: flex; align-items: baseline; gap: 10px;
    margin-bottom: 14px;
  }
  .section-header h2 {
    font-size: 1rem; font-weight: 700; color: #f9fafb;
  }
  .section-sub {
    font-size: .75rem; color: #4b5563;
  }

  .shortcuts-grid {
    display: flex; gap: 10px; flex-wrap: wrap;
  }

  .shortcut-card {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 18px;
    background: #161f2e;
    border: 1px solid rgba(110,231,183,.1);
    border-radius: 10px;
    color: #a7f3d0; font-size: .855rem; font-weight: 600;
    text-decoration: none;
    transition: background .15s, border-color .15s, transform .15s, color .15s;
  }
  .shortcut-card:hover {
    background: rgba(110,231,183,.1);
    border-color: rgba(110,231,183,.25);
    color: #6ee7b7;
    transform: translateY(-1px);
    text-decoration: none;
  }
  .shortcut-arrow {
    width: 26px; height: 26px;
    border-radius: 6px;
    background: rgba(110,231,183,.1);
    display: flex; align-items: center; justify-content: center;
    color: #6ee7b7; flex-shrink: 0;
    transition: background .15s;
  }
  .shortcut-card:hover .shortcut-arrow {
    background: rgba(110,231,183,.2);
  }

  /* ── Responsive ── */
  @media (max-width: 640px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .dash-header-right { width: 100%; }
    .quick-action-btn  { width: 100%; justify-content: center; }
  }
</style>
