
 <!-- // src/App.vue -->
<template>
  <div class="layout">
    <aside class="sidebar" :class="{ open: isSidebarOpen }">
      <div class="brand">Grupo Detpon</div>
      <nav class="menu">
        <router-link to="/">Dashboard</router-link>
        <router-link to="/reports">Reportes</router-link>
        <router-link to="/parametros">Parámetros</router-link> 
        <a>Historial</a>
        <a>Productos</a>
        <router-link to="/ajustes">Ajustes</router-link> 
      </nav>
    </aside>


    <main class="main">
      <header class="topbar">
        <div class="left">
          <button class="hamburger" @click="isSidebarOpen = !isSidebarOpen" aria-label="Abrir menú">
            ☰
          </button>

          <div class="crumbs">
            <span>Monitoreo en tiempo real</span>
          </div>
        </div>

      </header>
      <router-view />
    </main>
    <div v-if="isSidebarOpen" class="backdrop" @click="isSidebarOpen = false"></div>
  </div>
</template>


<script setup>
import { ref } from 'vue'
const isSidebarOpen = ref(false)
</script>

