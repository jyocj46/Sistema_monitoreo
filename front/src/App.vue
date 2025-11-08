
 <!-- // src/App.vue -->
<template>
  <div class="layout">
    <aside class="sidebar" :class="{ open: isSidebarOpen }">
      <div class="brand">Grupo Detpon</div>
      <nav class="menu">
        <router-link to="/">Dashboard</router-link>
        <router-link to="/reports">Reportes</router-link>
        <router-link to="/parametros">Parámetros</router-link> 
        <a href="https://fichas.detpon.com/welcome/vista/a05c7eadedddc6a7ec0d3aaacd228201/#" target="_blank" rel="noopener noreferrer">
        Productos
        </a>
        <router-link to="/ajustes">Ajustes</router-link> 
        <router-link v-if="!isLoggedIn" to="/login">Login</router-link>     
        <a v-else @click="handleLogout" class="logout-button">
          Cerrar Sesión
        </a>
      </nav>
    </aside>


    <main class="main">
      <header class="topbar">
        <div class="left">
          <button class="hamburger" @click="isSidebarOpen = !isSidebarOpen" aria-label="Abrir menú">
            ☰
          </button>

          <div class="crumbs">
            <span>Sistema de monitoreo</span>
          </div>
        </div>

      </header>
      <router-view />
    </main>
    <div v-if="isSidebarOpen" class="backdrop" @click="isSidebarOpen = false"></div>
  </div>
</template>


<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const isSidebarOpen = ref(false)

const router = useRouter()
const route = useRoute()
const isLoggedIn = ref(false)

const checkAuth = () => {
  isLoggedIn.value = !!localStorage.getItem('auth_token')
}

onMounted(() => {
  checkAuth()
})

watch(() => route.path, (newPath) => {
  checkAuth()
})

function handleLogout() {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('user')
  
  isLoggedIn.value = false
 

  router.push({ name: 'Login' })
}

</script>

