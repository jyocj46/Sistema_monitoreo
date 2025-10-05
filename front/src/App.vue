<template>
  <div class="layout">

    <aside class="sidebar">
      <div class="brand">Grupo Detpon</div>
      <nav class="menu">
        <router-link to="/">Dashboard</router-link>
        <router-link to="/reports">Reportes</router-link>
        <a>Historial</a>
        <a>Rangos</a>
        <a>Aplicación de Productos</a>
        <a>Cultivos</a>
        <a>Productos</a>
        <a>Administración</a>
        </nav>
    </aside>

    <!-- Contenido principal -->
    <main class="main">
      <header class="topbar">
        <div class="left">
          <div class="crumbs">
            <span>Monitoreo en tiempo real</span>
          </div>
        </div>

      </header>
       <router-view />
    </main>
  </div>
</template>

<script setup>

</script>