<script>
  import { onMount, tick } from 'svelte';
  import AppLayout from '../../layouts/AppLayout.svelte';
  import { attendanceApi } from '../../../infrastructure/api/attendanceApi.js';
  import { masterService }  from '../../../application/services/masterService.js';
  import { monthName }      from '../../../core/utils/format.js';

  const currentYear  = new Date().getFullYear();
  const currentMonth = new Date().getMonth() + 1;

  let year     = currentYear;
  let month    = currentMonth;
  let classId  = '';
  let courseId = '';

  let classes    = [];
  let allCourses = [];
  let years      = [];

  let recapData   = null;
  let loading     = false;
  let error       = '';
  let hasSearched = false;

  const months = Array.from({ length: 12 }, (_, i) => ({ value: i + 1, label: monthName(i + 1) }));

  onMount(async () => {
    [classes, allCourses, years] = await Promise.all([
      masterService.getClasses(),
      masterService.getCourses(),
      masterService.getYears(),
    ]);
    if (years.length > 0 && !years.find(y => y.id === year)) {
      year = years[years.length - 1].id;
    }
    if (classes.length > 0) {
      classId = String(Math.min(...classes.map(c => c.id)));
    }
  });

  $: selectedClassObj = classes.find(c => String(c.id) === String(classId));
  $: coursesForClass  = selectedClassObj?.course_ids?.length
    ? allCourses.filter(c => selectedClassObj.course_ids.includes(c.id))
    : [];

  $: if (coursesForClass.length > 0) {
    const minCourseId = String(Math.min(...coursesForClass.map(c => c.id)));
    if (!courseId || !coursesForClass.find(c => String(c.id) === courseId)) {
      courseId = minCourseId;
    }
  }

  async function onClassChange() {
    courseId = ''; recapData = null; hasSearched = false;
    await tick();
  }

  async function loadRecap() {
    if (!classId) { error = 'Pilih kelas terlebih dahulu.'; return; }
    loading = true; error = ''; hasSearched = true;
    const res = await attendanceApi.recap({
      year, month,
      class_id:  classId  || undefined,
      course_id: courseId || undefined,
    });
    if (res?.ok) { recapData = res.data.data; }
    else         { error = 'Gagal memuat rekap absensi.'; }
    loading = false;
  }

  $: dayNumbers = recapData
    ? Array.from({ length: recapData.days_in_month }, (_, i) => i + 1)
    : [];

  // Weekend detection (simple: Saturday=6, Sunday=0)
  function isWeekend(year, month, day) {
    const d = new Date(year, month - 1, day).getDay();
    return d === 0 || d === 6;
  }

  // Status config
  const statusCfg = {
    H: { label: 'Hadir', color: '#34d399', bg: 'rgba(52,211,153,.15)'  },
    I: { label: 'Izin',  color: '#fbbf24', bg: 'rgba(251,191,36,.15)'  },
    S: { label: 'Sakit', color: '#60a5fa', bg: 'rgba(96,165,250,.15)'  },
    A: { label: 'Alpha', color: '#f87171', bg: 'rgba(248,113,113,.15)' },
  };

  // Per-row summary counts
  function rowCounts(row) {
    const vals = Object.values(row.days);
    return {
      H: vals.filter(v => v === 'H').length,
      I: vals.filter(v => v === 'I').length,
      S: vals.filter(v => v === 'S').length,
      A: vals.filter(v => v === 'A').length,
    };
  }

  // Selected course name
  $: selectedCourseName = courseId
    ? (coursesForClass.find(c => String(c.id) === String(courseId))?.name ?? '')
    : '';
</script>

<svelte:head><title>Rekap Absensi — Absensi</title></svelte:head>

<AppLayout currentPath="/attendance/recap">

  <!-- Header -->
  <div class="page-header">
    <div class="header-left">
      <div class="page-eyebrow">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <line x1="18" y1="20" x2="18" y2="10"/>
          <line x1="12" y1="20" x2="12" y2="4"/>
          <line x1="6"  y1="20" x2="6"  y2="14"/>
        </svg>
        Laporan Kehadiran
      </div>
      <h1>Rekap Absensi Bulanan</h1>
    </div>
  </div>

  <!-- Filter card -->
  <div class="filter-card">
    <div class="filter-grid">
      <div class="form-group">
        <label for="r-class">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
          Kelas
        </label>
        <select id="r-class" class="form-control" bind:value={classId} on:change={onClassChange}>
          <option value="" disabled>Pilih kelas</option>
          {#each classes as cl}
            <option value={String(cl.id)}>{cl.code} — {cl.name}</option>
          {/each}
        </select>
      </div>

      <div class="form-group">
        <label for="r-course">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
          Mata Kuliah
        </label>
        <select id="r-course" class="form-control" bind:value={courseId}
          disabled={!classId || coursesForClass.length === 0}>
          {#each coursesForClass as c}
            <option value={String(c.id)}>{c.code} — {c.name}</option>
          {/each}
        </select>
        {#if classId && coursesForClass.length === 0}
          <small class="hint-text">Tidak ada mata kuliah untuk kelas ini.</small>
        {/if}
      </div>

      <div class="form-group">
        <label for="r-month">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Bulan
        </label>
        <select id="r-month" class="form-control" bind:value={month}>
          {#each months as m}
            <option value={m.value}>{m.label}</option>
          {/each}
        </select>
      </div>

      <div class="form-group">
        <label for="r-year">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Tahun
        </label>
        <select id="r-year" class="form-control" bind:value={year}>
          {#each years as y}
            <option value={y.id}>{y.name}</option>
          {/each}
        </select>
      </div>
    </div>

    <div class="filter-footer">
      <button class="search-btn" on:click={loadRecap} disabled={loading || !classId}>
        {#if loading}
          <span class="btn-spinner"></span>
          Memuat...
        {:else}
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          Tampilkan Rekap
        {/if}
      </button>
    </div>
  </div>

  <!-- Loading -->
  {#if loading}
    <div class="state-wrap">
      <div class="state-spinner"></div>
      <span>Memuat rekap absensi...</span>
    </div>

  <!-- Error -->
  {:else if error}
    <div class="alert alert-error" role="alert">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {error}
    </div>

  <!-- Data -->
  {:else if recapData}
    <!-- Recap title strip -->
    <div class="recap-strip">
      <div class="recap-strip-left">
        <div class="recap-period">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          {monthName(recapData.month)} {recapData.year}
        </div>
        {#if selectedClassObj}
          <span class="recap-class">{selectedClassObj.code}</span>
        {/if}
        {#if selectedCourseName}
          <span class="recap-course">{selectedCourseName}</span>
        {/if}
      </div>
      <!-- Legend -->
      <div class="legend">
        {#each Object.entries(statusCfg) as [key, cfg]}
          <span class="legend-item" style="color:{cfg.color}">
            <span class="legend-dot" style="background:{cfg.color}"></span>
            {key} = {cfg.label}
          </span>
        {/each}
        <span class="legend-item" style="color:#374151">
          <span class="legend-dot" style="background:#374151"></span>
          — = Kosong
        </span>
      </div>
    </div>

    {#if recapData.rows.length === 0}
      <div class="state-wrap state-empty">
        <div class="empty-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
            <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
          </svg>
        </div>
        <p class="empty-title">Tidak ada data untuk periode ini</p>
        <p class="empty-sub">Coba ubah kelas, mata kuliah, atau periode waktu.</p>
      </div>

    {:else}
      <div class="card matrix-card">
        <div class="matrix-scroll">
          <table class="matrix-table">
            <thead>
              <tr>
                <th class="th-sticky col-no">#</th>
                <th class="th-sticky col-nip">NIP</th>
                <th class="th-sticky col-name">Nama</th>
                {#each dayNumbers as d}
                  <th class="col-day" class:col-weekend={isWeekend(recapData.year, recapData.month, d)}>
                    {d}
                  </th>
                {/each}
                <th class="col-summary">H</th>
                <th class="col-summary">I</th>
                <th class="col-summary">S</th>
                <th class="col-summary">A</th>
              </tr>
            </thead>
            <tbody>
              {#each recapData.rows as row, i (row.student_id)}
                {@const counts = rowCounts(row)}
                <tr>
                  <td class="td-sticky col-no td-muted">{i + 1}</td>
                  <td class="td-sticky col-nip">
                    <span class="nip-badge">{row.nip}</span>
                  </td>
                  <td class="td-sticky col-name">
                    <div class="name-cell">
                      <div class="row-avatar">{row.name[0]}</div>
                      <span>{row.name}</span>
                    </div>
                  </td>
                  {#each dayNumbers as d}
                    {@const val = row.days[d] ?? null}
                    {@const cfg = val ? statusCfg[val] : null}
                    <td class="col-day"
                      class:col-weekend={isWeekend(recapData.year, recapData.month, d)}>
                      {#if cfg}
                        <span class="status-cell" style="color:{cfg.color};background:{cfg.bg}">
                          {val}
                        </span>
                      {:else}
                        <span class="status-empty">—</span>
                      {/if}
                    </td>
                  {/each}
                  <!-- Summary cols -->
                  <td class="col-summary summary-h">{counts.H}</td>
                  <td class="col-summary summary-i">{counts.I}</td>
                  <td class="col-summary summary-s">{counts.S}</td>
                  <td class="col-summary summary-a">{counts.A}</td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      </div>
    {/if}

  <!-- Not yet searched -->
  {:else if !hasSearched}
    <div class="info-banner">
      <div class="info-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
        </svg>
      </div>
      <div>
        <p class="info-title">Siap Menampilkan Rekap</p>
        <p class="info-sub">Pilih kelas, mata kuliah, bulan, dan tahun — lalu klik <strong>Tampilkan Rekap</strong>.</p>
      </div>
    </div>
  {/if}

</AppLayout>

<style>
  /* ── Header ── */
  .page-eyebrow {
    display: flex; align-items: center; gap: 5px;
    font-size: .7rem; font-weight: 700; color: #6ee7b7;
    text-transform: uppercase; letter-spacing: .08em; margin-bottom: 4px;
  }
  .header-left h1 {
    font-size: 1.35rem; font-weight: 800;
    color: #f9fafb; letter-spacing: -.02em;
  }

  /* ── Filter card ── */
  .filter-card {
    background: #161f2e;
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 18px;
  }
  .filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 16px;
  }
  .filter-footer {
    display: flex; justify-content: flex-end;
    padding-top: 14px;
    border-top: 1px solid rgba(255,255,255,.05);
  }

  .hint-text { color: #4b5563; font-size: .75rem; margin-top: 4px; display: block; }

  .search-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 22px; border-radius: 10px; border: none;
    background: linear-gradient(135deg, #34d399, #059669);
    color: #0a2218; font-size: .88rem; font-weight: 700;
    cursor: pointer; font-family: inherit;
    box-shadow: 0 4px 16px rgba(52,211,153,.25);
    transition: transform .15s, box-shadow .15s, opacity .15s;
  }
  .search-btn:hover:not(:disabled) {
    transform: translateY(-1px); box-shadow: 0 6px 22px rgba(52,211,153,.35);
  }
  .search-btn:disabled { opacity: .45; cursor: not-allowed; }

  .btn-spinner {
    width: 13px; height: 13px;
    border: 2px solid rgba(10,34,24,.3);
    border-top-color: #0a2218; border-radius: 50%;
    animation: spin .6s linear infinite; flex-shrink: 0;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ── Info banner ── */
  .info-banner {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 18px 20px; border-radius: 12px;
    background: rgba(110,231,183,.05); border: 1px solid rgba(110,231,183,.12);
  }
  .info-icon {
    width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
    background: rgba(110,231,183,.08); border: 1px solid rgba(110,231,183,.15);
    display: flex; align-items: center; justify-content: center; color: #6ee7b7;
  }
  .info-title { font-size: .9rem; font-weight: 700; color: #d1fae5; margin-bottom: 3px; }
  .info-sub   { font-size: .8rem; color: #4b5563; line-height: 1.5; }
  .info-sub strong { color: #6ee7b7; }

  /* ── States ── */
  .state-wrap {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 10px;
    padding: 60px 24px; color: #4b5563; font-size: .875rem;
  }
  .state-spinner {
    width: 34px; height: 34px;
    border: 3px solid rgba(110,231,183,.1);
    border-top-color: #6ee7b7; border-radius: 50%;
    animation: spin .7s linear infinite;
  }
  .state-empty { gap: 8px; padding: 48px 24px; }
  .empty-icon {
    width: 56px; height: 56px; border-radius: 50%;
    background: rgba(110,231,183,.05); border: 1px solid rgba(110,231,183,.1);
    display: flex; align-items: center; justify-content: center;
    color: rgba(110,231,183,.3);
  }
  .empty-title { font-size: .9rem; font-weight: 600; color: #6b7280; }
  .empty-sub   { font-size: .8rem; color: #4b5563; }

  /* ── Recap strip ── */
  .recap-strip {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    padding: 12px 18px; margin-bottom: 14px;
    background: rgba(110,231,183,.05);
    border: 1px solid rgba(110,231,183,.1);
    border-radius: 10px;
  }
  .recap-strip-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  .recap-period {
    display: flex; align-items: center; gap: 6px;
    font-size: .875rem; font-weight: 700; color: #d1fae5;
  }
  .recap-period svg { color: #6ee7b7; }
  .recap-class {
    padding: 2px 10px; border-radius: 6px;
    background: rgba(96,165,250,.1); border: 1px solid rgba(96,165,250,.2);
    font-size: .72rem; font-weight: 700; color: #60a5fa;
  }
  .recap-course {
    font-size: .78rem; color: #6b7280; font-style: italic;
  }

  .legend {
    display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
  }
  .legend-item {
    display: flex; align-items: center; gap: 5px;
    font-size: .75rem; font-weight: 600; white-space: nowrap;
  }
  .legend-dot {
    width: 8px; height: 8px; border-radius: 2px;
  }

  /* ── Matrix card ── */
  .matrix-card { padding: 0; overflow: hidden; }
  .matrix-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }

  .matrix-table {
    border-collapse: collapse; min-width: 100%;
    font-size: .8rem; white-space: nowrap;
  }
  .matrix-table th {
    padding: 10px 8px;
    background: rgba(110,231,183,.04);
    color: #6b7280; font-weight: 700;
    font-size: .7rem; text-transform: uppercase; letter-spacing: .04em;
    border-bottom: 1px solid rgba(255,255,255,.06);
    text-align: center;
  }
  .matrix-table td {
    padding: 9px 8px;
    border-bottom: 1px solid rgba(255,255,255,.04);
    text-align: center; color: #d1d5db;
    vertical-align: middle;
  }
  .matrix-table tbody tr:hover { background: rgba(110,231,183,.025); }
  .matrix-table tbody tr:last-child td { border-bottom: none; }

  /* Sticky columns */
  .th-sticky, .td-sticky {
    position: sticky; z-index: 2;
    background: #161f2e;
  }
  .matrix-table thead .th-sticky { background: #161f2e; z-index: 3; }
  .col-no   { left: 0;    width: 36px; text-align: center; }
  .col-nip  { left: 36px; width: 100px; text-align: left; }
  .col-name { left: 136px; min-width: 150px; text-align: left;
    box-shadow: 4px 0 8px rgba(0,0,0,.25); }

  .col-day  { width: 28px; min-width: 28px; }
  .col-weekend { opacity: .4; }

  /* Summary cols */
  .col-summary { width: 32px; font-weight: 700; }
  .summary-h { color: #34d399; }
  .summary-i { color: #fbbf24; }
  .summary-s { color: #60a5fa; }
  .summary-a { color: #f87171; }
  .matrix-table thead .col-summary { font-size: .72rem; }

  /* Cell content */
  .status-cell {
    display: inline-flex; align-items: center; justify-content: center;
    width: 20px; height: 20px; border-radius: 4px;
    font-size: .7rem; font-weight: 800;
  }
  .status-empty { color: rgba(255,255,255,.1); font-size: .75rem; }

  .td-muted { color: #374151; font-size: .75rem; }

  .nip-badge {
    display: inline-block; padding: 2px 8px;
    background: rgba(110,231,183,.06); border: 1px solid rgba(110,231,183,.12);
    border-radius: 5px; font-family: 'Courier New', monospace;
    font-size: .73rem; font-weight: 700; color: #6ee7b7;
  }

  .name-cell {
    display: flex; align-items: center; gap: 8px;
    font-weight: 600; color: #f9fafb;
  }
  .row-avatar {
    width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #34d399, #059669);
    color: #0a2218; font-size: .65rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    text-transform: uppercase;
  }
</style>
