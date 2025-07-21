import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), vueJsx(), vueDevTools()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: true, // hace que sea accesible desde el host
    port: 5173, // puerto dev por defecto de Vite
    allowedHosts: ['real-state.ddev.site'],
    proxy: {
      '/api': {
        target: 'http://localhost:80', // Laravel dentro de DDEV
        changeOrigin: true,
        secure: false,
      },
    },
  },
})
