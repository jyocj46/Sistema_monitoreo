// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'


import Dashboard from '../views/dashboard.vue'
import Reports from '../views/reports.vue'
import Parameters from '../views/Parameters.vue'
import Ajustes from '../views/Ajustes.vue'
import Login from '../views/Login.vue'

const routes = [
  {
    path: '/dashboard',
    alias: '/',    
    name: 'dashboard',
    component: Dashboard,
  },
  {
    path: '/reports',
    name: 'reports',
    component: Reports,
    meta: { requiresAuth: true } // <-- protegida
  },
  {
    path: '/parametros',
    name: 'Parameters',
    component: Parameters,
    meta: { requiresAuth: true } // <-- protegida
  },
  {
    path: '/ajustes',
    name: 'Ajustes',
    component: Ajustes,
    meta: { requiresAuth: true } // <-- protegida
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  }
]
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  linkActiveClass: 'active'
})
router.beforeEach((to, from, next) => {
  const requiresAuth = to.meta.requiresAuth === true

  const authToken = localStorage.getItem('auth_token')

  if (requiresAuth && !authToken) {
    next({ name: 'Login' })
    return
  }

  if (to.name === 'Login' && authToken) {
    next({ name: 'dashboard' })
    return
  }

  next()
})

export default router
