// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '../views/dashboard.vue'
import Reports from '../views/reports.vue' 
import Parameters from '../views/Parameters.vue'; 

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
    },
    {
    path: '/parametros',
    name: 'Parameters',
    component: Parameters
    }

  ],
  linkActiveClass: 'active'
})

export default router