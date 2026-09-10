<script>
  import { onMount } from 'svelte';
  import AppLayout        from '../../layouts/AppLayout.svelte';
  import Spinner          from '../../components/common/Spinner.svelte';
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

  // Filter: hanya kelas (wajib, default id terkecil)
  let classes  = [];
  let classId  = '';   // diisi setelah data loaded
  let search   = '';

  // Modals
  let showForm       = false;
  let editingStudent = null;
  let showConfirm    = false;
  let deletingId     = null;
  let deleteLoading  = false;

  // Toast
  let toastMsg     = '';
  let toastType    = 'success';
  let toastVisible = false;
  function showToast(msg, type = 'success') { toastMsg = msg; toastType = type; toastVisible = true; }

  onMount(async () => {
    classes = await masterService.getClasses();
    // Default ke id terkecil
    if (classes.length > 0) {
      const minId = Math.min(...classes.map(c => c.id));
      classId = String(minId);
    }
    await loadStudents();
  });

  async function loadStudents(page = 1) {
    loading = true;
    error   = '';
    const res = await studentApi.list({
      page,
      limit:    20,
      search:   search   || undefined,
      class_id: classId  || undefined,
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

  function onSaved() {
    showToast(editingStudent ? 'Mahasiswa berhasil diperbarui.' : 'Mahasiswa berhasil ditambahkan.');
    loadStudents(pagination.page);
  }

  function confirmDelete(id) { deletingId = id; showConfirm = true; }

  async function doDelete() {
    deleteLoading = true;
    const res = await studentApi.delete(deletingId);
    deleteLoading = false;
    showConfirm   = false;
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
  <div class="page-header">
    <h1>Data Mahasiswa</h1>
    <button class="btn btn-primary" on:click={openCreate}>+ Tambah Mahasiswa</button>
  </div>

  <!-- Filter bar: hanya search + kelas -->
  <div class="filter-bar">
    <div class="form-group">
      <label for="s-search">Cari</label>
      <input id="s-search" type="search" class="form-control" bind:value={search}
        on:input={onSearchInput} on:search={onSearchInput} placeholder="NIP atau nama..." />
    </div>
    <div class="form-group">
      <label for="s-class">Kelas <span style="color:var(--danger)">*</span></label>
      <select id="s-class" class="form-control" bind:value={classId}
        on:change={() => loadStudents(1)}>
        <option value="">Semua Kelas</option>
        {#each classes as cl}
          <option value={String(cl.id)}>{cl.code}</option>
        {/each}
      </select>
    </div>
  </div>

  <div class="card">
    {#if loading}
      <Spinner />
    {:else if error}
      <div class="alert alert-error" role="alert">{error}</div>
    {:else if students.length === 0}
      <div class="empty-wrap">
        <span style="font-size:2rem">👥</span>
        <p>Belum ada mahasiswa{classId ? ' di kelas ini' : ''}.</p>
        <button class="btn btn-primary btn-sm" on:click={openCreate}>Tambah Mahasiswa</button>
      </div>
    {:else}
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>NIP</th>
              <th>Nama</th>
              <th>Kelas</th>
              <th style="text-align:right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {#each students as s (s.id)}
              <tr>
                <td><code style="font-size:.8rem">{s.nip}</code></td>
                <td style="font-weight:500">{s.name}</td>
                <td>{s.class?.name ?? '-'} <span style="color:var(--gray-400);font-size:.78rem">({s.class?.code ?? '-'})</span></td>
                <td style="text-align:right;white-space:nowrap">
                  <button class="btn btn-secondary btn-sm" on:click={() => openEdit(s)}>Edit</button>
                  <button class="btn btn-danger btn-sm"    on:click={() => confirmDelete(s.id)}>Hapus</button>
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
