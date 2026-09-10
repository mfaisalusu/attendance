<script>
  import { onMount } from 'svelte';
  import { masterService } from '../../../application/services/masterService.js';
  import { studentApi }    from '../../../infrastructure/api/studentApi.js';

  export let visible = false;
  export let student = null;
  export let onSaved = () => {};

  let classes     = [];
  let form        = defaultForm();
  let loading     = false;
  let error       = '';
  let fieldErrors = {};

  function defaultForm() { return { nip: '', name: '', class_id: '' }; }

  onMount(async () => { classes = await masterService.getClasses(); });

  $: if (visible) {
    error       = '';
    fieldErrors = {};
    form = student
      ? { nip: student.nip, name: student.name, class_id: student.class_id }
      : defaultForm();
  }

  async function handleSubmit(e) {
    e.preventDefault();
    error = ''; fieldErrors = {}; loading = true;
    const payload = { nip: form.nip, name: form.name, class_id: Number(form.class_id) };
    try {
      const res = student
        ? await studentApi.update(student.id, payload)
        : await studentApi.create(payload);
      if (res?.ok) { onSaved(res.data.data); visible = false; }
      else {
        error       = res?.data?.message ?? 'Gagal menyimpan data.';
        fieldErrors = res?.data?.errors  ?? {};
      }
    } catch { error = 'Gagal terhubung ke server.'; }
    finally  { loading = false; }
  }
</script>

{#if visible}
  <!-- svelte-ignore a11y-click-events-have-key-events a11y-no-static-element-interactions -->
  <div class="modal-backdrop" role="presentation" on:click|self={() => !loading && (visible = false)}>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="student-modal-title">

      <div class="modal-header">
        <div class="modal-title-wrap">
          <div class="modal-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
              {#if student}
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
              {:else}
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
              {/if}
            </svg>
          </div>
          <h3 id="student-modal-title">{student ? 'Edit Mahasiswa' : 'Tambah Mahasiswa'}</h3>
        </div>
        <button class="modal-close" on:click={() => !loading && (visible = false)} aria-label="Tutup">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <form on:submit={handleSubmit} novalidate>
        <div class="modal-body">
          {#if error}
            <div class="alert alert-error" role="alert">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              {error}
            </div>
          {/if}

          <div class="form-group">
            <label for="f-nip">NIP</label>
            <input id="f-nip" type="text" class="form-control"
              class:is-invalid={fieldErrors.nip}
              bind:value={form.nip} placeholder="cth. 2021001" required />
            {#if fieldErrors.nip}
              <span class="invalid-feedback">{fieldErrors.nip[0]}</span>
            {/if}
          </div>

          <div class="form-group">
            <label for="f-name">Nama Lengkap</label>
            <input id="f-name" type="text" class="form-control"
              class:is-invalid={fieldErrors.name}
              bind:value={form.name} placeholder="cth. Budi Santoso" required />
            {#if fieldErrors.name}
              <span class="invalid-feedback">{fieldErrors.name[0]}</span>
            {/if}
          </div>

          <div class="form-group">
            <label for="f-class">Kelas</label>
            <select id="f-class" class="form-control"
              class:is-invalid={fieldErrors.class_id}
              bind:value={form.class_id} required>
              <option value="" disabled>Pilih kelas</option>
              {#each classes as cl}
                <option value={cl.id}>{cl.code} — {cl.name}</option>
              {/each}
            </select>
            {#if fieldErrors.class_id}
              <span class="invalid-feedback">{fieldErrors.class_id[0]}</span>
            {/if}
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary"
            on:click={() => (visible = false)} disabled={loading}>Batal</button>
          <button type="submit" class="btn btn-primary" disabled={loading}>
            {#if loading}
              <span class="btn-spinner"></span>
              Menyimpan...
            {:else}
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              {student ? 'Simpan Perubahan' : 'Tambah Mahasiswa'}
            {/if}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<style>
  .modal-title-wrap {
    display: flex; align-items: center; gap: 10px;
  }
  .modal-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(110,231,183,.1);
    border: 1px solid rgba(110,231,183,.2);
    display: flex; align-items: center; justify-content: center;
    color: #6ee7b7; flex-shrink: 0;
  }
  .btn-spinner {
    width: 13px; height: 13px;
    border: 2px solid rgba(10,34,24,.3);
    border-top-color: #0a2218; border-radius: 50%;
    animation: spin .6s linear infinite; flex-shrink: 0;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
