<script>
  export let pagination = { page: 1, total_pages: 1, total: 0, limit: 20 };
  export let onPageChange = (_page) => {};

  $: pages = Array.from({ length: pagination.total_pages }, (_, i) => i + 1);
</script>

{#if pagination.total_pages > 1}
  <div class="pagination">
    <button disabled={pagination.page <= 1} on:click={() => onPageChange(pagination.page - 1)}>‹</button>
    {#each pages as p}
      {#if Math.abs(p - pagination.page) <= 2 || p === 1 || p === pagination.total_pages}
        <button class:active={p === pagination.page} on:click={() => onPageChange(p)}>{p}</button>
      {:else if Math.abs(p - pagination.page) === 3}
        <span style="padding:0 4px;color:var(--gray-400)">…</span>
      {/if}
    {/each}
    <button disabled={pagination.page >= pagination.total_pages} on:click={() => onPageChange(pagination.page + 1)}>›</button>
    <span style="font-size:.8rem;color:var(--gray-500);margin-left:8px">
      Total: {pagination.total}
    </span>
  </div>
{/if}
