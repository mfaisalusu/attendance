<script>
  import { onMount } from 'svelte';
  import AppLayout    from '../../layouts/AppLayout.svelte';
  import ConfirmModal from '../../components/common/ConfirmModal.svelte';
  import Toast        from '../../components/common/Toast.svelte';
  import { masterService } from '../../../application/services/masterService.js';

  // ── Tabs ──────────────────────────────────────────────────────────
  const TABS = [
    { key: 'departments', label: 'Jurusan',     icon: 'dept'     },
    { key: 'courses',     label: 'Mata Kuliah',  icon: 'course'   },
    { key: 'classes',     label: 'Kelas',        icon: 'class'    },
    { key: 'semesters',   label: 'Semester',     icon: 'semester' },
    { key: 'years',       label: 'Tahun',        icon: 'year'     },
  ];
  let activeTab = 'departments';

  // ── Data ──────────────────────────────────────────────────────────
  let departments = [], courses = [], classes = [], semesters = [], years = [];
  let loading = true, error = '';

  // ── Form ──────────────────────────────────────────────────────────
  let showForm    = false;
  let editingItem = null;

  let formCode = '', formName = '';
  let formDepartmentId = '';
  let formClassDeptId  = '';
  let formSemesterId   = '';
  let formYear         = '';
  let formCourseIds    = [];
  let formError        = '', formLoading = false;

  // ── Delete ────────────────────────────────────────────────────────
  let showConfirm   = false;
  let deletingItem  = null;
  let deleteLoading = false;

  // ── Toast ─────────────────────────────────────────────────────────
  let toastMsg = '', toastType = 'success', toastVisible = false;
  function toast(msg, type = 'success') { toastMsg = msg; toastType = type; toastVisible = true; }

  // ── Load ──────────────────────────────────────────────────────────
  onMount(loadAll);

  async function loadAll() {
    loading = true; error = '';
    try {
      [departments, courses, classes, semesters, years] = await Promise.all([
        masterService.getDepartments(),
        masterService.getCourses(),
        masterService.getClasses(),
        masterService.getSemesters(),
        masterService.getYears(),
      ]);
    } catch { error = 'Gagal memuat data master.'; }
    loading = false;
  }

  async function reloadActive() {
    if      (activeTab === 'departments') departments = await masterService.getDepartments();
    else if (activeTab === 'courses')     courses     = await masterService.getCourses();
    else if (activeTab === 'classes')     classes     = await masterService.getClasses();
  }

  // ── Auto-generate kode ────────────────────────────────────────────
  function generateCourseCode(name, deptId) {
    const dept = departments.find(d => d.id === Number(deptId));
    if (!dept || !name.trim()) return '';
    const initials = name.trim().split(/\s+/).map(w => w[0].toUpperCase()).join('');
    return `${dept.code}-${initials}`;
  }
  function generateClassCode(deptId, semId, yr) {
    const dept = departments.find(d => d.id === Number(deptId));
    if (!dept || !semId || !yr) return '';
    return `${dept.code}-SEM-${semId}-${yr}`;
  }

  $: if (activeTab === 'courses' && !editingItem) formCode = generateCourseCode(formName, formDepartmentId);
  $: if (activeTab === 'classes' && !editingItem) formCode = generateClassCode(formClassDeptId, formSemesterId, formYear);

  $: coursesForClass = formClassDeptId
    ? courses.filter(c => c.department_id === Number(formClassDeptId))
    : courses;

  // ── Form helpers ──────────────────────────────────────────────────
  function openCreate() {
    editingItem = null; formCode = ''; formName = '';
    formDepartmentId = departments[0]?.id ?? '';
    formClassDeptId  = departments[0]?.id ?? '';
    formSemesterId   = semesters[0]?.id   ?? '';
    formYear         = years[0]?.id       ?? '';
    formCourseIds    = []; formError = '';
    showForm = true;
  }
  function openEdit(item) {
    editingItem = item; formCode = item.code ?? ''; formName = item.name ?? '';
    formDepartmentId = item.department_id ?? '';
    formClassDeptId  = item.department_id ?? '';
    formSemesterId   = item.semester_id   ?? '';
    formYear         = item.year          ?? '';
    formCourseIds    = item.course_ids ? [...item.course_ids] : [];
    formError = ''; showForm = true;
  }
  function closeForm() { showForm = false; }

  function toggleCourse(id) {
    const n = Number(id);
    formCourseIds = formCourseIds.includes(n)
      ? formCourseIds.filter(x => x !== n)
      : [...formCourseIds, n];
  }

  async function handleSubmit(e) {
    e.preventDefault(); formError = '';
    if (!formName.trim()) { formError = 'Nama wajib diisi.'; return; }
    if (!formCode.trim()) { formError = 'Kode wajib diisi.'; return; }
    if (activeTab === 'courses' && !formDepartmentId) { formError = 'Jurusan wajib dipilih.'; return; }
    if (activeTab === 'classes') {
      if (!formClassDeptId)           { formError = 'Jurusan wajib dipilih.'; return; }
      if (!formSemesterId)            { formError = 'Semester wajib dipilih.'; return; }
      if (!formYear)                  { formError = 'Tahun wajib dipilih.'; return; }
      if (formCourseIds.length === 0) { formError = 'Pilih minimal satu mata kuliah.'; return; }
    }
    formLoading = true;
    let res;
    try {
      if (activeTab === 'departments') {
        const p = { name: formName.trim(), code: formCode.trim() };
        res = editingItem ? await masterService.updateDepartment(editingItem.id, p)
                          : await masterService.createDepartment(p);
      } else if (activeTab === 'courses') {
        const p = { name: formName.trim(), code: formCode.trim(), department_id: Number(formDepartmentId) };
        res = editingItem ? await masterService.updateCourse(editingItem.id, p)
                          : await masterService.createCourse(p);
      } else if (activeTab === 'classes') {
        const p = {
          name: formName.trim(), code: formCode.trim(),
          department_id: Number(formClassDeptId), semester_id: Number(formSemesterId),
          year: Number(formYear), course_ids: formCourseIds,
        };
        res = editingItem ? await masterService.updateClass(editingItem.id, p)
                          : await masterService.createClass(p);
      }
      if (res?.ok) {
        toast(editingItem ? 'Data berhasil diperbarui.' : 'Data berhasil ditambahkan.');
        closeForm(); await reloadActive();
      } else { formError = res?.data?.message ?? 'Gagal menyimpan data.'; }
    } catch { formError = 'Gagal terhubung ke server.'; }
    formLoading = false;
  }

  // ── Delete helpers ────────────────────────────────────────────────
  function confirmDelete(item) { deletingItem = item; showConfirm = true; }
  async function doDelete() {
    deleteLoading = true;
    let res;
    try {
      if      (activeTab === 'departments') res = await masterService.deleteDepartment(deletingItem.id);
      else if (activeTab === 'courses')     res = await masterService.deleteCourse(deletingItem.id);
      else if (activeTab === 'classes')     res = await masterService.deleteClass(deletingItem.id);
    } catch { toast('Gagal terhubung ke server.', 'error'); deleteLoading = false; showConfirm = false; return; }
    deleteLoading = false; showConfirm = false;
    if (res?.ok) { toast('Data berhasil dihapus.'); await reloadActive(); }
    else         { toast(res?.data?.message ?? 'Gagal menghapus data.', 'error'); }
  }

  // ── Computed ──────────────────────────────────────────────────────
  $: isReadOnly = activeTab === 'semesters' || activeTab === 'years';
  $: activeList = activeTab === 'departments' ? departments
    : activeTab === 'courses'   ? courses
    : activeTab === 'classes'   ? classes
    : activeTab === 'semesters' ? semesters
    : years;
  $: activeLabel = TABS.find(t => t.key === activeTab)?.label ?? '';

  function semesterLabel(id) { return semesters.find(s => s.id === Number(id))?.name ?? '-'; }
  function deptLabel(id)     { return departments.find(d => d.id === Number(id))?.name ?? '-'; }
  function courseNames(ids)  {
    if (!ids?.length) return '-';
    return ids.map(id => courses.find(c => c.id === id)?.name ?? id).join(', ');
  }
</script>

<svelte:head><title>Master Data — Absensi</title></svelte:head>

<AppLayout currentPath="/master">

  <!-- Header -->
  <div class="page-header">
    <div class="header-left">
      <div class="page-eyebrow">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <ellipse cx="12" cy="5" rx="9" ry="3"/>
          <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
          <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
        </svg>
        Konfigurasi Sistem
      </div>
      <h1>Master Data</h1>
    </div>
    {#if !isReadOnly && !loading}
      <button class="btn btn-primary" on:click={openCreate}>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Tambah {activeLabel}
      </button>
    {/if}
  </div>

  <!-- Tabs -->
  <div class="tabs-wrap" role="tablist" aria-label="Kategori master data">
    {#each TABS as tab}
      <button
        role="tab"
        class="tab-btn"
        class:active={activeTab === tab.key}
        aria-selected={activeTab === tab.key}
        on:click={() => { activeTab = tab.key; }}
      >
        <!-- Icon per tab -->
        <span class="tab-icon">
          {#if tab.icon === 'dept'}
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            </svg>
          {:else if tab.icon === 'course'}
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
            </svg>
          {:else if tab.icon === 'class'}
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          {:else if tab.icon === 'semester'}
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/>
              <line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/>
            </svg>
          {:else}
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
          {/if}
        </span>
        {tab.label}
        {#if isReadOnly && (tab.key === 'semesters' || tab.key === 'years')}
          <span class="ro-badge">Hanya baca</span>
        {/if}
        {#if activeTab === tab.key}
          <span class="tab-count">
            {activeTab === tab.key ? activeList.length : ''}
          </span>
        {/if}
      </button>
    {/each}
  </div>

  <!-- Table card -->
  <div class="card table-card">
    {#if loading}
      <div class="state-wrap">
        <div class="state-spinner"></div>
        <span>Memuat data master...</span>
      </div>

    {:else if error}
      <div class="alert alert-error" role="alert">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {error}
      </div>

    {:else if activeList.length === 0}
      <div class="state-wrap state-empty">
        <div class="empty-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
            <ellipse cx="12" cy="5" rx="9" ry="3"/>
            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
          </svg>
        </div>
        <p class="empty-title">Belum ada data {activeLabel.toLowerCase()}</p>
        {#if !isReadOnly}
          <button class="btn btn-primary btn-sm" on:click={openCreate}>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah {activeLabel}
          </button>
        {/if}
      </div>

    {:else}
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:44px">#</th>
              {#if activeTab !== 'semesters' && activeTab !== 'years'}
                <th style="width:110px">Kode</th>
              {/if}
              <th>Nama</th>
              {#if activeTab === 'courses'}
                <th style="width:160px">Jurusan</th>
              {/if}
              {#if activeTab === 'classes'}
                <th style="width:140px">Jurusan</th>
                <th style="width:110px">Semester</th>
                <th style="width:70px">Tahun</th>
                <th>Mata Kuliah</th>
              {/if}
              {#if !isReadOnly}
                <th style="width:130px;text-align:right">Aksi</th>
              {/if}
            </tr>
          </thead>
          <tbody>
            {#each activeList as item, i (item.id)}
              <tr>
                <td class="td-num">{i + 1}</td>
                {#if activeTab !== 'semesters' && activeTab !== 'years'}
                  <td><span class="code-badge">{item.code}</span></td>
                {/if}
                <td class="td-name">{item.name}</td>
                {#if activeTab === 'courses'}
                  <td><span class="dept-chip">{deptLabel(item.department_id)}</span></td>
                {/if}
                {#if activeTab === 'classes'}
                  <td><span class="dept-chip">{deptLabel(item.department_id)}</span></td>
                  <td><span class="sem-chip">{semesterLabel(item.semester_id)}</span></td>
                  <td><span class="year-chip">{item.year ?? '-'}</span></td>
                  <td class="td-courses">{courseNames(item.course_ids)}</td>
                {/if}
                {#if !isReadOnly}
                  <td class="td-actions">
                    <button class="action-btn action-edit" on:click={() => openEdit(item)}>
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                      Edit
                    </button>
                    <button class="action-btn action-delete" on:click={() => confirmDelete(item)}>
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                      </svg>
                      Hapus
                    </button>
                  </td>
                {/if}
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {/if}
  </div>

</AppLayout>

<!-- ── Form Modal ─────────────────────────────────────────────────── -->
{#if showForm}
  <!-- svelte-ignore a11y-click-events-have-key-events a11y-no-static-element-interactions -->
  <div class="modal-backdrop" role="presentation" on:click|self={() => !formLoading && closeForm()}>
    <div class="modal master-modal" role="dialog" aria-modal="true" aria-labelledby="mf-title">

      <div class="modal-header">
        <div class="modal-title-wrap">
          <div class="modal-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
              {#if editingItem}
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
              {:else}
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
              {/if}
            </svg>
          </div>
          <h3 id="mf-title">{editingItem ? 'Edit' : 'Tambah'} {activeLabel}</h3>
        </div>
        <button class="modal-close" on:click={closeForm} disabled={formLoading} aria-label="Tutup">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <form on:submit={handleSubmit} novalidate>
        <div class="modal-body">
          {#if formError}
            <div class="alert alert-error" role="alert">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              {formError}
            </div>
          {/if}

          <!-- Jurusan — courses -->
          {#if activeTab === 'courses'}
            <div class="form-group">
              <label for="f-dept">Jurusan</label>
              <select id="f-dept" class="form-control" bind:value={formDepartmentId} required>
                <option value="" disabled>Pilih jurusan</option>
                {#each departments as d}<option value={d.id}>{d.code} — {d.name}</option>{/each}
              </select>
            </div>
          {/if}

          <!-- Jurusan — classes -->
          {#if activeTab === 'classes'}
            <div class="form-group">
              <label for="f-cdept">Jurusan</label>
              <select id="f-cdept" class="form-control" bind:value={formClassDeptId} required>
                <option value="" disabled>Pilih jurusan</option>
                {#each departments as d}<option value={d.id}>{d.code} — {d.name}</option>{/each}
              </select>
            </div>
          {/if}

          <!-- Kode -->
          <div class="form-group">
            <label for="f-code">Kode</label>
            <input id="f-code" type="text" class="form-control" bind:value={formCode}
              placeholder={
                activeTab === 'departments' ? 'cth: SI' :
                activeTab === 'courses'     ? 'cth: SI-TB' :
                                             'cth: SI-SEM-1-2026'
              }
              maxlength="30" required />
            {#if !editingItem && (activeTab === 'courses' || activeTab === 'classes')}
              <small class="hint-text">Diisi otomatis berdasarkan nama — bisa diubah.</small>
            {/if}
          </div>

          <!-- Nama -->
          <div class="form-group">
            <label for="f-name">Nama</label>
            <input id="f-name" type="text" class="form-control" bind:value={formName}
              placeholder={
                activeTab === 'departments' ? 'cth: Sistem Informasi' :
                activeTab === 'courses'     ? 'cth: Teknologi Blockchain' :
                                             'cth: SI Semester 1 2026'
              }
              maxlength="150" required />
          </div>

          <!-- Semester & Tahun — classes -->
          {#if activeTab === 'classes'}
            <div class="form-row">
              <div class="form-group">
                <label for="f-sem">Semester</label>
                <select id="f-sem" class="form-control" bind:value={formSemesterId} required>
                  <option value="" disabled>Pilih semester</option>
                  {#each semesters as s}<option value={s.id}>{s.name}</option>{/each}
                </select>
              </div>
              <div class="form-group">
                <label for="f-year">Tahun</label>
                <select id="f-year" class="form-control" bind:value={formYear} required>
                  <option value="" disabled>Pilih tahun</option>
                  {#each years as y}<option value={y.id}>{y.name}</option>{/each}
                </select>
              </div>
            </div>

            <!-- Mata kuliah checklist -->
            <div class="form-group">
              <label id="courses-label">
                Mata Kuliah
                <span class="label-hint">(pilih satu atau lebih)</span>
              </label>
              {#if coursesForClass.length === 0}
                <p class="hint-text">Belum ada mata kuliah untuk jurusan ini.</p>
              {:else}
                <div class="course-checklist" role="group" aria-labelledby="courses-label">
                  {#each coursesForClass as c (c.id)}
                    <label class="check-item" class:checked={formCourseIds.includes(c.id)}>
                      <input type="checkbox"
                        checked={formCourseIds.includes(c.id)}
                        on:change={() => toggleCourse(c.id)} />
                      <span class="check-box">
                        {#if formCourseIds.includes(c.id)}
                          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                        {/if}
                      </span>
                      <span class="check-code">{c.code}</span>
                      <span class="check-name">{c.name}</span>
                    </label>
                  {/each}
                </div>
              {/if}
            </div>
          {/if}
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" on:click={closeForm} disabled={formLoading}>
            Batal
          </button>
          <button type="submit" class="btn btn-primary" disabled={formLoading}>
            {#if formLoading}
              <span class="btn-spinner"></span>
              Menyimpan...
            {:else}
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
              {editingItem ? 'Simpan Perubahan' : `Tambah ${activeLabel}`}
            {/if}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<ConfirmModal
  bind:visible={showConfirm}
  title="Hapus {activeLabel}"
  message="Data <b>{deletingItem?.name ?? ''}</b> akan dihapus permanen. Lanjutkan?"
  onConfirm={doDelete}
  loading={deleteLoading}
/>
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

  /* ── Tabs ── */
  .tabs-wrap {
    display: flex; gap: 4px; flex-wrap: wrap;
    margin-bottom: 16px;
    padding-bottom: 2px;
    border-bottom: 1px solid rgba(255,255,255,.06);
  }
  .tab-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; font-size: .83rem; font-weight: 600;
    color: #4b5563; background: none; border: none;
    border-bottom: 2px solid transparent; margin-bottom: -1px;
    cursor: pointer; font-family: inherit;
    transition: color .15s, border-color .15s;
    border-radius: 6px 6px 0 0;
    white-space: nowrap;
  }
  .tab-btn:hover  { color: #9ca3af; background: rgba(255,255,255,.03); }
  .tab-btn.active {
    color: #6ee7b7;
    border-bottom-color: #6ee7b7;
    background: rgba(110,231,183,.05);
  }
  .tab-icon { display: flex; align-items: center; opacity: .7; }
  .tab-btn.active .tab-icon { opacity: 1; }

  .ro-badge {
    font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    background: rgba(251,191,36,.1); color: #fbbf24;
    border: 1px solid rgba(251,191,36,.2);
    padding: 1px 7px; border-radius: 999px;
  }
  .tab-count {
    font-size: .68rem; font-weight: 800;
    background: rgba(110,231,183,.12); color: #6ee7b7;
    border: 1px solid rgba(110,231,183,.2);
    padding: 1px 7px; border-radius: 999px; min-width: 20px; text-align: center;
  }

  /* ── Table card ── */
  .table-card { padding: 0; overflow: hidden; }

  /* ── States ── */
  .state-wrap {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 10px;
    padding: 56px 24px; color: #4b5563; font-size: .875rem;
  }
  .state-spinner {
    width: 34px; height: 34px;
    border: 3px solid rgba(110,231,183,.1);
    border-top-color: #6ee7b7; border-radius: 50%;
    animation: spin .7s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
  .state-empty { gap: 8px; }
  .empty-icon {
    width: 56px; height: 56px; border-radius: 50%;
    background: rgba(110,231,183,.05); border: 1px solid rgba(110,231,183,.1);
    display: flex; align-items: center; justify-content: center;
    color: rgba(110,231,183,.3);
  }
  .empty-title { font-size: .9rem; font-weight: 600; color: #6b7280; }

  /* ── Table cells ── */
  .td-num { color: #374151; font-size: .75rem; font-weight: 600; }
  .td-name { font-weight: 600; color: #f9fafb; }
  .td-courses { font-size: .78rem; color: #6b7280; max-width: 240px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

  .code-badge {
    display: inline-block; padding: 2px 9px;
    background: rgba(110,231,183,.07); border: 1px solid rgba(110,231,183,.15);
    border-radius: 6px; font-family: 'Courier New', monospace;
    font-size: .75rem; font-weight: 700; color: #6ee7b7;
  }
  .dept-chip {
    display: inline-block; padding: 2px 8px; border-radius: 6px;
    background: rgba(96,165,250,.08); border: 1px solid rgba(96,165,250,.18);
    font-size: .72rem; font-weight: 600; color: #60a5fa;
  }
  .sem-chip {
    display: inline-block; padding: 2px 8px; border-radius: 6px;
    background: rgba(167,139,250,.08); border: 1px solid rgba(167,139,250,.18);
    font-size: .72rem; font-weight: 600; color: #a78bfa;
  }
  .year-chip {
    display: inline-block; padding: 2px 8px; border-radius: 6px;
    background: rgba(251,191,36,.08); border: 1px solid rgba(251,191,36,.18);
    font-size: .72rem; font-weight: 600; color: #fbbf24;
  }

  /* ── Action buttons ── */
  .td-actions { text-align: right; white-space: nowrap; padding-right: 16px; }
  .action-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 7px;
    font-size: .775rem; font-weight: 600; cursor: pointer;
    border: 1px solid transparent; font-family: inherit;
    transition: background .15s, transform .1s;
    margin-left: 5px;
  }
  .action-btn:hover { transform: translateY(-1px); }
  .action-edit {
    background: rgba(110,231,183,.08); border-color: rgba(110,231,183,.18); color: #6ee7b7;
  }
  .action-edit:hover  { background: rgba(110,231,183,.15); }
  .action-delete {
    background: rgba(248,113,113,.08); border-color: rgba(248,113,113,.18); color: #f87171;
  }
  .action-delete:hover { background: rgba(248,113,113,.15); }

  /* ── Modal ── */
  .master-modal { max-width: 540px; }
  .modal-title-wrap { display: flex; align-items: center; gap: 10px; }
  .modal-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(110,231,183,.1); border: 1px solid rgba(110,231,183,.2);
    display: flex; align-items: center; justify-content: center;
    color: #6ee7b7; flex-shrink: 0;
  }

  /* Form extras */
  .hint-text { color: #4b5563; font-size: .75rem; margin-top: 4px; display: block; }
  .label-hint { font-size: .72rem; color: #4b5563; font-weight: 400; margin-left: 4px; text-transform: none; letter-spacing: 0; }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

  /* Checklist */
  .course-checklist {
    display: flex; flex-direction: column; gap: 5px;
    max-height: 190px; overflow-y: auto;
    border: 1px solid rgba(110,231,183,.12); border-radius: 10px;
    padding: 10px; background: rgba(110,231,183,.03);
    margin-top: 4px;
  }
  .course-checklist::-webkit-scrollbar { width: 4px; }
  .course-checklist::-webkit-scrollbar-thumb { background: rgba(110,231,183,.2); border-radius: 2px; }

  .check-item {
    display: flex; align-items: center; gap: 8px; cursor: pointer;
    padding: 6px 8px; border-radius: 7px; font-size: .82rem;
    border: 1px solid transparent;
    transition: background .15s, border-color .15s;
    user-select: none;
  }
  .check-item:hover { background: rgba(110,231,183,.05); }
  .check-item.checked {
    background: rgba(110,231,183,.08);
    border-color: rgba(110,231,183,.18);
  }
  .check-item input[type="checkbox"] { display: none; }

  .check-box {
    width: 16px; height: 16px; border-radius: 4px; flex-shrink: 0;
    border: 1.5px solid rgba(110,231,183,.3);
    background: rgba(110,231,183,.05);
    display: flex; align-items: center; justify-content: center;
    color: #34d399; transition: all .15s;
  }
  .check-item.checked .check-box {
    background: rgba(52,211,153,.2);
    border-color: #34d399;
  }

  .check-code {
    font-family: 'Courier New', monospace; font-size: .72rem; font-weight: 700;
    color: #6ee7b7; background: rgba(110,231,183,.07);
    border: 1px solid rgba(110,231,183,.15); border-radius: 4px;
    padding: 1px 6px; flex-shrink: 0;
  }
  .check-name { color: #9ca3af; flex: 1; }
  .check-item.checked .check-name { color: #d1fae5; }

  /* Btn spinner */
  .btn-spinner {
    width: 13px; height: 13px;
    border: 2px solid rgba(10,34,24,.3);
    border-top-color: #0a2218; border-radius: 50%;
    animation: spin .6s linear infinite; flex-shrink: 0;
  }
</style>
