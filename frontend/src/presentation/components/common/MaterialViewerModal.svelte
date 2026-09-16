<script>
  import { onMount, onDestroy } from 'svelte';
  import mammoth from 'mammoth';
  import { materialApi } from '../../../infrastructure/api/materialApi.js';
  import { getToken } from '../../../core/auth/authStore.js';

  /** @type {boolean} */
  export let visible = false;

  /** @type {{ id: number, meeting_name: string, file_name: string } | null} */
  export let material = null;

  let htmlContent  = '';
  let loading      = false;
  let errorMsg     = '';
  let contentEl;  // bind:this on the scrollable content div

  // Load whenever material changes and modal is visible
  $: if (visible && material) {
    loadDocument(material.id);
  }

  // Reset when closed
  $: if (!visible) {
    htmlContent = '';
    errorMsg    = '';
    loading     = false;
  }

  async function loadDocument(id) {
    loading     = true;
    htmlContent = '';
    errorMsg    = '';

    try {
      // Fetch via Authorization header (same as all other API calls)
      const token = getToken();
      const res = await fetch(`/api/materials/content/${id}`, {
        headers: {
          Authorization: token ? `Bearer ${token}` : '',
        },
      });

      if (!res.ok) {
        errorMsg = `Gagal memuat file (HTTP ${res.status}).`;
        loading = false;
        return;
      }

      const arrayBuffer = await res.arrayBuffer();

      const result = await mammoth.convertToHtml(
        { arrayBuffer },
        {
          // Keep images embedded as base64 data URIs
          convertImage: mammoth.images.imgElement(image => {
            return image.read('base64').then(imageData => ({
              src: `data:${image.contentType};base64,${imageData}`,
            }));
          }),
        }
      );

      htmlContent = result.value;
    } catch (e) {
      errorMsg = 'Terjadi kesalahan saat memuat dokumen.';
      console.error('[MaterialViewer]', e);
    } finally {
      loading = false;
    }
  }

  function close() {
    visible = false;
  }

  // Close on Escape key
  function onKeydown(e) {
    if (e.key === 'Escape') close();
  }

  // Scroll content to top whenever a new doc loads
  $: if (!loading && htmlContent && contentEl) {
    contentEl.scrollTop = 0;
  }
</script>

<svelte:window on:keydown={onKeydown} />

{#if visible}
  <!-- svelte-ignore a11y-click-events-have-key-events a11y-no-static-element-interactions -->
  <div class="viewer-backdrop" role="presentation" on:click|self={close}>
    <div class="viewer-modal" role="dialog" aria-modal="true" aria-labelledby="viewer-title">

      <!-- ── Header ── -->
      <div class="viewer-header">
        <div class="viewer-header-left">
          <div class="viewer-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
            </svg>
          </div>
          <div class="viewer-title-wrap">
            <h3 id="viewer-title">{material?.meeting_name ?? 'Materi'}</h3>
            <span class="viewer-filename">{material?.file_name ?? ''}</span>
          </div>
        </div>
        <button class="viewer-close" on:click={close} aria-label="Tutup">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <!-- ── Body ── -->
      <div class="viewer-body" bind:this={contentEl}>

        {#if loading}
          <div class="viewer-state">
            <div class="viewer-spinner"></div>
            <span>Memuat dokumen…</span>
          </div>

        {:else if errorMsg}
          <div class="viewer-state viewer-error">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p>{errorMsg}</p>
          </div>

        {:else if htmlContent}
          <!-- Rendered DOCX content -->
          <div class="docx-content">
            {@html htmlContent}
          </div>

        {:else}
          <div class="viewer-state">
            <span>Tidak ada konten.</span>
          </div>
        {/if}

      </div>

      <!-- ── Footer ── -->
      <div class="viewer-footer">
        <a
          href={materialApi.download(material?.id)}
          target="_blank"
          class="btn btn-secondary btn-sm"
        >
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Download
        </a>
        <button class="btn btn-secondary btn-sm" on:click={close}>
          Tutup
        </button>
      </div>

    </div>
  </div>
{/if}

<style>
  /* ── Backdrop ── */
  .viewer-backdrop {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(0, 0, 0, .72);
    backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    padding: 16px;
    animation: fadeIn .15s ease;
  }
  @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

  /* ── Modal shell ── */
  .viewer-modal {
    background: #111827;
    border: 1px solid rgba(110,231,183,.12);
    border-radius: 14px;
    box-shadow: 0 24px 80px rgba(0,0,0,.6);
    display: flex; flex-direction: column;
    width: 100%;
    max-width: 860px;
    height: 90vh;
    max-height: 900px;
    overflow: hidden;
    animation: slideUp .18s ease;
  }
  @keyframes slideUp {
    from { transform: translateY(16px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }

  /* ── Header ── */
  .viewer-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(110,231,183,.1);
    gap: 12px;
    flex-shrink: 0;
  }
  .viewer-header-left {
    display: flex; align-items: center; gap: 12px;
    min-width: 0;
  }
  .viewer-icon {
    width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
    background: rgba(110,231,183,.08);
    border: 1px solid rgba(110,231,183,.15);
    display: flex; align-items: center; justify-content: center;
    color: #6ee7b7;
  }
  .viewer-title-wrap {
    min-width: 0;
  }
  .viewer-title-wrap h3 {
    font-size: .95rem; font-weight: 700; color: #f9fafb;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin: 0 0 2px;
  }
  .viewer-filename {
    font-size: .75rem; color: #6b7280;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    display: block;
  }
  .viewer-close {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.08);
    color: #9ca3af; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, color .15s;
  }
  .viewer-close:hover {
    background: rgba(248,113,113,.1);
    border-color: rgba(248,113,113,.2);
    color: #f87171;
  }

  /* ── Body (scrollable) ── */
  .viewer-body {
    flex: 1;
    overflow-y: auto;
    padding: 32px 40px;
    background: #fff;
    scroll-behavior: smooth;
  }

  /* ── States ── */
  .viewer-state {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 12px;
    height: 100%;
    min-height: 240px;
    color: #6b7280; font-size: .875rem;
    background: #fff;
  }
  .viewer-error { color: #f87171; }
  .viewer-spinner {
    width: 36px; height: 36px;
    border: 3px solid rgba(110,231,183,.15);
    border-top-color: #6ee7b7; border-radius: 50%;
    animation: spin .7s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ── DOCX rendered content ── */
  .docx-content {
    font-family: 'Times New Roman', Times, Georgia, serif;
    font-size: 12pt;
    color: #111;
    line-height: 1.7;
    word-break: break-word;
  }

  /* Headings */
  .docx-content :global(h1) { font-size: 1.8em; font-weight: 700; margin: 1em 0 .4em; }
  .docx-content :global(h2) { font-size: 1.4em; font-weight: 700; margin: 1em 0 .4em; }
  .docx-content :global(h3) { font-size: 1.15em; font-weight: 700; margin: .9em 0 .3em; }
  .docx-content :global(h4),
  .docx-content :global(h5),
  .docx-content :global(h6) { font-size: 1em; font-weight: 700; margin: .8em 0 .3em; }

  /* Paragraphs */
  .docx-content :global(p) { margin: 0 0 .75em; }
  .docx-content :global(p:last-child) { margin-bottom: 0; }

  /* Lists */
  .docx-content :global(ul),
  .docx-content :global(ol) { margin: .5em 0 .75em 1.5em; padding: 0; }
  .docx-content :global(li) { margin-bottom: .3em; }

  /* Tables */
  .docx-content :global(table) {
    width: 100%; border-collapse: collapse;
    margin: 1em 0; font-size: .9em;
  }
  .docx-content :global(th),
  .docx-content :global(td) {
    border: 1px solid #ccc;
    padding: 6px 10px; text-align: left;
    vertical-align: top;
  }
  .docx-content :global(th) { background: #f3f4f6; font-weight: 700; }

  /* Images */
  .docx-content :global(img) {
    max-width: 100%; height: auto; display: block;
    margin: .75em auto; border-radius: 4px;
  }

  /* Inline styles */
  .docx-content :global(strong), .docx-content :global(b) { font-weight: 700; }
  .docx-content :global(em), .docx-content :global(i)     { font-style: italic; }
  .docx-content :global(u) { text-decoration: underline; }
  .docx-content :global(s) { text-decoration: line-through; }

  /* ── Footer ── */
  .viewer-footer {
    display: flex; align-items: center; justify-content: flex-end; gap: 8px;
    padding: 12px 20px;
    border-top: 1px solid rgba(110,231,183,.1);
    flex-shrink: 0;
  }
  .btn-sm {
    padding: 6px 14px !important;
    font-size: .8rem !important;
    text-decoration: none;
  }

  /* ── Scrollbar styling inside body ── */
  .viewer-body::-webkit-scrollbar { width: 8px; }
  .viewer-body::-webkit-scrollbar-track { background: #e5e7eb; }
  .viewer-body::-webkit-scrollbar-thumb { background: #9ca3af; border-radius: 4px; }
  .viewer-body::-webkit-scrollbar-thumb:hover { background: #6b7280; }

  /* ── Responsive ── */
  @media (max-width: 640px) {
    .viewer-modal { height: 95vh; max-height: none; border-radius: 10px; }
    .viewer-body  { padding: 20px 18px; }
    .viewer-filename { display: none; }
  }
</style>
