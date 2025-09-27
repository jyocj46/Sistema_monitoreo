import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  base: '/frontend/',
  server: {
    proxy: {
      '/api': {
        target: 'https://drover.detpon.com',
        changeOrigin: true,
        secure: true,
      },
    },
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src')
    }
  },
  build: {
    rollupOptions: {
      external: ['mqtt'],
      output: {
        globals: {
          mqtt: 'mqtt' // Define mqtt como variable global
        }
      }
    }
  }
})