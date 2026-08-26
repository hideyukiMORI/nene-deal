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
  const appPort = projectEnv['NENE_DEAL_PORT'] ?? '8110'
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
      // Fixed, family-unique dev port (NeNe Deal). strictPort avoids silent
      // fallback into a sibling's range. See AGENTS.md "Local dev ports".
      port: 5187,
      strictPort: true,
      proxy: {
        '/api': { target, changeOrigin: true },
        '/health': { target, changeOrigin: true },
        // The disposable demo seat (#69) is a backend route that parks a token and
        // redirects into the SPA. Without it here, `/demo/standard` on the dev server
        // falls through to Vite's SPA fallback: the app boots with no seat and renders
        // the login screen — which looks like "the demo is broken" rather than "the dev
        // server never asked the backend". #225
        '/demo': { target, changeOrigin: true },
      },
    },
  }
})
