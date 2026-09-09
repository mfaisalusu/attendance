<script>
  import { onMount } from 'svelte';
  import AppLayout from '../../layouts/AppLayout.svelte';
  import Spinner   from '../../components/common/Spinner.svelte';
  import { attendanceApi } from '../../../infrastructure/api/attendanceApi.js';
  import { masterService }  from '../../../application/services/masterService.js';
  import { monthName }      from '../../../core/utils/format.js';

  const currentYear  = new Date().getFullYear();
  const currentMonth = new Date().getMonth() + 1;

  let year    = currentYear;
  let month   = currentMonth;
  let classId = '';

  let classes = [];
  let years   = [];

  let recapData   = null;
  let loading     = false;
  let error       = '';
  let hasSearched = false;

  const months = Array.from({ length: 12 }, (_, i) => ({ value: i + 1, label: monthName(i + 1) }));

  onMount(async () => {
    [classes, years] = await Promise.all([
      masterService.getClasses(),
      masterService.getYears(),
    ]);
    // Set default tahun ke nilai terdekat yang tersedia
    if (years.length > 0 && !years.find(y => y.id === year)) {
      year = years[years.length - 1].id;
    }
    // Default kelas ke id terkecil
    if (classes.length > 0) {
      classId = String(Math.min(...classes.map(c => c.id)));
    }
  });

  async function loadRecap() {
    if (!classId) { error = 'Pilih kelas terlebih dahulu.'; return; }
    loading     = true;
    error       = '';
    hasSearched = true;

    const res = await attendanceApi.recap({
      year,
      month,
      class_id: classId || undefined,
    });

    if (res?.ok) {
      recapData = res.data.data;
    } else {
      error = 'Gagal memuat rekap absensi.';
    }
    loading = false;
  }

  // Array of day numbers: [1, 2, ..., daysInMonth]
  $: dayNumbers = recapData
    ? Array.from({ length: recapData.days_in_month }, (_, i) => i + 1)
    : [];

  // Status color helper
  function statusColor(val) {
    if (val === 'H') return 'var(--success)';
    if (val === 'I') return '#f59e0b';
    if (val === 'S') return '#3b82f6';
    if (val === 'A') return 'var(--danger)';
    return 'var(--gray-300)';
  }

  // Selected class label
  $: selectedClass = classes.find(c => String(c.id) === String(classId));
</script>

<svelte:head><title>Rekap Absensi — Absensi</title></svelte:head>

<AppLayout currentPath="/attendance/recap">
  <div class="page-header">
    <h1>Rekap Absensi Bulanan</h1>
  </div>

  <!-- Filter bar: kelas + bulan + tahun -->
  <div class="filter-bar">
    <div class="form-group">
      <label for="r-class">Kelas <span style="color:var(--danger)">*</span></label>
      <select id="r-class" class="form-control" bind:value={classId}>
        <option value="" disabled>Pilih kelas</option>
        {#each classes as cl}
          <option value={String(cl.id)}>{cl.code} — {cl.name}</option>
        {/each}
      </select>
    </div>
    <div class="form-group">
      <label for="r-month">Bulan</label>
      <select id="r-month" class="form-control" bind:value={month}>
        {#each months as m}<option value={m.value}>{m.label}</option>{/each}
      </select>
    </div>
    <div class="form-group">
      <label for="r-year">Tahun</label>
      <select id="r-year" class="form-control" bind:value={year}>
        {#each years as y}<option value={y.id}>{y.name}</option>{/each}
      </select>
    </div>
    <div class="form-group" style="justify-content:flex-end">
      <!-- svelte-ignore a11y-label-has-associated-control -->
      <label aria-hidden="true">&nbsp;</label>
      <button class="btn btn-primary" on:click={loadRecap} disabled={loading || !classId}>
        {loading ? 'Memuat...' : '🔍 Tampilkan Rekap'}
      </button>
    </div>
  </div>

  {#if loading}
    <Spinner />
  {:else if error}
    <div class="alert alert-error" role="alert">{error}</div>
  {:else if recapData}
    <div class="card">
      <div class="recap-header">
        <div>
          <h2>{monthName(recapData.month)} {recapData.year}</h2>
          {#if selectedClass}
            <p class="recap-sub">{selectedClass.code} — {selectedClass.name}</p>
          {/if}
        </div>
        <div class="legend">
          <span class="leg-item" style="color:var(--success)">■ H = Hadir</span>
          <span class="leg-item" style="color:#f59e0b">■ I = Izin</span>
          <span class="leg-item" style="color:#3b82f6">■ S = Sakit</span>
          <span class="leg-item" style="color:var(--danger)">■ A = Alpha</span>
          <span class="leg-item" style="color:var(--gray-300)">■ — = Belum diisi</span>
        </div>
      </div>

      {#if recapData.rows.length === 0}
        <div class="empty-wrap">
          <p>Tidak ada data mahasiswa untuk kelas dan periode ini.</p>
        </div>
      {:else}
        <!-- Horizontal scroll wrapper untuk tabel lebar -->
        <div class="matrix-wrap">
          <table class="matrix-table">
            <thead>
              <tr>
                <th class="col-no">#</th>
                <th class="col-nip">NIP</th>
                <th class="col-name">Nama</th>
                {#each dayNumbers as d}
                  <th class="col-day">{d}</th>
                {/each}
              </tr>
            </thead>
            <tbody>
              {#each recapData.rows as row, i (row.student_id)}
                <tr>
                  <td class="col-no muted">{i + 1}</td>
                  <td class="col-nip"><code class="nip-code">{row.nip}</code></td>
                  <td class="col-name name-cell">{row.name}</td>
                  {#each dayNumbers as d}
                    {@const val = row.days[d] ?? null}
                    <td class="col-day day-cell"
                      style="color:{statusColor(val)};font-weight:{val ? 700 : 400}">
                      {val ?? '—'}
                    </td>
                  {/each}
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      {/if}
    </div>
  {:else if !hasSearched}
    <div class="alert alert-info" role="status">
      Pilih <strong>Kelas</strong>, bulan, dan tahun, lalu klik <strong>Tampilkan Rekap</strong>.
    </div>
  {/if}
</AppLayout>

<style>
  .recap-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
  }
  .recap-header h2 {
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 2px;
  }
  .recap-sub { color: var(--gray-500); font-size: .875rem; margin: 0; }

  .legend {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    align-items: center;
    font-size: .78rem;
  }
  .leg-item { white-space: nowrap; }

  /* Matrix table */
  .matrix-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .matrix-table {
    border-collapse: collapse;
    min-width: 100%;
    font-size: .82rem;
    white-space: nowrap;
  }
  .matrix-table th,
  .matrix-table td {
    border: 1px solid var(--gray-200);
    padding: 5px 6px;
    vertical-align: middle;
  }
  .matrix-table thead th {
    background: var(--gray-50);
    font-weight: 600;
    text-align: center;
    position: sticky;
    top: 0;
    z-index: 1;
  }

  /* Sticky columns */
  .col-no   { width: 36px; text-align: center; }
  .col-nip  { width: 90px; }
  .col-name { min-width: 140px; max-width: 200px; }
  .col-day  { width: 30px; text-align: center; font-family: monospace; }

  .col-no, .col-nip, .col-name {
    position: sticky;
    background: #fff;
    z-index: 2;
  }
  .col-no   { left: 0; }
  .col-nip  { left: 36px; }
  .col-name { left: 126px; box-shadow: 2px 0 4px rgba(0,0,0,.04); }

  .matrix-table thead .col-no,
  .matrix-table thead .col-nip,
  .matrix-table thead .col-name { background: var(--gray-50); z-index: 3; }

  .muted     { color: var(--gray-400); }
  .nip-code  { font-size: .78rem; }
  .name-cell { font-weight: 500; overflow: hidden; text-overflow: ellipsis; }

  .day-cell { font-size: .8rem; }
</style>
