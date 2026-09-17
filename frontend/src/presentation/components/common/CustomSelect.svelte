<script>
  import { createEventDispatcher, onMount, onDestroy } from 'svelte';
  import { fade, fly } from 'svelte/transition';

  /** @type {string} */
  export let value = '';

  /** @type {string} */
  export let id = '';

  /** @type {string} */
  export let placeholder = 'Pilih...';

  /** @type {boolean} */
  export let disabled = false;

  /** @type {Array<{ value: string, label: string, disabled?: boolean }>} */
  export let options = [];

  const dispatch = createEventDispatcher();

  let isOpen = false;
  let containerEl;
  let dropdownEl;
  let dropdownPosition = { top: 0, left: 0, width: 0 };

  $: selectedOption = options.find(o => String(o.value) === String(value));

  function toggle() {
    if (disabled) return;
    isOpen = !isOpen;
    if (isOpen) {
      calculateDropdownPosition();
    }
  }

  function selectOption(opt) {
    if (opt.disabled) return;
    value = opt.value;
    dispatch('change', { value: opt.value, label: opt.label });
    isOpen = false;
  }

  function calculateDropdownPosition() {
    if (!containerEl) return;
    const rect = containerEl.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const spaceBelow = viewportHeight - rect.bottom;
    const spaceAbove = rect.top;
    const dropdownHeight = 280; // max-height dropdown

    // Default: position below
    let top = rect.bottom + 4;
    let left = rect.left;
    let width = rect.width;

    // If not enough space below but enough above, flip to top
    if (spaceBelow < dropdownHeight && spaceAbove > spaceBelow) {
      top = rect.top - 4; // will be transformed to bottom
    }

    dropdownPosition = { top, left, width, flip: spaceBelow < dropdownHeight && spaceAbove > spaceBelow };
  }

  function handleClickOutside(e) {
    if (containerEl && !containerEl.contains(e.target)) {
      isOpen = false;
    }
  }

  function handleKeydown(e) {
    if (!isOpen) return;
    if (e.key === 'Escape') {
      isOpen = false;
    }
  }

  onMount(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
    window.addEventListener('resize', () => {
      if (isOpen) calculateDropdownPosition();
    });
    window.addEventListener('scroll', () => {
      if (isOpen) calculateDropdownPosition();
    }, true);
  });

  onDestroy(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
  });
</script>

<div
  class="custom-select"
  class:disabled
  class:open={isOpen}
  bind:this={containerEl}
>
  <button
    type="button"
    class="select-trigger"
    {id}
    {disabled}
    on:click={toggle}
    aria-haspopup="listbox"
    aria-expanded={isOpen}
  >
    <span class="select-value" class:placeholder={!selectedOption}>
      {selectedOption?.label ?? placeholder}
    </span>
    <span class="select-arrow">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    </span>
  </button>
</div>

{#if isOpen}
  <div
    class="select-dropdown"
    class:dropdown-flip={dropdownPosition.flip}
    style="top: {dropdownPosition.top}px; left: {dropdownPosition.left}px; width: {dropdownPosition.width}px;"
    transition:fly={{ y: dropdownPosition.flip ? -8 : 8, duration: 120 }}
    role="listbox"
    bind:this={dropdownEl}
  >
    <div class="dropdown-scroll">
      {#each options as opt (opt.value)}
        <button
          type="button"
          class="dropdown-option"
          class:selected={String(opt.value) === String(value)}
          class:disabled={opt.disabled}
          on:click={() => selectOption(opt)}
          disabled={opt.disabled}
          role="option"
          aria-selected={String(opt.value) === String(value)}
        >
          {opt.label}
        </button>
      {/each}
      {#if options.length === 0}
        <div class="dropdown-empty">Tidak ada opsi</div>
      {/if}
    </div>
  </div>
{/if}

<style>
  .custom-select {
    position: relative;
    width: 100%;
  }

  .select-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 9px 13px;
    border: 1px solid rgba(110,231,183,.15);
    border-radius: 10px;
    font-size: .875rem;
    color: #f9fafb;
    background: #1a2332;
    cursor: pointer;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
    font-family: inherit;
    text-align: left;
  }

  .select-trigger:hover:not(:disabled) {
    border-color: rgba(110,231,183,.25);
    background: #1e2d3f;
  }

  .select-trigger:focus {
    border-color: #6ee7b7;
    background: #1e2d3f;
    box-shadow: 0 0 0 3px rgba(110,231,183,.12);
  }

  .custom-select.disabled .select-trigger {
    opacity: .45;
    cursor: not-allowed;
  }

  .custom-select.open .select-trigger {
    border-color: #6ee7b7;
    box-shadow: 0 0 0 3px rgba(110,231,183,.12);
  }

  .select-value {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .select-value.placeholder {
    color: #4b5563;
  }

  .select-arrow {
    flex-shrink: 0;
    color: #6b7280;
    transition: transform .2s;
  }

  .custom-select.open .select-arrow {
    transform: rotate(180deg);
  }

  /* ── Dropdown (teleported to body via fixed positioning) ── */
  .select-dropdown {
    position: fixed;
    z-index: 1100;
    background: #111827;
    border: 1px solid rgba(110,231,183,.15);
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.03);
    overflow: hidden;
  }

  .dropdown-flip {
    transform: translateY(-100%);
    transform-origin: bottom;
  }

  .dropdown-scroll {
    max-height: 280px;
    overflow-y: auto;
    padding: 4px;
  }

  .dropdown-scroll::-webkit-scrollbar { width: 5px; }
  .dropdown-scroll::-webkit-scrollbar-track { background: transparent; }
  .dropdown-scroll::-webkit-scrollbar-thumb { background: rgba(110,231,183,.2); border-radius: 3px; }

  .dropdown-option {
    width: 100%;
    display: block;
    padding: 9px 12px;
    font-size: .875rem;
    color: #d1d5db;
    background: transparent;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-align: left;
    font-family: inherit;
    transition: background .12s, color .12s;
  }

  .dropdown-option:hover:not(.disabled) {
    background: rgba(110,231,183,.08);
    color: #6ee7b7;
  }

  .dropdown-option.selected {
    background: rgba(110,231,183,.12);
    color: #6ee7b7;
    font-weight: 600;
  }

  .dropdown-option.disabled {
    opacity: .4;
    cursor: not-allowed;
  }

  .dropdown-empty {
    padding: 20px 16px;
    text-align: center;
    color: #6b7280;
    font-size: .85rem;
  }
</style>
