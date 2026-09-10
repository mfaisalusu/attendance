<script>
  export let visible  = false;
  export let title    = 'Konfirmasi';
  export let message  = 'Apakah Anda yakin?';
  export let onConfirm = () => {};
  export let loading  = false;
</script>

{#if visible}
  <!-- svelte-ignore a11y-click-events-have-key-events a11y-no-static-element-interactions -->
  <div class="modal-backdrop" role="presentation" on:click|self={() => !loading && (visible = false)}>
    <div class="modal confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">

      <div class="modal-header">
        <div class="confirm-title-wrap">
          <div class="confirm-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
              <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
          </div>
          <h3 id="confirm-title">{title}</h3>
        </div>
        <button class="modal-close" on:click={() => !loading && (visible = false)} aria-label="Tutup">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div class="modal-body">
        <p class="confirm-msg">{@html message}</p>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" on:click={() => (visible = false)} disabled={loading}>
          Batal
        </button>
        <button class="btn btn-danger" on:click={onConfirm} disabled={loading}>
          {#if loading}
            <span class="btn-spinner"></span>
            Memproses...
          {:else}
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
              <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
            </svg>
            Ya, Hapus
          {/if}
        </button>
      </div>

    </div>
  </div>
{/if}

<style>
  .confirm-modal { max-width: 420px; }

  .confirm-title-wrap {
    display: flex; align-items: center; gap: 10px;
  }
  .confirm-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(248,113,113,.1);
    border: 1px solid rgba(248,113,113,.2);
    display: flex; align-items: center; justify-content: center;
    color: #f87171; flex-shrink: 0;
  }

  .confirm-msg {
    font-size: .875rem; color: #9ca3af; line-height: 1.65;
  }

  .btn-spinner {
    width: 13px; height: 13px;
    border: 2px solid rgba(248,113,113,.3);
    border-top-color: #f87171; border-radius: 50%;
    animation: spin .6s linear infinite; flex-shrink: 0;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
