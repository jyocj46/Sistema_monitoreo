// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '../views/dashboard.vue'
import Reports from '../views/reports.vue' // Lo crearemos en el siguiente paso

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'dashboard',
      component: Dashboard
    },
    {
      path: '/reports',
      name: 'reports',
      component: Reports
    }
    // Aquí puedes agregar más rutas en el futuro
  ],
  linkActiveClass: 'active' // Esto hará que tu clase .active funcione con <router-link>
})

export default router