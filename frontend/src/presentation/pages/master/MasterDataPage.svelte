<script>
  import { onMount } from 'svelte';
  import AppLayout      from '../../layouts/AppLayout.svelte';
  import Spinner        from '../../components/common/Spinner.svelte';
  import ConfirmModal   from '../../components/common/ConfirmModal.svelte';
  import Toast          from '../../components/common/Toast.svelte';
  import { masterService } from '../../../application/services/masterService.js';

  // ----------------------------------------------------------------
  // Tab state
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

  let loading = true;
  let error   = '';

  // ----------------------------------------------------------------
  // Form state
  // ----------------------------------------------------------------
  let showForm      = false;
  let editingItem   = null;
  let formName      = '';
  let formCode      = '';
  let formSemesterId = '';   // khusus kelas
  let formYear      = '';    // khusus kelas
  let formError     = '';
  let formLoading   = false;

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

  function showToast(msg, type = 'success') {
    toastMsg = msg; toastType = type; toastVisible = true;
  }

  // ----------------------------------------------------------------
  // Load
  // ----------------------------------------------------------------
  onMount(async () => {
    await loadAll();
  });

  async function loadAll() {
    loading = true;
    error = '';
    try {
      [departments, courses, classes, semesters, years] = await Promise.all([
        masterService.getDepartments(),
        masterService.getCourses(),
        masterService.getClasses(),
        masterService.getSemesters(),
        masterService.getYears(),
      ]);
    } catch {
      error = 'Gagal memuat data master.';
    }
    loading = false;
  }

  async function reloadActive() {
    if      (activeTab === 'departments') departments = await masterService.getDepartments();
    else if (activeTab === 'courses')     courses     = await masterService.getCourses();
    else if (activeTab === 'classes')     classes     = await masterService.getClasses();
    else if (activeTab === 'years')       years       = await masterService.getYears();
  }

  // ----------------------------------------------------------------
  // Lookup helpers (untuk tampil nama di tabel kelas)
  // ----------------------------------------------------------------
  function semesterName(id) {
    return semesters.find(s => s.id === Number(id))?.name ?? '-';
  }

  // ----------------------------------------------------------------
  // Form helpers
  // ----------------------------------------------------------------
  function openCreate() {
    editingItem    = null;
    formName       = '';
    formCode       = '';
    formSemesterId = semesters[0]?.id ?? '';
    formYear       = years[0]?.id     ?? '';
    formError      = '';
    showForm       = true;
  }

  function openEdit(item) {
    editingItem    = item;
    formName       = item.name;
    formCode       = item.code ?? '';
    formSemesterId = item.semester_id ?? semesters[0]?.id ?? '';
    formYear       = item.year        ?? years[0]?.id     ?? '';
    formError      = '';
    showForm       = true;
  }

  function closeForm() { showForm = false; }

  async function handleSubmit(e) {
    e.preventDefault();
    formError = '';

    if (!formName.trim()) { formError = 'Nama wajib diisi.'; return; }
    if (!formCode.trim()) { formError = 'Kode wajib diisi.'; return; }
    if (activeTab === 'classes') {
      if (!formSemesterId) { formError = 'Semester wajib dipilih.'; return; }
      if (!formYear)       { formError = 'Tahun wajib dipilih.'; return; }
    }

    formLoading = true;

    const basePayload = { name: formName.trim(), code: formCode.trim() };
    const classPayload = {
      ...basePayload,
      semester_id: Number(formSemesterId),
      year:        Number(formYear),
    };

    let res;
    try {
      if (activeTab === 'departments') {
        res = editingItem
          ? await masterService.updateDepartment(editingItem.id, basePayload)
          : await masterService.createDepartment(basePayload);
      } else if (activeTab === 'courses') {
        res = editingItem
          ? await masterService.updateCourse(editingItem.id, basePayload)
          : await masterService.createCourse(basePayload);
      } else if (activeTab === 'classes') {
        res = editingItem
          ? await masterService.updateClass(editingItem.id, classPayload)
          : await masterService.createClass(classPayload);
      }

      if (res?.ok) {
        showToast(editingItem ? 'Data berhasil diperbarui.' : 'Data berhasil ditambahkan.');
        closeForm();
        await reloadActive();
      } else {
        formError = res?.data?.message ?? 'Gagal menyimpan data.';
      }
    } catch {
      formError = 'Gagal terhubung ke server.';
    }

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
      showToast('Gagal terhubung ke server.', 'error');
      deleteLoading = false; showConfirm = false;
      return;
    }
    deleteLoading = false; showConfirm = false;
    if (res?.ok) {
      showToast('Data berhasil dihapus.');
      await reloadActive();
    } else {
      showToast(res?.data?.message ?? 'Gagal menghapus data.', 'error');
    }
  }

  // ----------------------------------------------------------------
  // Computed
  // ----------------------------------------------------------------
  $: isReadOnly  = activeTab === 'semesters' || activeTab === 'years';
  $: isClasses   = activeTab === 'classes';

  $: activeList  = activeTab === 'departments' ? departments
    : activeTab === 'courses'   ? courses
    : activeTab === 'classes'   ? classes
    : activeTab === 'semesters' ? semesters
    : years;

  $: activeLabel = TABS.find(t => t.key === activeTab)?.label ?? '';
  $: tabHasCode  = activeTab !== 'semesters' && activeTab !== 'years';
</script>

<svelte:head><title>Master Data — Absensi</title></svelte:head>

<AppLayout currentPath="/master">
  <div class="page-header">
    <h1>Master Data</h1>
    {#if !isReadOnly}
      <button class="btn btn-primary" on:click={openCreate}>
        + Tambah {activeLabel}
      </button>
    {/if}
  </div>

  <!-- Tabs -->
  <div class="tabs" role="tablist">
    {#each TABS as tab}
      <button
        role="tab"
        class="tab-btn"
        class:active={activeTab === tab.key}
        aria-selected={activeTab === tab.key}
        on:click={() => { activeTab = tab.key; }}
      >
        {tab.label}
        {#if tab.key === 'semesters' || tab.key === 'years'}
          <span class="readonly-badge">read-only</span>
        {/if}
      </button>
    {/each}
  </div>

  <!-- Content -->
  <div class="card tab-content">
    {#if loading}
      <Spinner />
    {:else if error}
      <div class="alert alert-error" role="alert">{error}</div>
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
              <th style="width:60px">#</th>
              {#if tabHasCode}<th style="width:110px">Kode</th>{/if}
              <th>Nama</th>
              {#if isClasses}
                <th style="width:130px">Semester</th>
                <th style="width:80px">Tahun</th>
              {/if}
              {#if !isReadOnly}<th style="width:120px;text-align:right">Aksi</th>{/if}
            </tr>
          </thead>
          <tbody>
            {#each activeList as item, i (item.id)}
              <tr>
                <td style="color:var(--gray-400);font-size:.8rem">{i + 1}</td>
                {#if tabHasCode}
                  <td><span class="badge">{item.code}</span></td>
                {/if}
                <td style="font-weight:500">{item.name}</td>
                {#if isClasses}
                  <td>{semesterName(item.semester_id)}</td>
                  <td>{item.year ?? '-'}</td>
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
     ---------------------------------------------------------------- -->
{#if showForm}
  <!-- svelte-ignore a11y-click-events-have-key-events a11y-no-static-element-interactions -->
  <div class="modal-backdrop" role="presentation" on:click|self={() => !formLoading && closeForm()}>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="master-form-title">
      <div class="modal-header">
        <h3 id="master-form-title">{editingItem ? 'Edit' : 'Tambah'} {activeLabel}</h3>
        <button class="modal-close" on:click={closeForm} aria-label="Tutup" disabled={formLoading}>✕</button>
      </div>

      <form on:submit={handleSubmit} novalidate>
        <div class="modal-body">
          {#if formError}
            <div class="alert alert-error" role="alert">{formError}</div>
          {/if}

          <!-- Kode -->
          <div class="form-group">
            <label for="f-code">Kode</label>
            <input
              id="f-code"
              type="text"
              class="form-control"
              bind:value={formCode}
              placeholder={activeTab === 'departments' ? 'cth: TI' : activeTab === 'courses' ? 'cth: IF101' : 'cth: TI-A'}
              maxlength="20"
              required
            />
          </div>

          <!-- Nama -->
          <div class="form-group">
            <label for="f-name">Nama</label>
            <input
              id="f-name"
              type="text"
              class="form-control"
              bind:value={formName}
              placeholder={activeTab === 'departments' ? 'cth: Teknik Informatika' : activeTab === 'courses' ? 'cth: Pemrograman Web' : 'cth: Teknik Informatika A'}
              maxlength="150"
              required
            />
          </div>

          <!-- Semester & Tahun — hanya untuk tab Kelas -->
          {#if isClasses}
            <div class="form-row">
              <div class="form-group">
                <label for="f-semester">Semester</label>
                <select id="f-semester" class="form-control" bind:value={formSemesterId} required>
                  <option value="" disabled>Pilih semester</option>
                  {#each semesters as s}
                    <option value={s.id}>{s.name}</option>
                  {/each}
                </select>
              </div>
              <div class="form-group">
                <label for="f-year">Tahun</label>
                <select id="f-year" class="form-control" bind:value={formYear} required>
                  <option value="" disabled>Pilih tahun</option>
                  {#each years as y}
                    <option value={y.id}>{y.name}</option>
                  {/each}
                </select>
              </div>
            </div>
          {/if}
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" on:click={closeForm} disabled={formLoading}>
            Batal
          </button>
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
  .tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
    border-bottom: 2px solid var(--gray-200);
    flex-wrap: wrap;
  }
  .tab-btn {
    padding: 8px 18px;
    font-size: .875rem;
    font-weight: 500;
    color: var(--gray-500);
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    transition: color .15s, border-color .15s;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .tab-btn:hover  { color: var(--gray-800); }
  .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); font-weight: 600; }

  .readonly-badge {
    font-size: .65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .03em;
    background: var(--gray-200);
    color: var(--gray-500);
    padding: 1px 6px;
    border-radius: 99px;
  }

  .tab-content { margin-top: 0; }

  .badge {
    display: inline-block;
    font-family: monospace;
    font-size: .8rem;
    font-weight: 600;
    background: var(--gray-100);
    color: var(--gray-700);
    padding: 2px 8px;
    border-radius: 4px;
  }

  /* Two-column row inside modal */
  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 16px 24px;
    border-top: 1px solid var(--gray-200);
  }
</style>
