<script>
  export let message = '';
  export let type    = 'success'; // success | error | info | warning
  export let visible = false;

  let timer;
  $: if (visible) {
    clearTimeout(timer);
    timer = setTimeout(() => { visible = false; }, 3500);
  }

  const icons = {
    success: `<polyline points="20 6 9 17 4 12"/>`,
    error:   `<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>`,
    info:    `<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>`,
    warning: `<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>`,
  };
</script>

{#if visible}
  <div class="toast toast-{type}" role="alert" aria-live="polite">
    <div class="toast-icon">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        {@html icons[type] ?? icons.info}
      </svg>
    </div>
    <span class="toast-msg">{message}</span>
    <button class="toast-close" on:click={() => (visible = false)} aria-label="Tutup">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
{/if}

<style>
  .toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 2000;
    padding: 12px 14px 12px 14px;
    border-radius: 12px;
    font-size: .855rem; font-weight: 500;
    display: flex; align-items: center; gap: 10px;
    max-width: 360px; min-width: 240px;
    border: 1px solid transparent;
    backdrop-filter: blur(8px);
    animation: slideIn .22s cubic-bezier(.34,1.56,.64,1);
    box-shadow: 0 8px 28px rgba(0,0,0,.4);
  }
  @keyframes slideIn {
    from { transform: translateX(110%); opacity: 0; }
    to   { transform: translateX(0);   opacity: 1; }
  }

  .toast-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .toast-msg { flex: 1; line-height: 1.4; }
  .toast-close {
    background: none; border: none; cursor: pointer;
    color: inherit; opacity: .5; padding: 2px;
    display: flex; align-items: center;
    transition: opacity .15s;
  }
  .toast-close:hover { opacity: 1; }

  .toast-success {
    background: rgba(17,24,39,.92);
    border-color: rgba(52,211,153,.25);
    color: #d1fae5;
  }
  .toast-success .toast-icon {
    background: rgba(52,211,153,.15); color: #34d399;
  }

  .toast-error {
    background: rgba(17,24,39,.92);
    border-color: rgba(248,113,113,.25);
    color: #fee2e2;
  }
  .toast-error .toast-icon {
    background: rgba(248,113,113,.15); color: #f87171;
  }

  .toast-info {
    background: rgba(17,24,39,.92);
    border-color: rgba(96,165,250,.25);
    color: #dbeafe;
  }
  .toast-info .toast-icon {
    background: rgba(96,165,250,.15); color: #60a5fa;
  }

  .toast-warning {
    background: rgba(17,24,39,.92);
    border-color: rgba(251,191,36,.25);
    color: #fef3c7;
  }
  .toast-warning .toast-icon {
    background: rgba(251,191,36,.15); color: #fbbf24;
  }
</style>
