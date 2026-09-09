<script>
  import { authService } from '../../../application/services/authService.js';

  export let navigate;

  let name = '', email = '', password = '', passwordConfirmation = '';
  let loading = false;
  let error = '';
  let fieldErrors = {};
  let success = false;

  async function handleSubmit(e) {
    e.preventDefault();
    error = '';
    fieldErrors = {};
    loading = true;

    try {
      const res = await authService.register(name, email, password, passwordConfirmation);
      if (res?.ok) {
        success = true;
      } else {
        error = res?.data?.message ?? 'Pendaftaran gagal.';
        fieldErrors = res?.data?.errors ?? {};
      }
    } catch {
      error = 'Gagal terhubung ke server.';
    } finally {
      loading = false;
    }
  }
</script>

<svelte:head><title>Daftar — Absensi</title></svelte:head>

<div class="auth-wrap">
  <div class="auth-card card">
    <div class="auth-brand">
      <span style="font-size:2rem">📋</span>
      <h1>Buat Akun</h1>
      <p>Daftar sebagai dosen</p>
    </div>

    {#if success}
      <div class="alert alert-success" role="alert">
        Akun berhasil dibuat! <a href="/login">Silakan login.</a>
      </div>
    {:else}
      {#if error}
        <div class="alert alert-error" role="alert">{error}</div>
      {/if}

      <form on:submit={handleSubmit} novalidate>
        <div class="form-group">
          <label for="name">Nama Lengkap</label>
          <input id="name" type="text" class="form-control" class:is-invalid={fieldErrors.name}
            bind:value={name} placeholder="Dr. Budi Santoso" required autocomplete="name" />
          {#if fieldErrors.name}<span class="invalid-feedback">{fieldErrors.name[0]}</span>{/if}
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" type="email" class="form-control" class:is-invalid={fieldErrors.email}
            bind:value={email} placeholder="dosen@kampus.ac.id" required autocomplete="email" />
          {#if fieldErrors.email}<span class="invalid-feedback">{fieldErrors.email[0]}</span>{/if}
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" type="password" class="form-control" class:is-invalid={fieldErrors.password}
            bind:value={password} placeholder="Minimal 8 karakter" required autocomplete="new-password" />
          {#if fieldErrors.password}<span class="invalid-feedback">{fieldErrors.password[0]}</span>{/if}
        </div>

        <div class="form-group">
          <label for="password_confirmation">Konfirmasi Password</label>
          <input id="password_confirmation" type="password" class="form-control"
            bind:value={passwordConfirmation} placeholder="Ulangi password" required autocomplete="new-password" />
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%" disabled={loading}>
          {loading ? 'Mendaftar...' : 'Daftar'}
        </button>
      </form>

      <p class="auth-footer">
        Sudah punya akun? <a href="/login">Login</a>
      </p>
    {/if}
  </div>
</div>

<style>
  .auth-wrap {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 16px;
  }
  .auth-card { width: 100%; max-width: 420px; }
  .auth-brand { text-align: center; margin-bottom: 24px; }
  .auth-brand h1 { font-size: 1.25rem; font-weight: 700; margin: 8px 0 4px; }
  .auth-brand p { color: var(--gray-500); font-size: .875rem; }
  .auth-footer { text-align: center; margin-top: 16px; font-size: .875rem; color: var(--gray-500); }
</style>
