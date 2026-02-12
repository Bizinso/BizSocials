import { fileURLToPath, URL } from 'node:url'
import http from 'node:http'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// Force IPv4 agent to prevent ECONNREFUSED on macOS with Node 18+
const ipv4Agent = new http.Agent({ family: 4 })

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8080',
        changeOrigin: true,
        agent: ipv4Agent,
      },
      '/sanctum': {
        target: 'http://127.0.0.1:8080',
        changeOrigin: true,
        agent: ipv4Agent,
      },
      '/broadcasting': {
        target: 'http://127.0.0.1:8080',
        changeOrigin: true,
        agent: ipv4Agent,
      },
    },
  },
})
