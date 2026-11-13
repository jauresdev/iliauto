import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

const phpBackend = process.env.PHP_BACKEND_URL || process.env.VITE_PHP_BACKEND_URL || 'http://127.0.0.1:8000';

export default defineConfig({
  plugins: [react()],
  base: './',
  server: {
    port: 4173,
    strictPort: true,
    proxy: {
      '/api': {
        target: phpBackend,
        changeOrigin: true,
        secure: false,
      },
      '/uploads': {
        target: phpBackend,
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
  },
});
