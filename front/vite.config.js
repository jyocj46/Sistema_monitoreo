// front/vite.config.js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  base: '/frontend/',
  
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate', 
      includeAssets: [
        'favicon.ico',
        'apple-touch-icon.png'
      ],
      manifest: {
        name: 'Sistema de Monitoreo',
        short_name: 'Monitoreo',
        description: 'Monitoreo de cuartos fríos',
        theme_color: '#ffffff',
        background_color: '#ffffff',
        display: 'standalone',
        scope: '/frontend/',
        start_url: '/frontend/',
          ios: {
      'apple-mobile-web-app-capable': 'yes',
      'apple-mobile-web-app-status-bar-style': 'default',
        },


        icons: [
          {
            src: 'pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable' 
          }
        ]
      },

      workbox: {
        importScripts: ['OneSignalSDKWorker.js']
      }

    })
  ],

  server: {
    proxy: {
      '/api': {
        target: 'https://drover.detpon.com',
        changeOrigin: true,
        secure: true
      }
    }
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
          mqtt: 'mqtt'
        }
      }
    }
  }
})
