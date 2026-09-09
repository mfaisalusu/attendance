<script>
  import { onMount } from 'svelte';
  import { masterService } from '../../../application/services/masterService.js';
  import { studentApi } from '../../../infrastructure/api/studentApi.js';

  export let visible = false;
  export let student = null;   // null = create, object = edit
  export let onSaved = () => {};

  let departments = [], courses = [], classes = [], semesters = [];
  let form = defaultForm();
  let loading = false;
  let error = '';
  let fieldErrors = {};

  function defaultForm() {
    return { nip: '', name: '', department_id: '', course_id: '', class_id: '', semester_id: '' };
  }

  onMount(async () => {
    [departments, courses, classes, semesters] = await Promise.all([
      masterService.getDepartments(),
      masterService.getCourses(),
      masterService.getClasses(),
      masterService.getSemesters(),
    ]);
  });

  $: if (visible) {
    error = '';
    fieldErrors = {};
    form = student
      ? {
          nip:           student.nip,
          name:          student.name,
          department_id: student.department_id,
          course_id:     student.course_id,
          class_id:      student.class_id,
          semester_id:   student.semester_id,
        }
      : defaultForm();
  }

  async function handleSubmit(e) {
    e.preventDefault();
    error = '';
    fieldErrors = {};
    loading = true;

    const payload = {
      ...form,
      department_id: Number(form.department_id),
      course_id:     Number(form.course_id),
      class_id:      Number(form.class_id),
      semester_id:   Number(form.semester_id),
    };

    try {
      const res = student
        ? await studentApi.update(student.id, payload)
        : await studentApi.create(payload);

      if (res?.ok) {
        onSaved(res.data.data);
        visible = false;
      } else {
        error = res?.data?.message ?? 'Gagal menyimpan data.';
        fieldErrors = res?.data?.errors ?? {};
      }
    } catch {
      error = 'Gagal terhubung ke server.';
    } finally {
      loading = false;
    }
  }
</script>

{#if visible}
  <!-- svelte-ignore a11y-click-events-have-key-events a11y-no-static-element-interactions -->
  <div class="modal-backdrop" role="presentation" on:click|self={() => !loading && (visible = false)}>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="student-modal-title">
      <div class="modal-header">
        <h3 id="student-modal-title">{student ? 'Edit Mahasiswa' : 'Tambah Mahasiswa'}</h3>
        <button class="modal-close" on:click={() => visible = false} aria-label="Tutup">✕</button>
      </div>

      <form on:submit={handleSubmit} novalidate>
        <div class="modal-body">
          {#if error}
            <div class="alert alert-error" role="alert">{error}</div>
          {/if}

          <div class="form-group">
            <label for="f-nip">NIP</label>
            <input id="f-nip" type="text" class="form-control" class:is-invalid={fieldErrors.nip}
              bind:value={form.nip} placeholder="cth. 2021001" required />
            {#if fieldErrors.nip}<span class="invalid-feedback">{fieldErrors.nip[0]}</span>{/if}
          </div>

          <div class="form-group">
            <label for="f-name">Nama Lengkap</label>
            <input id="f-name" type="text" class="form-control" class:is-invalid={fieldErrors.name}
              bind:value={form.name} placeholder="cth. Budi Santoso" required />
            {#if fieldErrors.name}<span class="invalid-feedback">{fieldErrors.name[0]}</span>{/if}
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group">
              <label for="f-dept">Jurusan</label>
              <select id="f-dept" class="form-control" bind:value={form.department_id} required>
                <option value="">Pilih Jurusan</option>
                {#each departments as d}
                  <option value={d.id}>{d.name}</option>
                {/each}
              </select>
            </div>

            <div class="form-group">
              <label for="f-course">Mata Kuliah</label>
              <select id="f-course" class="form-control" bind:value={form.course_id} required>
                <option value="">Pilih Mata Kuliah</option>
                {#each courses as c}
                  <option value={c.id}>{c.name}</option>
                {/each}
              </select>
            </div>

            <div class="form-group">
              <label for="f-class">Kelas</label>
              <select id="f-class" class="form-control" bind:value={form.class_id} required>
                <option value="">Pilih Kelas</option>
                {#each classes as cl}
                  <option value={cl.id}>{cl.name}</option>
                {/each}
              </select>
            </div>

            <div class="form-group">
              <label for="f-semester">Semester</label>
              <select id="f-semester" class="form-control" bind:value={form.semester_id} required>
                <option value="">Pilih Semester</option>
                {#each semesters as s}
                  <option value={s.id}>{s.name}</option>
                {/each}
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" on:click={() => visible = false} disabled={loading}>Batal</button>
          <button type="submit" class="btn btn-primary" disabled={loading}>
            {loading ? 'Menyimpan...' : (student ? 'Simpan Perubahan' : 'Tambah Mahasiswa')}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
