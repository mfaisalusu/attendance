<script>
  import { onMount, tick } from 'svelte';
  import AppLayout from '../../layouts/AppLayout.svelte';
  import Toast     from '../../components/common/Toast.svelte';
  import { attendanceApi } from '../../../infrastructure/api/attendanceApi.js';
  import { masterService }  from '../../../application/services/masterService.js';
  import { today, formatDate } from '../../../core/utils/format.js';

  let date     = today();
  let classId  = '';
  let courseId = '';

  let classes        = [];
  let allCourses     = [];
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
    if (classes.length > 0) {
      classId = String(Math.min(...classes.map(c => c.id)));
    }
    await loadAttendance();
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
    courseId = '';
    await tick();
    loadAttendance();
  }

  async function loadAttendance() {
    if (!classId) { attendanceData = null; return; }
    loading = true; error = '';
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
    } else { error = 'Gagal memuat data absensi.'; }
    loading = false;
  }

  function setAllHadir() {
    if (!attendanceData) return;
    const next = {};
    for (const s of attendanceData.students) next[s.id] = 'hadir';
    statusMap = next;
  }

  $: absenCount = Object.values(statusMap).filter(Boolean).length;
  $: hadirCount = Object.values(statusMap).filter(v => v === 'hadir').length;
  $: izinCount  = Object.values(statusMap).filter(v => v === 'izin').length;
  $: sakitCount = Object.values(statusMap).filter(v => v === 'sakit').length;
  $: alphaCount = Object.values(statusMap).filter(v => v === 'alpha').length;
  $: totalCount = attendanceData?.students?.length ?? 0;
  $: belumCount = totalCount - absenCount;
  $: progressPct = totalCount > 0 ? Math.round((absenCount / totalCount) * 100) : 0;

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

  const statusConfig = {
    hadir: { label: 'Hadir',  color: '#34d399', bg: 'rgba(52,211,153,.12)',  border: 'rgba(52,211,153,.25)'  },
    izin:  { label: 'Izin',   color: '#fbbf24', bg: 'rgba(251,191,36,.12)',  border: 'rgba(251,191,36,.25)'  },
    sakit: { label: 'Sakit',  color: '#fb923c', bg: 'rgba(251,146,60,.12)',  border: 'rgba(251,146,60,.25)'  },
    alpha: { label: 'Alpha',  color: '#f87171', bg: 'rgba(248,113,113,.12)', border: 'rgba(248,113,113,.25)' },
  };
</script>

<svelte:head><title>Absensi Harian — Absensi</title></svelte:head>

<AppLayout currentPath="/attendance">

  <!-- Header -->
  <div class="page-header">
    <div class="header-left">
      <div class="page-eyebrow">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M9 11l3 3L22 4"/>
          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
        </svg>
        Input Kehadiran
      </div>
      <h1>Absensi Harian</h1>
    </div>
    {#if attendanceData && attendanceData.students.length > 0}
      <button class="save-btn" on:click={saveAttendance} disabled={saving}>
        {#if saving}
          <span class="btn-spinner"></span>
          Menyimpan...
        {:else}
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
            <polyline points="17 21 17 13 7 13 7 21"/>
            <polyline points="7 3 7 8 15 8"/>
          </svg>
          Simpan Absensi
        {/if}
      </button>
    {/if}
  </div>

  <!-- Filter bar -->
  <div class="filter-bar">
    <div class="form-group">
      <label for="a-date">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Tanggal
      </label>
      <input id="a-date" type="date" class="form-control"
        bind:value={date} on:change={loadAttendance} />
    </div>
    <div class="form-group">
      <label for="a-class">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        Kelas
      </label>
      <select id="a-class" class="form-control" bind:value={classId} on:change={onClassChange}>
        <option value="" disabled>Pilih kelas</option>
        {#each classes as cl}
          <option value={String(cl.id)}>{cl.code} — {cl.name}</option>
        {/each}
      </select>
    </div>
    <div class="form-group">
      <label for="a-course">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        Mata Kuliah
      </label>
      <select id="a-course" class="form-control" bind:value={courseId}
        on:change={loadAttendance} disabled={!classId || coursesForClass.length === 0}>
        {#each coursesForClass as c}
          <option value={String(c.id)}>{c.code} — {c.name}</option>
        {/each}
      </select>
      {#if classId && coursesForClass.length === 0}
        <small class="hint-text">Tidak ada mata kuliah untuk kelas ini.</small>
      {/if}
    </div>
  </div>

  <!-- No class selected -->
  {#if !classId}
    <div class="info-banner">
      <div class="info-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </div>
      <div>
        <p class="info-title">Pilih Kelas & Mata Kuliah</p>
        <p class="info-sub">Gunakan filter di atas untuk menampilkan daftar mahasiswa yang akan diabsen.</p>
      </div>
    </div>

  <!-- Loading -->
  {:else if loading}
    <div class="state-wrap">
      <div class="state-spinner"></div>
      <span>Memuat data absensi...</span>
    </div>

  <!-- Error -->
  {:else if error}
    <div class="alert alert-error" role="alert">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {error}
    </div>

  <!-- Data -->
  {:else if attendanceData}

    <!-- Summary strip -->
    <div class="summary-strip">
      <div class="summary-left">
        <div class="summary-date">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          {formatDate(date)}
        </div>
        {#if selectedClassObj}
          <div class="summary-class">{selectedClassObj.code}</div>
        {/if}
      </div>
      <div class="summary-counts">
        <span class="sc-item sc-hadir">
          <span class="sc-dot"></span>{hadirCount} Hadir
        </span>
        <span class="sc-item sc-izin">
          <span class="sc-dot"></span>{izinCount} Izin
        </span>
        <span class="sc-item sc-sakit">
          <span class="sc-dot"></span>{sakitCount} Sakit
        </span>
        <span class="sc-item sc-alpha">
          <span class="sc-dot"></span>{alphaCount} Alpha
        </span>
        {#if belumCount > 0}
          <span class="sc-item sc-belum">
            <span class="sc-dot"></span>{belumCount} Belum
          </span>
        {/if}
      </div>
    </div>

    <!-- Progress bar -->
    <div class="progress-wrap">
      <div class="progress-label">
        <span>Progress pengisian</span>
        <span class="progress-pct" class:complete={belumCount === 0}>{progressPct}%</span>
      </div>
      <div class="progress-track">
        <div class="progress-fill" style="width:{progressPct}%"
          class:complete={belumCount === 0}></div>
      </div>
    </div>

    <!-- Table card -->
    <div class="card">
      <div class="card-toolbar">
        <div class="card-toolbar-left">
          <h2>Daftar Mahasiswa</h2>
          <span class="total-chip">{totalCount} orang</span>
        </div>
        <button class="all-hadir-btn" on:click={setAllHadir} title="Tandai semua hadir">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Semua Hadir
        </button>
      </div>

      {#if attendanceData.students.length === 0}
        <div class="state-wrap state-empty">
          <div class="empty-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
            </svg>
          </div>
          <p class="empty-title">Tidak ada mahasiswa di kelas ini</p>
        </div>
      {:else}
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th style="width:40px">#</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Status Kehadiran</th>
              </tr>
            </thead>
            <tbody>
              {#each attendanceData.students as s, i (s.id)}
                {@const curStatus = statusMap[s.id]}
                <tr class:row-filled={curStatus}>
                  <td class="td-num">{i + 1}</td>
                  <td><span class="nip-badge">{s.nip}</span></td>
                  <td class="td-name">
                    <div class="student-avatar"
                      style="background: linear-gradient(135deg,
                        {curStatus === 'hadir' ? '#34d399,#059669' :
                         curStatus === 'izin'  ? '#fbbf24,#d97706' :
                         curStatus === 'sakit' ? '#fb923c,#ea580c' :
                         curStatus === 'alpha' ? '#f87171,#ef4444' :
                                                 '#374151,#1f2937'})">
                      {s.name[0]}
                    </div>
                    <span>{s.name}</span>
                  </td>
                  <td class="td-status">
                    <div class="status-pills" role="radiogroup" aria-label="Status {s.name}">
                      {#each Object.entries(statusConfig) as [st, cfg]}
                        <label
                          class="status-pill"
                          class:selected={curStatus === st}
                          style={curStatus === st
                            ? `background:${cfg.bg};border-color:${cfg.border};color:${cfg.color};`
                            : ''}
                          title={cfg.label}
                        >
                          <input type="radio" name="att-{s.id}" value={st}
                            bind:group={statusMap[s.id]} />
                          {cfg.label}
                        </label>
                      {/each}
                    </div>
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>

        <!-- Bottom save bar -->
        <div class="save-bar">
          <div class="save-bar-info">
            <span class="save-filled">{absenCount}</span>/<span>{totalCount}</span> mahasiswa telah diisi
          </div>
          <button class="save-btn" on:click={saveAttendance} disabled={saving}>
            {#if saving}
              <span class="btn-spinner"></span>
              Menyimpan...
            {:else}
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
                <polyline points="7 3 7 8 15 8"/>
              </svg>
              Simpan Absensi
            {/if}
          </button>
        </div>
      {/if}
    </div>
  {/if}

</AppLayout>

<Toast bind:visible={toastVisible} message={toastMsg} type={toastType} />

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

  /* ── Hint ── */
  .hint-text {
    color: #4b5563; font-size: .75rem;
    margin-top: 4px; display: block;
  }

  /* ── Info banner ── */
  .info-banner {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 18px 20px; border-radius: 12px;
    background: rgba(96,165,250,.06);
    border: 1px solid rgba(96,165,250,.15);
    margin-bottom: 8px;
  }
  .info-icon {
    width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
    background: rgba(96,165,250,.1);
    border: 1px solid rgba(96,165,250,.2);
    display: flex; align-items: center; justify-content: center;
    color: #60a5fa;
  }
  .info-title { font-size: .9rem; font-weight: 700; color: #93c5fd; margin-bottom: 3px; }
  .info-sub   { font-size: .8rem; color: #4b5563; line-height: 1.5; }

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
  @keyframes spin { to { transform: rotate(360deg); } }

  .state-empty { gap: 8px; padding: 40px 24px; }
  .empty-icon {
    width: 56px; height: 56px; border-radius: 50%;
    background: rgba(110,231,183,.05); border: 1px solid rgba(110,231,183,.1);
    display: flex; align-items: center; justify-content: center;
    color: rgba(110,231,183,.35);
  }
  .empty-title { font-size: .9rem; font-weight: 600; color: #6b7280; }

  /* ── Summary strip ── */
  .summary-strip {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    padding: 12px 18px; margin-bottom: 10px;
    background: rgba(110,231,183,.05);
    border: 1px solid rgba(110,231,183,.1);
    border-radius: 10px;
  }
  .summary-left { display: flex; align-items: center; gap: 10px; }
  .summary-date {
    display: flex; align-items: center; gap: 6px;
    font-size: .82rem; font-weight: 600; color: #d1fae5;
  }
  .summary-date svg { color: #6ee7b7; }
  .summary-class {
    padding: 2px 10px; border-radius: 6px;
    background: rgba(96,165,250,.1); border: 1px solid rgba(96,165,250,.2);
    font-size: .75rem; font-weight: 700; color: #60a5fa;
  }
  .summary-counts { display: flex; flex-wrap: wrap; gap: 10px; }
  .sc-item {
    display: flex; align-items: center; gap: 5px;
    font-size: .78rem; font-weight: 600;
  }
  .sc-dot { width: 7px; height: 7px; border-radius: 50%; }
  .sc-hadir .sc-dot { background: #34d399; }
  .sc-hadir { color: #6ee7b7; }
  .sc-izin .sc-dot  { background: #fbbf24; }
  .sc-izin  { color: #fcd34d; }
  .sc-sakit .sc-dot { background: #fb923c; }
  .sc-sakit { color: #fdba74; }
  .sc-alpha .sc-dot { background: #f87171; }
  .sc-alpha { color: #fca5a5; }
  .sc-belum .sc-dot { background: #6b7280; }
  .sc-belum { color: #9ca3af; }

  /* ── Progress bar ── */
  .progress-wrap { margin-bottom: 16px; }
  .progress-label {
    display: flex; justify-content: space-between;
    font-size: .75rem; color: #4b5563; margin-bottom: 5px;
  }
  .progress-pct { font-weight: 700; color: #6b7280; }
  .progress-pct.complete { color: #34d399; }
  .progress-track {
    height: 6px; background: rgba(255,255,255,.05);
    border-radius: 999px; overflow: hidden;
  }
  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #34d399, #6ee7b7);
    border-radius: 999px;
    transition: width .4s cubic-bezier(.4,0,.2,1);
  }
  .progress-fill.complete {
    box-shadow: 0 0 8px rgba(52,211,153,.5);
  }

  /* ── Card toolbar ── */
  .card-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px; flex-wrap: wrap; gap: 8px;
  }
  .card-toolbar-left { display: flex; align-items: center; gap: 10px; }
  .card-toolbar-left h2 { font-size: .95rem; font-weight: 700; color: #f9fafb; }
  .total-chip {
    padding: 2px 10px; border-radius: 999px;
    background: rgba(110,231,183,.08); border: 1px solid rgba(110,231,183,.15);
    font-size: .72rem; font-weight: 700; color: #6ee7b7;
  }

  .all-hadir-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 8px;
    background: rgba(52,211,153,.1); border: 1px solid rgba(52,211,153,.2);
    color: #34d399; font-size: .8rem; font-weight: 700;
    cursor: pointer; font-family: inherit;
    transition: background .15s, box-shadow .15s;
  }
  .all-hadir-btn:hover {
    background: rgba(52,211,153,.18);
    box-shadow: 0 2px 10px rgba(52,211,153,.15);
  }

  /* ── Table ── */
  .td-num { color: #4b5563; font-size: .78rem; font-weight: 600; }
  .nip-badge {
    display: inline-block; padding: 3px 9px;
    background: rgba(110,231,183,.07); border: 1px solid rgba(110,231,183,.15);
    border-radius: 6px; font-family: 'Courier New', monospace;
    font-size: .78rem; font-weight: 700; color: #6ee7b7;
  }
  .td-name {
    display: flex; align-items: center; gap: 10px;
    font-weight: 600; color: #f9fafb;
  }
  .student-avatar {
    width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
    color: #0a2218; font-size: .72rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    text-transform: uppercase; transition: background .3s;
  }
  .row-filled { background: rgba(110,231,183,.02); }

  /* ── Status pills ── */
  .td-status { min-width: 260px; }
  .status-pills {
    display: flex; gap: 6px; flex-wrap: wrap;
  }
  .status-pill {
    display: inline-flex; align-items: center;
    padding: 5px 13px; border-radius: 8px; cursor: pointer;
    font-size: .78rem; font-weight: 600;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.08);
    color: #4b5563;
    transition: background .15s, border-color .15s, color .15s, transform .1s;
    user-select: none;
  }
  .status-pill input[type="radio"] { display: none; }
  .status-pill:hover:not(.selected) {
    background: rgba(255,255,255,.07);
    color: #9ca3af;
    transform: translateY(-1px);
  }
  .status-pill.selected { transform: translateY(-1px); font-weight: 700; }

  /* ── Save bar ── */
  .save-bar {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 20px; padding-top: 16px;
    border-top: 1px solid rgba(255,255,255,.06);
    flex-wrap: wrap; gap: 10px;
  }
  .save-bar-info {
    font-size: .82rem; color: #6b7280;
  }
  .save-filled { font-weight: 800; color: #6ee7b7; }

  /* ── Shared save button ── */
  .save-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 20px; border-radius: 10px; border: none;
    background: linear-gradient(135deg, #34d399, #059669);
    color: #0a2218; font-size: .88rem; font-weight: 700;
    cursor: pointer; font-family: inherit;
    box-shadow: 0 4px 16px rgba(52,211,153,.25);
    transition: transform .15s, box-shadow .15s, opacity .15s;
  }
  .save-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 22px rgba(52,211,153,.35);
  }
  .save-btn:disabled { opacity: .5; cursor: not-allowed; }

  .btn-spinner {
    width: 13px; height: 13px;
    border: 2px solid rgba(10,34,24,.3);
    border-top-color: #0a2218; border-radius: 50%;
    animation: spin .6s linear infinite; flex-shrink: 0;
  }

  /* ── Responsive ── */

  /* Tablet (≤ 768px) */
  @media (max-width: 768px) {
    .summary-strip { padding: 10px 14px; gap: 8px; }
    .summary-counts { gap: 8px; }
    .card-toolbar { flex-direction: column; align-items: flex-start; gap: 8px; }
    .all-hadir-btn { width: 100%; justify-content: center; }
    .save-btn { width: 100%; justify-content: center; }
    .header-left h1 { font-size: 1.15rem; }
  }

  /* Mobile (≤ 600px) — pivot table ke card-list per mahasiswa */
  @media (max-width: 600px) {
    /* Hide table header kolom # dan NIP */
    thead th:nth-child(1),
    thead th:nth-child(2) { display: none; }
    .td-num, td:nth-child(2):has(.nip-badge) { display: none; }

    /* Status pills: 2x2 grid agar tidak terlalu lebar */
    .td-status { min-width: 0; }
    .status-pills { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; }
    .status-pill { padding: 6px 8px; font-size: .75rem; justify-content: center; }

    /* Progress bar compact */
    .progress-wrap { margin-bottom: 10px; }
  }

  /* Very small (≤ 420px) */
  @media (max-width: 420px) {
    .summary-counts { display: none; } /* summary terlalu ramai, cukup di progress bar */
    .student-avatar { width: 26px; height: 26px; font-size: .65rem; }
    .save-bar { flex-direction: column; align-items: stretch; }
    .save-bar-info { text-align: center; }
  }
</style>
