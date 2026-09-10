import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';

export default defineConfig({
  plugins: [svelte()],

  // Vite otomatis load .env / .env.production dari direktori ini
  envDir: '.',

  build: {
    // Output folder hasil build — ini yang di-upload ke hosting
    outDir: 'dist',
    // Bersihkan dist/ sebelum build
    emptyOutDir: true,
  },

  server: {
    port: 5173,
    // Dev proxy: semua /api/* diteruskan ke backend PHP lokal
    // Di production tidak dipakai — VITE_API_BASE yang handle
    proxy: {
      
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
});
