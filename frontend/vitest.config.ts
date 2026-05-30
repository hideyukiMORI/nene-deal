import path from 'node:path'
import { fileURLToPath } from 'node:url'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vitest/config'

const dirname = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': path.resolve(dirname, './src'),
      '@tests': path.resolve(dirname, './tests'),
    },
  },
  test: {
    environment: 'jsdom',
    setupFiles: ['./tests/setup/vitest.setup.ts'],
    include: ['src/**/*.test.ts', 'src/**/*.test.tsx', 'tests/**/*.test.ts', 'tests/**/*.test.tsx'],
  },
})
