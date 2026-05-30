import path from 'node:path'
import { fileURLToPath } from 'node:url'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig, loadEnv } from 'vite'

const dirname = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig(({ mode }) => {
  // Keep the dev proxy in sync with the project-root .env app port without
  // duplicating the value (one level up from frontend/).
  const projectEnv = loadEnv(mode, path.resolve(dirname, '..'), '')
  const appPort = projectEnv['NENE_DEAL_PORT'] ?? '8080'
  const target = `http://localhost:${appPort}`

  return {
    plugins: [react(), tailwindcss()],
    resolve: {
      alias: {
        '@': path.resolve(dirname, './src'),
        '@tests': path.resolve(dirname, './tests'),
      },
    },
    server: {
      proxy: {
        '/api': { target, changeOrigin: true },
        '/health': { target, changeOrigin: true },
      },
    },
  }
})
