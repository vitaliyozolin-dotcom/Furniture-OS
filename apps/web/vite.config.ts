import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  base: process.env.VITE_BASE_PATH ?? '/',
  server: { proxy: { '/health': { target: process.env.API_PROXY_TARGET ?? 'http://127.0.0.1:8000', changeOrigin: true }, '/api': { target: process.env.API_PROXY_TARGET ?? 'http://127.0.0.1:8000', changeOrigin: true } } },
  test: {
    environment: 'jsdom',
    setupFiles: './src/test/setup.ts',
    exclude: ['e2e/**', 'node_modules/**'],
    coverage: { provider: 'v8', reporter: ['text', 'json-summary', 'html'], include: ['src/**/*.{ts,tsx}'], exclude: ['src/main.tsx', 'src/shared/api/generated/**', 'src/test/**'], thresholds: { lines: 99.13, branches: 93.22, functions: 100, statements: 98.6 } },
  },
});
