<template>
  <div class="settings-page">
    <h2>Gestión de Correos de Alerta</h2>
    <p>Añade o elimina los correos que recibirán notificaciones de alerta.</p>

    <div class="settings-container">
      <form @submit.prevent="addEmail" class="form-card">
        <h3>Añadir Nuevo Destinatario</h3>
        <div class="form-group">
          <label for="nombre">Nombre:</label>
          <input type="text" id="nombre" v-model="newEmail.nombre" required>
        </div>
        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" id="email" v-model="newEmail.email" required>
        </div>
        <button type="submit" :disabled="loading">
          {{ loading ? 'Guardando...' : 'Añadir Correo' }}
        </button>
      </form>

      <div class="list-card">
        <h3>Correos Actuales</h3>
        <ul v-if="destinatarios.length > 0">
          <li v-for="dest in destinatarios" :key="dest.id">
            <span>
              <strong>{{ dest.nombre }}</strong><br>
              <small>{{ dest.email }}</small>
            </span>
            <button @click="deleteEmail(dest.id)" class="btn-danger" :disabled="loading">
              &times;
            </button>
          </li>
        </ul>
        <p v-else>No hay correos registrados.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import '../assets/settings.css'; 

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api';
const destinatarios = ref([]);
const newEmail = ref({ nombre: '', email: '' });
const loading = ref(false);

// Cargar los correos al montar la página
async function fetchDestinatarios() {
  loading.value = true;
  try {
    const res = await fetch(`${API_BASE}/destinatarios`);
    const data = await res.json();
    if (data.success) {
      destinatarios.value = data.data;
    }
  } catch (e) {
    console.error("Error al cargar destinatarios:", e);
  } finally {
    loading.value = false;
  }
}

// Añadir un nuevo correo
async function addEmail() {
  loading.value = true;
  try {
    await fetch(`${API_BASE}/destinatarios`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(newEmail.value)
    });
    newEmail.value = { nombre: '', email: '' }; // Limpiar formulario
    await fetchDestinatarios(); // Recargar lista
  } catch (e) {
    console.error("Error al añadir email:", e);
  } finally {
    loading.value = false;
  }
}

// Eliminar un correo
async function deleteEmail(id) {
  if (!confirm('¿Estás seguro de que quieres eliminar este correo?')) return;
  loading.value = true;
  try {
    await fetch(`${API_BASE}/destinatarios/${id}`, {
      method: 'DELETE'
    });
    await fetchDestinatarios(); // Recargar lista
  } catch (e) {
    console.error("Error al eliminar email:", e);
  } finally {
    loading.value = false;
  }
}

onMounted(fetchDestinatarios);
</script>