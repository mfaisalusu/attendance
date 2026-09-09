<script>
  import { onMount } from 'svelte';
  import AppLayout    from '../../layouts/AppLayout.svelte';
  import Spinner      from '../../components/common/Spinner.svelte';
  import ConfirmModal from '../../components/common/ConfirmModal.svelte';
  import Toast        from '../../components/common/Toast.svelte';
  import { masterService } from '../../../application/services/masterService.js';

  // ----------------------------------------------------------------
  // Tabs
  // ----------------------------------------------------------------
  const TABS = [
    { key: 'departments', label: 'Jurusan' },
    { key: 'courses',     label: 'Mata Kuliah' },
    { key: 'classes',     label: 'Kelas' },
    { key: 'semesters',   label: 'Semester' },
    { key: 'years',       label: 'Tahun' },
  ];

  let activeTab = 'departments';

  // ----------------------------------------------------------------
  // Data
  // ----------------------------------------------------------------
  let departments = [];
  let courses     = [];
  let classes     = [];
  let semesters   = [];
  let years       = [];
  let loading     = true;
  let error       = '';

  // ----------------------------------------------------------------
  // Form state
  // ----------------------------------------------------------------
  let showForm    = false;
  let editingItem = null;

  // shared fields
  let formCode = '';
  let formName = '';

  // courses extra
  let formDepartmentId = '';

  // classes extra
  let formClassDeptId  = '';
  let formSemesterId   = '';
  let formYear         = '';
  let formCourseIds    = [];   // int[]

  let formError   = '';
  let formLoading = false;

  // ----------------------------------------------------------------
  // Delete state
  // ----------------------------------------------------------------
  let showConfirm   = false;
  let deletingItem  = null;
  let deleteLoading = false;

  // ----------------------------------------------------------------
  // Toast
  // ----------------------------------------------------------------
  let toastMsg     = '';
  let toastType    = 'success';
  let toastVisible = false;
  function toast(msg, type = 'success') { toastMsg = msg; toastType = type; toastVisible = true; }

  // ----------------------------------------------------------------
  // Load
  // ----------------------------------------------------------------
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

  // ----------------------------------------------------------------
  // Auto-generate kode
  // ----------------------------------------------------------------

  // Courses: {DEPT_CODE}-{initials_of_name}  e.g. SI-TB
  function generateCourseCode(name, deptId) {
    const dept = departments.find(d => d.id === Number(deptId));
    if (!dept || !name.trim()) return '';
    const initials = name.trim().split(/\s+/).map(w => w[0].toUpperCase()).join('');
    return `${dept.code}-${initials}`;
  }

  // Classes: {DEPT_CODE}-SEM-{semester}-{year}  e.g. SI-SEM-1-2026
  function generateClassCode(deptId, semId, yr) {
    const dept = departments.find(d => d.id === Number(deptId));
    if (!dept || !semId || !yr) return '';
    return `${dept.code}-SEM-${semId}-${yr}`;
  }

  // Reactive auto-fill kode
  $: if (activeTab === 'courses' && !editingItem) {
    formCode = generateCourseCode(formName, formDepartmentId);
  }
  $: if (activeTab === 'classes' && !editingItem) {
    formCode = generateClassCode(formClassDeptId, formSemesterId, formYear);
  }

  // ----------------------------------------------------------------
  // Courses filtered by selected dept (for classes form)
  // ----------------------------------------------------------------
  $: coursesForClass = formClassDeptId
    ? courses.filter(c => c.department_id === Number(formClassDeptId))
    : courses;

  // ----------------------------------------------------------------
  // Form helpers
  // ----------------------------------------------------------------
  function openCreate() {
    editingItem      = null;
    formCode         = '';
    formName         = '';
    formDepartmentId = departments[0]?.id ?? '';
    formClassDeptId  = departments[0]?.id ?? '';
    formSemesterId   = semesters[0]?.id   ?? '';
    formYear         = years[0]?.id       ?? '';
    formCourseIds    = [];
    formError        = '';
    showForm         = true;
  }

  function openEdit(item) {
    editingItem      = item;
    formCode         = item.code  ?? '';
    formName         = item.name  ?? '';
    formDepartmentId = item.department_id ?? '';
    formClassDeptId  = item.department_id ?? '';
    formSemesterId   = item.semester_id   ?? '';
    formYear         = item.year          ?? '';
    formCourseIds    = item.course_ids    ? [...item.course_ids] : [];
    formError        = '';
    showForm         = true;
  }

  function closeForm() { showForm = false; }

  function toggleCourse(id) {
    const n = Number(id);
    formCourseIds = formCourseIds.includes(n)
      ? formCourseIds.filter(x => x !== n)
      : [...formCourseIds, n];
  }

  async function handleSubmit(e) {
    e.preventDefault();
    formError = '';

    if (!formName.trim())  { formError = 'Nama wajib diisi.'; return; }
    if (!formCode.trim())  { formError = 'Kode wajib diisi.'; return; }

    if (activeTab === 'courses' && !formDepartmentId) {
      formError = 'Jurusan wajib dipilih.'; return;
    }
    if (activeTab === 'classes') {
      if (!formClassDeptId)            { formError = 'Jurusan wajib dipilih.'; return; }
      if (!formSemesterId)             { formError = 'Semester wajib dipilih.'; return; }
      if (!formYear)                   { formError = 'Tahun wajib dipilih.'; return; }
      if (formCourseIds.length === 0)  { formError = 'Pilih minimal satu mata kuliah.'; return; }
    }

    formLoading = true;
    let res;
    try {
      if (activeTab === 'departments') {
        const p = { name: formName.trim(), code: formCode.trim() };
        res = editingItem
          ? await masterService.updateDepartment(editingItem.id, p)
          : await masterService.createDepartment(p);

      } else if (activeTab === 'courses') {
        const p = { name: formName.trim(), code: formCode.trim(), department_id: Number(formDepartmentId) };
        res = editingItem
          ? await masterService.updateCourse(editingItem.id, p)
          : await masterService.createCourse(p);

      } else if (activeTab === 'classes') {
        const p = {
          name:          formName.trim(),
          code:          formCode.trim(),
          department_id: Number(formClassDeptId),
          semester_id:   Number(formSemesterId),
          year:          Number(formYear),
          course_ids:    formCourseIds,
        };
        res = editingItem
          ? await masterService.updateClass(editingItem.id, p)
          : await masterService.createClass(p);
      }

      if (res?.ok) {
        toast(editingItem ? 'Data berhasil diperbarui.' : 'Data berhasil ditambahkan.');
        closeForm();
        await reloadActive();
      } else {
        formError = res?.data?.message ?? 'Gagal menyimpan data.';
      }
    } catch { formError = 'Gagal terhubung ke server.'; }
    formLoading = false;
  }

  // ----------------------------------------------------------------
  // Delete helpers
  // ----------------------------------------------------------------
  function confirmDelete(item) { deletingItem = item; showConfirm = true; }

  async function doDelete() {
    deleteLoading = true;
    let res;
    try {
      if      (activeTab === 'departments') res = await masterService.deleteDepartment(deletingItem.id);
      else if (activeTab === 'courses')     res = await masterService.deleteCourse(deletingItem.id);
      else if (activeTab === 'classes')     res = await masterService.deleteClass(deletingItem.id);
    } catch {
      toast('Gagal terhubung ke server.', 'error');
      deleteLoading = false; showConfirm = false; return;
    }
    deleteLoading = false; showConfirm = false;
    if (res?.ok) { toast('Data berhasil dihapus.'); await reloadActive(); }
    else         { toast(res?.data?.message ?? 'Gagal menghapus data.', 'error'); }
  }

  // ----------------------------------------------------------------
  // Computed
  // ----------------------------------------------------------------
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
  <div class="page-header">
    <h1>Master Data</h1>
    {#if !isReadOnly}
      <button class="btn btn-primary" on:click={openCreate}>+ Tambah {activeLabel}</button>
    {/if}
  </div>

  <!-- Tabs -->
  <div class="tabs" role="tablist">
    {#each TABS as tab}
      <button role="tab" class="tab-btn" class:active={activeTab === tab.key}
        aria-selected={activeTab === tab.key} on:click={() => { activeTab = tab.key; }}>
        {tab.label}
        {#if tab.key === 'semesters' || tab.key === 'years'}
          <span class="ro-badge">read-only</span>
        {/if}
      </button>
    {/each}
  </div>

  <!-- Table -->
  <div class="card tab-content">
    {#if loading}
      <Spinner />
    {:else if error}
      <div class="alert alert-error">{error}</div>
    {:else if activeList.length === 0}
      <div class="empty-wrap">
        <span style="font-size:2rem">📂</span>
        <p>Belum ada data {activeLabel.toLowerCase()}.</p>
        {#if !isReadOnly}
          <button class="btn btn-primary btn-sm" on:click={openCreate}>Tambah {activeLabel}</button>
        {/if}
      </div>
    {:else}
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:50px">#</th>
              {#if activeTab !== 'semesters' && activeTab !== 'years'}
                <th style="width:100px">Kode</th>
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
                <th style="width:120px;text-align:right">Aksi</th>
              {/if}
            </tr>
          </thead>
          <tbody>
            {#each activeList as item, i (item.id)}
              <tr>
                <td class="muted">{i + 1}</td>
                {#if activeTab !== 'semesters' && activeTab !== 'years'}
                  <td><span class="badge">{item.code}</span></td>
                {/if}
                <td style="font-weight:500">{item.name}</td>
                {#if activeTab === 'courses'}
                  <td class="muted">{deptLabel(item.department_id)}</td>
                {/if}
                {#if activeTab === 'classes'}
                  <td class="muted">{deptLabel(item.department_id)}</td>
                  <td class="muted">{semesterLabel(item.semester_id)}</td>
                  <td class="muted">{item.year ?? '-'}</td>
                  <td class="muted small">{courseNames(item.course_ids)}</td>
                {/if}
                {#if !isReadOnly}
                  <td style="text-align:right;white-space:nowrap">
                    <button class="btn btn-secondary btn-sm" on:click={() => openEdit(item)}>Edit</button>
                    <button class="btn btn-danger btn-sm"    on:click={() => confirmDelete(item)}>Hapus</button>
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

<!-- ----------------------------------------------------------------
     Form Modal
----------------------------------------------------------------- -->
{#if showForm}
  <!-- svelte-ignore a11y-click-events-have-key-events a11y-no-static-element-interactions -->
  <div class="modal-backdrop" role="presentation" on:click|self={() => !formLoading && closeForm()}>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="mf-title">
      <div class="modal-header">
        <h3 id="mf-title">{editingItem ? 'Edit' : 'Tambah'} {activeLabel}</h3>
        <button class="modal-close" on:click={closeForm} disabled={formLoading} aria-label="Tutup">✕</button>
      </div>

      <form on:submit={handleSubmit} novalidate>
        <div class="modal-body">
          {#if formError}
            <div class="alert alert-error">{formError}</div>
          {/if}

          <!-- Jurusan dropdown — courses & classes -->
          {#if activeTab === 'courses'}
            <div class="form-group">
              <label for="f-dept">Jurusan</label>
              <select id="f-dept" class="form-control" bind:value={formDepartmentId} required>
                <option value="" disabled>Pilih jurusan</option>
                {#each departments as d}<option value={d.id}>{d.name}</option>{/each}
              </select>
            </div>
          {/if}

          {#if activeTab === 'classes'}
            <div class="form-group">
              <label for="f-cdept">Jurusan</label>
              <select id="f-cdept" class="form-control" bind:value={formClassDeptId} required>
                <option value="" disabled>Pilih jurusan</option>
                {#each departments as d}<option value={d.id}>{d.name}</option>{/each}
              </select>
            </div>
          {/if}

          <!-- Kode (auto-filled, tapi bisa diubah manual) -->
          <div class="form-group">
            <label for="f-code">Kode</label>
            <input id="f-code" type="text" class="form-control" bind:value={formCode}
              placeholder={activeTab === 'departments' ? 'cth: SI' : activeTab === 'courses' ? 'cth: SI-TB' : 'cth: SI-SEM-1-2026'}
              maxlength="30" required />
            {#if !editingItem && (activeTab === 'courses' || activeTab === 'classes')}
              <small class="hint">Diisi otomatis, bisa diubah manual.</small>
            {/if}
          </div>

          <!-- Nama -->
          <div class="form-group">
            <label for="f-name">Nama</label>
            <input id="f-name" type="text" class="form-control" bind:value={formName}
              placeholder={activeTab === 'departments' ? 'cth: Sistem Informasi' : activeTab === 'courses' ? 'cth: Teknologi Blockchain' : 'cth: SI Semester 1 2026'}
              maxlength="150" required />
          </div>

          <!-- Semester & Tahun — hanya kelas -->
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

            <!-- Mata kuliah multi-select -->
            <div class="form-group">
              <label>Mata Kuliah <small class="hint">(pilih satu atau lebih)</small></label>
              {#if coursesForClass.length === 0}
                <p class="hint">Belum ada mata kuliah untuk jurusan ini.</p>
              {:else}
                <div class="course-checklist">
                  {#each coursesForClass as c (c.id)}
                    <label class="check-item">
                      <input type="checkbox" checked={formCourseIds.includes(c.id)}
                        on:change={() => toggleCourse(c.id)} />
                      <span>{c.code} — {c.name}</span>
                    </label>
                  {/each}
                </div>
              {/if}
            </div>
          {/if}
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" on:click={closeForm} disabled={formLoading}>Batal</button>
          <button type="submit" class="btn btn-primary" disabled={formLoading}>
            {formLoading ? 'Menyimpan...' : (editingItem ? 'Simpan Perubahan' : 'Tambah')}
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
  .tabs { display:flex; gap:4px; margin-bottom:16px; border-bottom:2px solid var(--gray-200); flex-wrap:wrap; }
  .tab-btn {
    padding:8px 18px; font-size:.875rem; font-weight:500; color:var(--gray-500);
    background:none; border:none; border-bottom:3px solid transparent; margin-bottom:-2px;
    cursor:pointer; transition:color .15s,border-color .15s; display:flex; align-items:center; gap:6px;
  }
  .tab-btn:hover  { color:var(--gray-800); }
  .tab-btn.active { color:var(--primary); border-bottom-color:var(--primary); font-weight:600; }

  .ro-badge {
    font-size:.65rem; font-weight:600; text-transform:uppercase; letter-spacing:.03em;
    background:var(--gray-200); color:var(--gray-500); padding:1px 6px; border-radius:99px;
  }

  .tab-content { margin-top:0; }
  .muted       { color:var(--gray-500); }
  .small       { font-size:.8rem; }

  .badge {
    display:inline-block; font-family:monospace; font-size:.8rem; font-weight:600;
    background:var(--gray-100); color:var(--gray-700); padding:2px 8px; border-radius:4px;
  }

  .hint { color:var(--gray-400); font-size:.78rem; margin-top:2px; }

  .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

  .course-checklist {
    display:flex; flex-direction:column; gap:6px;
    max-height:180px; overflow-y:auto;
    border:1px solid var(--gray-200); border-radius:6px; padding:10px;
    background:var(--gray-50);
  }
  .check-item { display:flex; align-items:center; gap:8px; cursor:pointer; font-size:.875rem; }
  .check-item input { width:16px; height:16px; cursor:pointer; flex-shrink:0; }

  .modal-footer {
    display:flex; justify-content:flex-end; gap:8px;
    padding:16px 24px; border-top:1px solid var(--gray-200);
  }
</style>
