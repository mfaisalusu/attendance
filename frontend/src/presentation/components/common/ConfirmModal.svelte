<script>
  export let visible = false;
  export let title = 'Konfirmasi';
  export let message = 'Apakah Anda yakin?';
  export let onConfirm = () => {};
  export let loading = false;
</script>

{#if visible}
  <!-- svelte-ignore a11y-click-events-have-key-events a11y-no-static-element-interactions -->
  <div class="modal-backdrop" role="presentation" on:click|self={() => !loading && (visible = false)}>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
      <div class="modal-header">
        <h3 id="confirm-title">{title}</h3>
        <button class="modal-close" on:click={() => !loading && (visible = false)} aria-label="Tutup">✕</button>
      </div>
      <div class="modal-body">
        <p>{message}</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" on:click={() => visible = false} disabled={loading}>Batal</button>
        <button class="btn btn-danger" on:click={onConfirm} disabled={loading}>
          {loading ? 'Memproses...' : 'Ya, Hapus'}
        </button>
      </div>
    </div>
  </div>
{/if}
