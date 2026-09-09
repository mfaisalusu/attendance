<script>
  import { onMount } from 'svelte';
  import AppLayout from '../../layouts/AppLayout.svelte';
  import Spinner   from '../../components/common/Spinner.svelte';
  import { attendanceApi } from '../../../infrastructure/api/attendanceApi.js';
  import { masterService }  from '../../../application/services/masterService.js';
  import { monthName }      from '../../../core/utils/format.js';

  const currentYear  = new Date().getFullYear();
  const currentMonth = new Date().getMonth() + 1;

  let year   = currentYear;
  let month  = currentMonth;
  let departmentId = '';
  let courseId     = '';
  let classId      = '';
  let semesterId   = '';

  let departments = [], courses = [], classes = [], semesters = [];
  let recapData = null;
  let loading = false;
  let error = '';
  let hasSearched = false;

  const years = Array.from({ length: 5 }, (_, i) => currentYear - i);
  const months = Array.from({ length: 12 }, (_, i) => ({ value: i + 1, label: monthName(i + 1) }));

  onMount(async () => {
    [departments, courses, classes, semesters] = await Promise.all([
      masterService.getDepartments(),
      masterService.getCourses(),
      masterService.getClasses(),
      masterService.getSemesters(),
    ]);
  });

  async function loadRecap() {
    loading = true;
    error = '';
    hasSearched = true;

    const res = await attendanceApi.recap({
      year,
      month,
      department_id: departmentId || undefined,
      course_id:     courseId     || undefined,
      class_id:      classId      || undefined,
      semester_id:   semesterId   || undefined,
    });

    if (res?.ok) {
      recapData = res.data.data;
    } else {
      error = 'Gagal memuat rekap absensi.';
    }
    loading = false;
  }
</script>

<svelte:head><title>Rekap Absensi — Bulanan</title></svelte:head>

<AppLayout currentPath="/attendance/recap">
  <div class="page-header">
    <h1>Rekap Absensi Bulanan</h1>
  </div>

  <!-- Filters -->
  <div class="filter-bar">
    <div class="form-group">
      <label for="r-month">Bulan</label>
      <select id="r-month" class="form-control" bind:value={month}>
        {#each months as m}<option value={m.value}>{m.label}</option>{/each}
      </select>
    </div>
    <div class="form-group">
      <label for="r-year">Tahun</label>
      <select id="r-year" class="form-control" bind:value={year}>
        {#each years as y}<option value={y}>{y}</option>{/each}
      </select>
    </div>
    <div class="form-group">
      <label for="r-dept">Jurusan</label>
      <select id="r-dept" class="form-control" bind:value={departmentId}>
        <option value="">Semua</option>
        {#each departments as d}<option value={d.id}>{d.name}</option>{/each}
      </select>
    </div>
    <div class="form-group">
      <label for="r-course">Mata Kuliah</label>
      <select id="r-course" class="form-control" bind:value={courseId}>
        <option value="">Semua</option>
        {#each courses as c}<option value={c.id}>{c.name}</option>{/each}
      </select>
    </div>
    <div class="form-group">
      <label for="r-class">Kelas</label>
      <select id="r-class" class="form-control" bind:value={classId}>
        <option value="">Semua</option>
        {#each classes as cl}<option value={cl.id}>{cl.name}</option>{/each}
      </select>
    </div>
    <div class="form-group">
      <label for="r-sem">Semester</label>
      <select id="r-sem" class="form-control" bind:value={semesterId}>
        <option value="">Semua</option>
        {#each semesters as s}<option value={s.id}>{s.name}</option>{/each}
      </select>
    </div>
    <div class="form-group" style="justify-content:flex-end">
      <!-- svelte-ignore a11y-label-has-associated-control -->
      <label aria-hidden="true">&nbsp;</label>
      <button class="btn btn-primary" on:click={loadRecap} disabled={loading}>
        {loading ? 'Memuat...' : '🔍 Tampilkan Rekap'}
      </button>
    </div>
  </div>

  {#if loading}
    <Spinner />
  {:else if error}
    <div class="alert alert-error" role="alert">{error}</div>
  {:else if recapData}
    <!-- Summary -->
    <div class="stats-grid" style="margin-bottom:20px">
      <div class="stat-card">
        <span class="stat-value">{recapData.summary.total_mahasiswa}</span>
        <span class="stat-label">Total Mahasiswa</span>
      </div>
      <div class="stat-card success">
        <span class="stat-value">{recapData.summary.total_hadir}</span>
        <span class="stat-label">Total Hadir</span>
      </div>
      <div class="stat-card warning">
        <span class="stat-value">{recapData.summary.total_izin}</span>
        <span class="stat-label">Total Izin</span>
      </div>
      <div class="stat-card warning">
        <span class="stat-value">{recapData.summary.total_sakit}</span>
        <span class="stat-label">Total Sakit</span>
      </div>
      <div class="stat-card danger">
        <span class="stat-value">{recapData.summary.total_alpha}</span>
        <span class="stat-label">Total Alpha</span>
      </div>
    </div>

    <div class="card">
      <h2 style="font-size:.95rem;font-weight:600;margin-bottom:16px">
        Rekap {monthName(recapData.month)} {recapData.year}
      </h2>

      {#if recapData.rows.length === 0}
        <div class="empty-wrap">
          <p>Tidak ada data absensi untuk periode ini.</p>
        </div>
      {:else}
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>NIP</th>
                <th>Nama</th>
                <th style="text-align:center">Hadir</th>
                <th style="text-align:center">Izin</th>
                <th style="text-align:center">Sakit</th>
                <th style="text-align:center">Alpha</th>
                <th style="text-align:center">Total</th>
                <th style="text-align:center">% Hadir</th>
              </tr>
            </thead>
            <tbody>
              {#each recapData.rows as row, i (row.student_id)}
                <tr>
                  <td style="color:var(--gray-400)">{i + 1}</td>
                  <td><code style="font-size:.8rem">{row.nip}</code></td>
                  <td style="font-weight:500">{row.name}</td>
                  <td style="text-align:center"><span class="badge badge-hadir">{row.hadir}</span></td>
                  <td style="text-align:center"><span class="badge badge-izin">{row.izin}</span></td>
                  <td style="text-align:center"><span class="badge badge-sakit">{row.sakit}</span></td>
                  <td style="text-align:center"><span class="badge badge-alpha">{row.alpha}</span></td>
                  <td style="text-align:center;font-weight:600">{row.total_pertemuan}</td>
                  <td style="text-align:center">
                    <span style="font-weight:600;color:{row.persentase >= 75 ? 'var(--success)' : 'var(--danger)'}">
                      {row.persentase}%
                    </span>
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      {/if}
    </div>
  {:else if !hasSearched}
    <div class="alert alert-info" role="status">
      Pilih periode dan klik <strong>Tampilkan Rekap</strong> untuk melihat rekap absensi.
    </div>
  {/if}
</AppLayout>
