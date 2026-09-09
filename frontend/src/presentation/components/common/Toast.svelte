<script>
  export let message = '';
  export let type = 'success'; // success | error | info
  export let visible = false;

  let timer;
  $: if (visible) {
    clearTimeout(timer);
    timer = setTimeout(() => { visible = false; }, 3500);
  }
</script>

{#if visible}
  <div class="toast toast-{type}" role="alert">
    {message}
    <button on:click={() => visible = false} aria-label="Tutup">✕</button>
  </div>
{/if}

<style>
  .toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 2000;
    padding: 12px 20px; border-radius: 8px; font-size: .875rem;
    font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,.15);
    display: flex; align-items: center; gap: 12px; max-width: 360px;
    animation: slideIn .2s ease;
  }
  .toast button {
    background: none; border: none; cursor: pointer;
    font-size: 1rem; opacity: .7; color: inherit; padding: 0;
  }
  .toast-success { background: #16a34a; color: #fff; }
  .toast-error   { background: #dc2626; color: #fff; }
  .toast-info    { background: #2563eb; color: #fff; }
  @keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to   { transform: translateX(0);   opacity: 1; }
  }
</style>
