<script>
  import { onMount } from 'svelte';
  import AppLayout from '../../layouts/AppLayout.svelte';
  import Spinner   from '../../components/common/Spinner.svelte';
  import Toast     from '../../components/common/Toast.svelte';
  import { attendanceApi } from '../../../infrastructure/api/attendanceApi.js';
  import { masterService }  from '../../../application/services/masterService.js';
  import { today, formatDate } from '../../../core/utils/format.js';

  let date          = today();
  let departmentId  = '';
  let courseId      = '';
  let classId       = '';
  let semesterId    = '';

  let departments = [], courses = [], classes = [], semesters = [];
  let attendanceData = null;   // { date, students, summary }
  let loading = false;
  let saving  = false;
  let error   = '';
  let toastMsg = '', toastType = 'success', toastVisible = false;

  // Local map: student_id → current status choice
  let statusMap = {};

  function showToast(msg, type = 'success') {
    toastMsg = msg; toastType = type; toastVisible = true;
  }

  onMount(async () => {
    [departments, courses, classes, semesters] = await Promise.all([
      masterService.getDepartments(),
      masterService.getCourses(),
      masterService.getClasses(),
      masterService.getSemesters(),
    ]);
  });

  async function loadAttendance() {
    if (!courseId || !classId) {
      attendanceData = null;
      return;
    }
    loading = true;
    error = '';

    const res = await attendanceApi.list({
      date,
      department_id: departmentId || undefined,
      course_id:     courseId     || undefined,
      class_id:      classId      || undefined,
      semester_id:   semesterId   || undefined,
    });

    if (res?.ok) {
      attendanceData = res.data.data;
      // Initialise statusMap from existing attendance records
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

  $: absenCount  = Object.values(statusMap).filter(Boolean).length;
  $: totalCount  = attendanceData?.students?.length ?? 0;
  $: belumCount  = totalCount - absenCount;

  async function saveAttendance() {
    const items = Object.entries(statusMap)
      .filter(([, status]) => status !== '')
      .map(([student_id, status]) => ({ student_id: Number(student_id), status }));

    if (items.length === 0) {
      showToast('Belum ada absensi yang diisi.', 'error');
      return;
    }

    saving = true;
    const res = await attendanceApi.save({ date, attendance: items });
    saving = false;

    if (res?.ok) {
      showToast('Absensi berhasil disimpan.');
      loadAttendance(); // refresh counts
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

  <!-- Filters -->
  <div class="filter-bar">
    <div class="form-group">
      <label for="a-date">Tanggal</label>
      <input id="a-date" type="date" class="form-control" bind:value={date} on:change={loadAttendance} />
    </div>
    <div class="form-group">
      <label for="a-dept">Jurusan</label>
      <select id="a-dept" class="form-control" bind:value={departmentId} on:change={loadAttendance}>
        <option value="">Semua</option>
        {#each departments as d}<option value={d.id}>{d.name}</option>{/each}
      </select>
    </div>
    <div class="form-group">
      <label for="a-course">Mata Kuliah <span style="color:var(--danger)">*</span></label>
      <select id="a-course" class="form-control" bind:value={courseId} on:change={loadAttendance}>
        <option value="">Pilih Mata Kuliah</option>
        {#each courses as c}<option value={c.id}>{c.name}</option>{/each}
      </select>
    </div>
    <div class="form-group">
      <label for="a-class">Kelas <span style="color:var(--danger)">*</span></label>
      <select id="a-class" class="form-control" bind:value={classId} on:change={loadAttendance}>
        <option value="">Pilih Kelas</option>
        {#each classes as cl}<option value={cl.id}>{cl.name}</option>{/each}
      </select>
    </div>
    <div class="form-group">
      <label for="a-sem">Semester</label>
      <select id="a-sem" class="form-control" bind:value={semesterId} on:change={loadAttendance}>
        <option value="">Semua</option>
        {#each semesters as s}<option value={s.id}>{s.name}</option>{/each}
      </select>
    </div>
  </div>

  {#if !courseId || !classId}
    <div class="alert alert-info" role="status">
      Pilih <strong>Mata Kuliah</strong> dan <strong>Kelas</strong> untuk menampilkan daftar mahasiswa.
    </div>
  {:else if loading}
    <Spinner />
  {:else if error}
    <div class="alert alert-error" role="alert">{error}</div>
  {:else if attendanceData}
    <!-- Summary indicator -->
    <div class="attendance-summary" role="status">
      <span>📅 <strong>{formatDate(date)}</strong></span>
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
          <p>Tidak ada mahasiswa untuk filter yang dipilih.</p>
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
                      {#each ['hadir','izin','sakit','alpha'] as status}
                        <label>
                          <input
                            type="radio"
                            name="attendance-{s.id}"
                            value={status}
                            bind:group={statusMap[s.id]}
                          />
                          {status.charAt(0).toUpperCase() + status.slice(1)}
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
