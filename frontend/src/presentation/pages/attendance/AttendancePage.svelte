<script>
  import { onMount } from 'svelte';
  import { tick } from 'svelte';
  import AppLayout from '../../layouts/AppLayout.svelte';
  import Spinner   from '../../components/common/Spinner.svelte';
  import Toast     from '../../components/common/Toast.svelte';
  import { attendanceApi } from '../../../infrastructure/api/attendanceApi.js';
  import { masterService }  from '../../../application/services/masterService.js';
  import { today, formatDate } from '../../../core/utils/format.js';

  let date     = today();
  let classId  = '';
  let courseId = '';

  let classes        = [];
  let allCourses     = [];   // semua courses milik user
  let attendanceData = null;
  let loading        = false;
  let saving         = false;
  let error          = '';
  let statusMap      = {};

  let toastMsg = '', toastType = 'success', toastVisible = false;
  function showToast(msg, type = 'success') { toastMsg = msg; toastType = type; toastVisible = true; }

  onMount(async () => {
    [classes, allCourses] = await Promise.all([
      masterService.getClasses(),
      masterService.getCourses(),
    ]);
    // Default ke id terkecil
    if (classes.length > 0) {
      classId = String(Math.min(...classes.map(c => c.id)));
    }
    await loadAttendance();
  });

  // Mata kuliah yang tersedia sesuai kelas yang dipilih
  $: selectedClassObj  = classes.find(c => String(c.id) === String(classId));
  $: coursesForClass   = selectedClassObj?.course_ids?.length
    ? allCourses.filter(c => selectedClassObj.course_ids.includes(c.id))
    : [];

  // Saat coursesForClass berubah (ganti kelas), default courseId ke id terkecil
  $: if (coursesForClass.length > 0) {
    const minCourseId = String(Math.min(...coursesForClass.map(c => c.id)));
    if (!courseId || !coursesForClass.find(c => String(c.id) === courseId)) {
      courseId = minCourseId;
    }
  }

  // Saat kelas berubah: reset courseId, tunggu reactive settle, lalu load
  async function onClassChange() {
    courseId = '';
    await tick();
    loadAttendance();
  }

  async function loadAttendance() {
    if (!classId) { attendanceData = null; return; }
    loading = true;
    error   = '';

    const res = await attendanceApi.list({
      date,
      class_id:  classId  || undefined,
      course_id: courseId || undefined,
    });

    if (res?.ok) {
      attendanceData = res.data.data;
      statusMap = {};
      for (const s of attendanceData.students) {
        statusMap[s.id] = s.attendance?.status ?? '';
      }
    } else {
      error = 'Gagal memuat data absensi.';
    }
    loading = false;
  }

  function setAllHadir() {
    if (!attendanceData) return;
    const next = {};
    for (const s of attendanceData.students) next[s.id] = 'hadir';
    statusMap = next;
  }

  $: absenCount = Object.values(statusMap).filter(Boolean).length;
  $: totalCount = attendanceData?.students?.length ?? 0;
  $: belumCount = totalCount - absenCount;

  async function saveAttendance() {
    const items = Object.entries(statusMap)
      .filter(([, s]) => s !== '')
      .map(([student_id, status]) => ({ student_id: Number(student_id), status }));

    if (items.length === 0) { showToast('Belum ada absensi yang diisi.', 'error'); return; }
    if (!courseId)          { showToast('Pilih mata kuliah terlebih dahulu.', 'error'); return; }

    saving = true;
    const res = await attendanceApi.save({
      date,
      course_id:  Number(courseId),
      attendance: items,
    });
    saving = false;

    if (res?.ok) {
      showToast('Absensi berhasil disimpan.');
      await loadAttendance();
    } else {
      showToast(res?.data?.message ?? 'Gagal menyimpan absensi.', 'error');
    }
  }
</script>

<svelte:head><title>Absensi — Harian</title></svelte:head>

<AppLayout currentPath="/attendance">
  <div class="page-header">
    <h1>Absensi Harian</h1>
  </div>

  <!-- Filter bar -->
  <div class="filter-bar">
    <div class="form-group">
      <label for="a-date">Tanggal</label>
      <input id="a-date" type="date" class="form-control"
        bind:value={date} on:change={loadAttendance} />
    </div>

    <div class="form-group">
      <label for="a-class">Kelas <span style="color:var(--danger)">*</span></label>
      <select id="a-class" class="form-control" bind:value={classId} on:change={onClassChange}>
        <option value="" disabled>Pilih kelas</option>
        {#each classes as cl}
          <option value={String(cl.id)}>{cl.code}</option>
        {/each}
      </select>
    </div>

    <div class="form-group">
      <label for="a-course">Mata Kuliah</label>
      <select id="a-course" class="form-control" bind:value={courseId}
        on:change={loadAttendance} disabled={!classId || coursesForClass.length === 0}>
        {#each coursesForClass as c}
          <option value={String(c.id)}>{c.code} — {c.name}</option>
        {/each}
      </select>
      {#if classId && coursesForClass.length === 0}
        <small class="hint">Tidak ada mata kuliah untuk kelas ini.</small>
      {/if}
    </div>
  </div>

  {#if !classId}
    <div class="alert alert-info" role="status">
      Pilih <strong>Kelas</strong> untuk menampilkan daftar mahasiswa.
    </div>
  {:else if loading}
    <Spinner />
  {:else if error}
    <div class="alert alert-error" role="alert">{error}</div>
  {:else if attendanceData}
    <!-- Summary bar -->
    <div class="attendance-summary" role="status">
      <span>📅 <strong>{formatDate(date)}</strong></span>
      {#if selectedClassObj}
        <span>🏫 <strong>{selectedClassObj.code}</strong></span>
      {/if}
      <span>Total: <strong>{totalCount}</strong></span>
      <span style="color:#15803d">✅ Diabsen: <strong>{absenCount}</strong></span>
      {#if belumCount > 0}
        <span style="color:#dc2626">⚠️ Belum: <strong>{belumCount}</strong></span>
      {/if}
    </div>

    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px">
        <h2 style="font-size:.95rem;font-weight:600">Daftar Mahasiswa</h2>
        <button class="btn btn-success btn-sm" on:click={setAllHadir}>✓ Semua Hadir</button>
      </div>

      {#if attendanceData.students.length === 0}
        <div class="empty-wrap">
          <p>Tidak ada mahasiswa di kelas ini.</p>
        </div>
      {:else}
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th style="width:36px">#</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Kehadiran</th>
              </tr>
            </thead>
            <tbody>
              {#each attendanceData.students as s, i (s.id)}
                <tr>
                  <td style="color:var(--gray-400)">{i + 1}</td>
                  <td><code style="font-size:.8rem">{s.nip}</code></td>
                  <td style="font-weight:500">{s.name}</td>
                  <td>
                    <div class="radio-group" role="radiogroup" aria-label="Status kehadiran {s.name}">
                      {#each ['hadir','izin','sakit','alpha'] as st}
                        <label>
                          <input type="radio" name="att-{s.id}" value={st}
                            bind:group={statusMap[s.id]} />
                          {st.charAt(0).toUpperCase() + st.slice(1)}
                        </label>
                      {/each}
                    </div>
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:16px">
          <button class="btn btn-primary" on:click={saveAttendance} disabled={saving}>
            {saving ? 'Menyimpan...' : '💾 Simpan Absensi'}
          </button>
        </div>
      {/if}
    </div>
  {/if}
</AppLayout>

<Toast bind:visible={toastVisible} message={toastMsg} type={toastType} />

<style>
  .hint { color: var(--gray-400); font-size: .75rem; margin-top: 3px; display: block; }
</style>
