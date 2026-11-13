import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
// https://vite.dev/config/server-options
export default defineConfig({
  plugins: [react()],
  server: {
    host: true,
    port: 5173,
    watch: {usePolling: true}, //aumenta consumo CPU -> mayor rendimiento
    proxy: {
      '/api': 'http://web',
      '/sanctum': 'http://web',
    },
  },
})
