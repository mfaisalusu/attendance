<script>
  import { onMount } from 'svelte';
  import AppLayout          from '../../layouts/AppLayout.svelte';
  import ConfirmModal       from '../../components/common/ConfirmModal.svelte';
  import MaterialViewerModal from '../../components/common/MaterialViewerModal.svelte';
  import CustomSelect       from '../../components/common/CustomSelect.svelte';
  import Toast              from '../../components/common/Toast.svelte';
  import { materialApi }    from '../../../infrastructure/api/materialApi.js';
  import { masterService }  from '../../../application/services/masterService.js';

  let courses      = [];
  let selectedCourseId = '';
  let materials   = [];
  let pagination  = { page: 1, total_pages: 1, total: 0, limit: 20 };
  let loading     = true;
  let error       = '';

  $: courseOptions = courses.map(c => ({ value: String(c.id), label: `${c.code} — ${c.name}` }));

  // Form state
  let showForm    = false;
  let meetingName = '';
  let fileInput;
  let selectedFile = null;
  let uploading   = false;

  // Delete state
  let showConfirm   = false;
  let deletingId    = null;
  let deleteLoading = false;

  // Viewer state
  let showViewer    = false;
  let viewingMaterial = null;

  function openViewer(m) { viewingMaterial = m; showViewer = true; }

  // Toast
  let toastMsg     = '';
  let toastType    = 'success';
  let toastVisible = false;
  function showToast(msg, type = 'success') { toastMsg = msg; toastType = type; toastVisible = true; }

  onMount(async () => {
    courses = await masterService.getCourses();
    if (courses.length > 0) {
      selectedCourseId = String(courses[0].id);
      await loadMaterials();
    }
    loading = false;
  });

  async function loadMaterials(page = 1) {
    if (!selectedCourseId) return;
    loading = true; error = '';
    const res = await materialApi.list({
      page, limit: 20,
      course_id: selectedCourseId,
    });
    if (res?.ok) {
      materials   = res.data.data.items;
      pagination = res.data.data.pagination;
    } else {
      error = 'Gagal memuat data materi.';
    }
    loading = false;
  }

  function onCourseChange() {
    loadMaterials(1);
  }

  function handleFileSelect(e) {
    const file = e.target.files?.[0];
    if (file) {
      // Validate file type (docx only)
      const validTypes = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword'
      ];
      if (!validTypes.includes(file.type)) {
        showToast('Format harus .docx (Word)', 'error');
        fileInput.value = '';
        selectedFile = null;
        return;
      }
      // Validate size (10MB max)
      if (file.size > 10 * 1024 * 1024) {
        showToast('Ukuran file maksimal 10MB', 'error');
        fileInput.value = '';
        selectedFile = null;
        return;
      }
      selectedFile = file;
    }
  }

  async function submitMaterial() {
    if (!selectedCourseId) {
      showToast('Pilih mata kuliah terlebih dahulu.', 'error');
      return;
    }
    if (!meetingName.trim()) {
      showToast('Nama pertemuan wajib diisi.', 'error');
      return;
    }
    if (!selectedFile) {
      showToast('Pilih file materi (.docx).', 'error');
      return;
    }

    uploading = true;
    const formData = new FormData();
    formData.append('course_id', selectedCourseId);
    formData.append('meeting_name', meetingName.trim());
    formData.append('file', selectedFile);

    const res = await materialApi.create(formData);
    uploading = false;

    if (res?.ok) {
      showToast('Materi berhasil ditambahkan.');
      resetForm();
      loadMaterials(1);
    } else {
      showToast(res?.data?.message || 'Gagal menambahkan materi.', 'error');
    }
  }

  function resetForm() {
    showForm = false;
    meetingName = '';
    selectedFile = null;
    if (fileInput) fileInput.value = '';
  }

  function confirmDelete(id) { deletingId = id; showConfirm = true; }
  async function doDelete() {
    deleteLoading = true;
    const res = await materialApi.delete(deletingId);
    deleteLoading = false; showConfirm = false;
    if (res?.ok) {
      showToast('Materi berhasil dihapus.');
      loadMaterials(pagination.page);
    } else {
      showToast('Gagal menghapus materi.', 'error');
    }
  }

  function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  }

  function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
      day: '2-digit', month: 'short', year: 'numeric'
    });
  }

  function openForm() {
    meetingName = '';
    selectedFile = null;
    if (fileInput) fileInput.value = '';
    showForm = true;
  }
</script>

<svelte:head><title>Mata Kuliah — Absensi</title></svelte:head>

<AppLayout currentPath="/materials">

  <!-- Header -->
  <div class="page-header">
    <div class="header-left">
      <div class="page-eyebrow">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
        </svg>
        Materi Kuliah
      </div>
      <h1>Kelola Materi Mata Kuliah</h1>
    </div>
    <button class="btn btn-primary" on:click={openForm} disabled={!selectedCourseId}>
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Tambah Materi
    </button>
  </div>

  <!-- Course selector -->
  <div class="course-selector">
    <div class="form-group" style="flex:1;max-width:400px">
      <label for="course-select">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
        </svg>
        Mata Kuliah
      </label>
      <CustomSelect
        id="course-select"
        bind:value={selectedCourseId}
        options={courseOptions}
        on:change={onCourseChange}
      />
    </div>
  </div>

  <!-- Add Material Form (inline) -->
  {#if showForm}
    <div class="card form-card">
      <h3>Tambah Materi Baru</h3>
      <div class="form-grid">
        <div class="form-group">
          <label for="meeting-name">
            Nama Pertemuan <span class="required">*</span>
          </label>
          <input
            id="meeting-name"
            type="text"
            class="form-control"
            bind:value={meetingName}
            placeholder="Contoh: Pertemuan 1 - Pengantar"
          />
        </div>
        <div class="form-group">
          <label for="file-upload">
            File Materi (.docx) <span class="required">*</span>
          </label>
          <input
            id="file-upload"
            type="file"
            class="form-control file-input"
            accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            bind:this={fileInput}
            on:change={handleFileSelect}
          />
          {#if selectedFile}
            <div class="file-preview">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
              </svg>
              <span>{selectedFile.name}</span>
              <span class="file-size">({formatFileSize(selectedFile.size)})</span>
            </div>
          {/if}
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn-secondary" on:click={resetForm} disabled={uploading}>
          Batal
        </button>
        <button class="btn btn-primary" on:click={submitMaterial} disabled={uploading}>
          {#if uploading}
            <span class="spinner"></span>
            Mengupload...
          {:else}
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
              <polyline points="17 8 12 3 7 8"/>
              <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            Upload Materi
          {/if}
        </button>
      </div>
    </div>
  {/if}

  <!-- Content card -->
  <div class="card">
    {#if !selectedCourseId}
      <div class="state-wrap">
        <p>Pilih mata kuliah untuk melihat materi.</p>
      </div>

    {:else if loading}
      <div class="state-wrap">
        <div class="state-spinner"></div>
        <span>Memuat data materi...</span>
      </div>

    {:else if error}
      <div class="alert alert-error" role="alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        {error}
      </div>

    {:else if materials.length === 0}
      <div class="state-wrap state-empty">
        <div class="empty-icon">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
        </div>
        <p class="empty-title">Belum ada materi</p>
        <p class="empty-sub">Tambahkan materi untuk mata kuliah ini.</p>
      </div>

    {:else}
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Nama Pertemuan</th>
              <th>File</th>
              <th>Ukuran</th>
              <th>Tanggal Upload</th>
              <th style="text-align:right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {#each materials as m, i (m.id)}
              <tr>
                <td class="td-num">
                  {(pagination.page - 1) * pagination.limit + i + 1}
                </td>
                <td class="td-name">
                  {m.meeting_name}
                </td>
                <td>
                  <span class="file-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                      <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    {m.file_name}
                  </span>
                </td>
                <td class="td-size">{formatFileSize(m.file_size)}</td>
                <td class="td-date">{formatDate(m.created_at)}</td>
                <td class="td-actions">
                  <button
                    class="action-btn action-view"
                    title="Lihat"
                    on:click={() => openViewer(m)}
                  >
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                  <a
                    href={materialApi.download(m.id)}
                    target="_blank"
                    class="action-btn action-download"
                    title="Download"
                  >
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                      <polyline points="7 10 12 15 17 10"/>
                      <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                  </a>
                  <button class="action-btn action-delete" on:click={() => confirmDelete(m.id)} title="Hapus">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                      <path d="M10 11v6"/><path d="M14 11v6"/>
                    </svg>
                  </button>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {/if}
  </div>

</AppLayout>

<ConfirmModal
  bind:visible={showConfirm}
  title="Hapus Materi"
  message="Materi ini akan dihapus permanen. Lanjutkan?"
  onConfirm={doDelete}
  loading={deleteLoading}
/>
<MaterialViewerModal
  bind:visible={showViewer}
  material={viewingMaterial}
/>
<Toast bind:visible={toastVisible} message={toastMsg} type={toastType} />

<style>
  /* ── Header ── */
  .page-eyebrow {
    display: flex; align-items: center; gap: 5px;
    font-size: .7rem; font-weight: 700; color: #6ee7b7;
    text-transform: uppercase; letter-spacing: .08em;
    margin-bottom: 4px;
  }
  .page-eyebrow svg { opacity: .8; }
  .header-left h1 {
    font-size: 1.35rem; font-weight: 800;
    color: #f9fafb; letter-spacing: -.02em;
  }

  /* ── Course selector ── */
  .course-selector {
    display: flex; gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
  }

  /* ── Form card ── */
  .form-card {
    margin-bottom: 20px;
    background: rgba(110,231,183,.03);
    border: 1px solid rgba(110,231,183,.12);
  }
  .form-card h3 {
    font-size: 1rem; font-weight: 700;
    color: #f9fafb; margin-bottom: 16px;
  }
  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }
  @media (max-width: 640px) {
    .form-grid { grid-template-columns: 1fr; }
  }
  .file-input {
    padding: 6px 10px;
  }
  .file-preview {
    display: flex; align-items: center; gap: 6px;
    margin-top: 8px; padding: 8px 10px;
    background: rgba(110,231,183,.08);
    border-radius: 6px;
    font-size: .8rem; color: #a7f3d0;
  }
  .file-size { color: #6b7280; font-size: .75rem; }
  .required { color: #f87171; }

  .form-actions {
    display: flex; justify-content: flex-end; gap: 10px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid rgba(110,231,183,.1);
  }

  /* ── Spinner ── */
  .spinner {
    width: 14px; height: 14px;
    border: 2px solid rgba(255,255,255,.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    display: inline-block;
    margin-right: 6px;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ── State (loading / empty) ── */
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

  .state-empty { gap: 8px; }
  .empty-icon {
    width: 64px; height: 64px; border-radius: 50%;
    background: rgba(110,231,183,.06);
    border: 1px solid rgba(110,231,183,.12);
    display: flex; align-items: center; justify-content: center;
    color: rgba(110,231,183,.4); margin-bottom: 4px;
  }
  .empty-title { font-size: .95rem; font-weight: 600; color: #9ca3af; }
  .empty-sub   { font-size: .8rem; color: #4b5563; }

  /* ── Table ── */
  .td-num { color: #4b5563; font-size: .78rem; font-weight: 600; width: 40px; }
  .td-name { font-weight: 500; color: #f9fafb; }
  .td-size { color: #9ca3af; font-size: .85rem; }
  .td-date { color: #9ca3af; font-size: .85rem; }

  .file-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 9px;
    background: rgba(96,165,250,.1);
    border: 1px solid rgba(96,165,250,.2);
    border-radius: 6px;
    font-size: .78rem; font-weight: 500; color: #60a5fa;
  }

  /* ── Action buttons (icon-only) ── */
  .td-actions { text-align: right; white-space: nowrap; }
  .action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px;
    padding: 0;
    border-radius: 7px;
    font-size: .775rem; font-weight: 600; cursor: pointer;
    border: 1px solid transparent; font-family: inherit;
    transition: background .15s, box-shadow .15s, transform .1s;
    margin-left: 5px;
    text-decoration: none;
  }
  .action-btn:hover { transform: translateY(-1px); }

  .action-view {
    background: rgba(167,243,208,.08);
    border-color: rgba(167,243,208,.2);
    color: #6ee7b7;
  }
  .action-view:hover {
    background: rgba(167,243,208,.15);
    box-shadow: 0 2px 10px rgba(167,243,208,.15);
  }

  .action-download {
    background: rgba(96,165,250,.1);
    border-color: rgba(96,165,250,.2);
    color: #60a5fa;
  }
  .action-download:hover {
    background: rgba(96,165,250,.2);
    box-shadow: 0 2px 10px rgba(96,165,250,.15);
  }

  .action-delete {
    background: rgba(248,113,113,.08);
    border-color: rgba(248,113,113,.18);
    color: #f87171;
  }
  .action-delete:hover {
    background: rgba(248,113,113,.15);
    box-shadow: 0 2px 10px rgba(248,113,113,.1);
  }

  /* ── Responsive ── */
  @media (max-width: 768px) {
    .header-left h1 { font-size: 1.15rem; }
  }
  @media (max-width: 580px) {
    .td-num, thead th:first-child { display: none; }
    .file-badge { font-size: .7rem; padding: 2px 6px; }
    .td-size, .td-date { 
      font-size: .75rem;
      color: #6b7280;
    }
    thead th:nth-child(4),
    thead th:nth-child(5) { font-size: .7rem; }
  }
</style>