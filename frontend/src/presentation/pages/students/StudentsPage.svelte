<script>
  import { onMount } from 'svelte';
  import AppLayout        from '../../layouts/AppLayout.svelte';
  import Pagination       from '../../components/common/Pagination.svelte';
  import ConfirmModal     from '../../components/common/ConfirmModal.svelte';
  import Toast            from '../../components/common/Toast.svelte';
  import StudentFormModal from './StudentFormModal.svelte';
  import { studentApi }   from '../../../infrastructure/api/studentApi.js';
  import { masterService } from '../../../application/services/masterService.js';

  let students   = [];
  let pagination = { page: 1, total_pages: 1, total: 0, limit: 20 };
  let loading    = true;
  let error      = '';

  let classes  = [];
  let classId  = '';
  let search   = '';

  let showForm       = false;
  let editingStudent = null;
  let showConfirm    = false;
  let deletingId     = null;
  let deleteLoading  = false;

  let toastMsg     = '';
  let toastType    = 'success';
  let toastVisible = false;
  function showToast(msg, type = 'success') { toastMsg = msg; toastType = type; toastVisible = true; }

  onMount(async () => {
    classes = await masterService.getClasses();
    if (classes.length > 0) {
      const minId = Math.min(...classes.map(c => c.id));
      classId = String(minId);
    }
    await loadStudents();
  });

  async function loadStudents(page = 1) {
    loading = true; error = '';
    const res = await studentApi.list({
      page, limit: 20,
      search:   search  || undefined,
      class_id: classId || undefined,
    });
    if (res?.ok) {
      students   = res.data.data.items;
      pagination = res.data.data.pagination;
    } else {
      error = 'Gagal memuat data mahasiswa.';
    }
    loading = false;
  }

  function handlePageChange(p) { loadStudents(p); }

  let searchTimer;
  function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadStudents(1), 400);
  }

  function openCreate() { editingStudent = null; showForm = true; }
  function openEdit(s)  { editingStudent = s;    showForm = true; }
  function onSaved()    {
    showToast(editingStudent ? 'Mahasiswa berhasil diperbarui.' : 'Mahasiswa berhasil ditambahkan.');
    loadStudents(pagination.page);
  }

  function confirmDelete(id) { deletingId = id; showConfirm = true; }
  async function doDelete() {
    deleteLoading = true;
    const res = await studentApi.delete(deletingId);
    deleteLoading = false; showConfirm = false;
    if (res?.ok) {
      showToast('Mahasiswa berhasil dihapus.');
      loadStudents(pagination.page);
    } else {
      showToast('Gagal menghapus mahasiswa.', 'error');
    }
  }
</script>

<svelte:head><title>Mahasiswa — Absensi</title></svelte:head>

<AppLayout currentPath="/students">

  <!-- Header -->
  <div class="page-header">
    <div class="header-left">
      <div class="page-eyebrow">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        Data Mahasiswa
      </div>
      <h1>Daftar Mahasiswa</h1>
    </div>
    <button class="btn btn-primary" on:click={openCreate}>
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Tambah Mahasiswa
    </button>
  </div>

  <!-- Filter bar -->
  <div class="filter-bar">
    <div class="form-group">
      <label for="s-search">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Cari
      </label>
      <div class="input-icon-wrap">
        <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input id="s-search" type="search" class="form-control has-icon"
          bind:value={search} on:input={onSearchInput} on:search={onSearchInput}
          placeholder="Cari NIP atau nama..." />
      </div>
    </div>
    <div class="form-group">
      <label for="s-class">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        Kelas
      </label>
      <select id="s-class" class="form-control" bind:value={classId} on:change={() => loadStudents(1)}>
        <option value="">Semua Kelas</option>
        {#each classes as cl}
          <option value={String(cl.id)}>{cl.code} — {cl.name}</option>
        {/each}
      </select>
    </div>

    {#if !loading}
      <div class="filter-count">
        <span class="count-badge">{pagination.total}</span>
        <span>mahasiswa ditemukan</span>
      </div>
    {/if}
  </div>

  <!-- Content card -->
  <div class="card">
    {#if loading}
      <div class="state-wrap">
        <div class="state-spinner"></div>
        <span>Memuat data mahasiswa...</span>
      </div>

    {:else if error}
      <div class="alert alert-error" role="alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        {error}
      </div>

    {:else if students.length === 0}
      <div class="state-wrap state-empty">
        <div class="empty-icon">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <p class="empty-title">Belum ada mahasiswa{classId ? ' di kelas ini' : ''}</p>
        <p class="empty-sub">Tambahkan mahasiswa pertama untuk memulai.</p>
        <button class="btn btn-primary btn-sm" on:click={openCreate}>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah Mahasiswa
        </button>
      </div>

    {:else}
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>NIP</th>
              <th>Nama</th>
              <th>Kelas</th>
              <th style="text-align:right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {#each students as s, i (s.id)}
              <tr>
                <td class="td-num">
                  {(pagination.page - 1) * pagination.limit + i + 1}
                </td>
                <td>
                  <span class="nip-badge">{s.nip}</span>
                </td>
                <td class="td-name">
                  <div class="student-avatar">{s.name[0]}</div>
                  {s.name}
                </td>
                <td>
                  <span class="class-badge">
                    {s.class?.code ?? '-'}
                  </span>
                  <span class="class-name">{s.class?.name ?? ''}</span>
                </td>
                <td class="td-actions">
                  <button class="action-btn action-edit" on:click={() => openEdit(s)} title="Edit">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Edit
                  </button>
                  <button class="action-btn action-delete" on:click={() => confirmDelete(s.id)} title="Hapus">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                      <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                      <path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/>
                    </svg>
                    Hapus
                  </button>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
      <Pagination {pagination} onPageChange={handlePageChange} />
    {/if}
  </div>

</AppLayout>

<StudentFormModal bind:visible={showForm} student={editingStudent} {onSaved} />
<ConfirmModal
  bind:visible={showConfirm}
  title="Hapus Mahasiswa"
  message="Mahasiswa ini akan dihapus permanen beserta seluruh data absensinya. Lanjutkan?"
  onConfirm={doDelete}
  loading={deleteLoading}
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

  /* ── Filter bar extra ── */
  .input-icon-wrap { position: relative; }
  .input-icon {
    position: absolute; left: 11px; top: 50%;
    transform: translateY(-50%);
    color: #4b5563; pointer-events: none;
  }
  .form-control.has-icon { padding-left: 34px; }

  .filter-count {
    display: flex; align-items: center; gap: 7px;
    align-self: flex-end; padding-bottom: 1px;
    font-size: .8rem; color: #6b7280;
  }
  .count-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 28px; padding: 2px 7px;
    background: rgba(110,231,183,.1);
    border: 1px solid rgba(110,231,183,.2);
    border-radius: 999px;
    font-size: .75rem; font-weight: 700; color: #6ee7b7;
  }

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
  @keyframes spin { to { transform: rotate(360deg); } }

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

  .nip-badge {
    display: inline-block; padding: 3px 9px;
    background: rgba(110,231,183,.07);
    border: 1px solid rgba(110,231,183,.15);
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: .78rem; font-weight: 700; color: #6ee7b7;
  }

  .td-name {
    display: flex; align-items: center; gap: 10px;
    font-weight: 600; color: #f9fafb;
  }
  .student-avatar {
    width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #34d399, #059669);
    color: #0a2218; font-size: .72rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    text-transform: uppercase;
  }

  .class-badge {
    display: inline-block; padding: 2px 8px;
    background: rgba(96,165,250,.1);
    border: 1px solid rgba(96,165,250,.2);
    border-radius: 6px;
    font-size: .72rem; font-weight: 700; color: #60a5fa;
    margin-right: 6px;
  }
  .class-name { font-size: .8rem; color: #6b7280; }

  /* ── Action buttons ── */
  .td-actions { text-align: right; white-space: nowrap; }
  .action-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 7px;
    font-size: .775rem; font-weight: 600; cursor: pointer;
    border: 1px solid transparent; font-family: inherit;
    transition: background .15s, box-shadow .15s, transform .1s;
    margin-left: 5px;
  }
  .action-btn:hover { transform: translateY(-1px); }

  .action-edit {
    background: rgba(110,231,183,.08);
    border-color: rgba(110,231,183,.18);
    color: #6ee7b7;
  }
  .action-edit:hover {
    background: rgba(110,231,183,.15);
    box-shadow: 0 2px 10px rgba(110,231,183,.1);
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
</style>
