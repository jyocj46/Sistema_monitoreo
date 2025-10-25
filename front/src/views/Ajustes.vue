<!-- src/views/Ajuste.vue -->

<template>
  <div class="settings-page container-fluid py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10 col-xl-8">
        
        <div class="text-center mb-5">
          <h2 class="h1 fw-bold text-primary mb-3">Gestión de Correos de Alerta</h2>
          <p class="lead text-muted mb-0">
            Añade o elimina los correos que recibirán notificaciones de alerta
          </p>
          <div class="border-bottom mx-auto mt-4" style="max-width: 200px;"></div>
        </div>

        <div class="row g-4">
          
          <div class="col-12 col-lg-6">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-header bg-primary text-white py-3">
                <h3 class="h5 mb-0">
                  <i class="bi bi-person-plus me-2"></i>
                  Añadir Nuevo Destinatario
                </h3>
              </div>
              <div class="settings-card-body p-4">
                <form @submit.prevent="addEmail" class="needs-validation" novalidate>
                  <div class="mb-3">
                    <label for="nombre" class="form-label fw-semibold">
                      Nombre <span class="text-danger">*</span>
                    </label>
                    <input 
                      type="text" 
                      id="nombre" 
                      class="form-control form-control-lg"
                      v-model="newEmail.nombre" 
                      required
                      placeholder="Ingresa el nombre del destinatario "
                    >
                    <div class="invalid-feedback">
                      Por favor ingresa un nombre válido
                    </div>
                  </div>
                  
                  <div class="mb-4">
                    <label for="email" class="form-label fw-semibold">
                      Correo Electrónico <span class="text-danger">*</span>
                    </label>
                    <input 
                      type="email" 
                      id="email" 
                      class="form-control form-control-lg"
                      v-model="newEmail.email" 
                      required
                      placeholder="ejemplo@correo.com"
                    >
                    <div class="invalid-feedback">
                      Por favor ingresa un email válido
                    </div>
                  </div>
                  
                  <button 
                    type="submit" 
                    class="btn btn-primary btn-lg w-100 py-2"
                    :disabled="loading"
                  >
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                    {{ loading ? 'Guardando...' : 'Añadir Correo' }}
                  </button>
                </form>
              </div>
            </div>
          </div>

          <!-- Lista - Ocupa toda la fila en móvil, mitad en desktop -->
          <div class="col-12 col-lg-6">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-header py-3 custom-green-bg text-white">
                <h3 class="h5 mb-0">
                  <i class="bi bi-list-ul me-2"></i>
                  Correos Actuales
                  <span class="badge bg-light text-dark ms-2">{{ destinatarios.length }}</span>
                </h3>
              </div>
              <div class="settings-card-body p-0">
                <div v-if="destinatarios.length > 0" class="list-group list-group-flush">
                  <div 
                    v-for="dest in destinatarios" 
                    :key="dest.id"
                    class="list-group-item d-flex justify-content-between align-items-center py-3 px-4"
                  >
                    <div class="d-flex align-items-center">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" 
                           style="width: 40px; height: 40px;">
                        <i class="bi bi-person text-muted"></i>
                      </div>
                      <div>
                        <strong class="d-block text-dark">{{ dest.nombre }}</strong>
                        <small class="text-muted">{{ dest.email }}</small>
                      </div>
                    </div>
                    <button 
                      @click="deleteEmail(dest.id)" 
                      class="btn btn-outline-danger btn-sm"
                      :disabled="loading"
                      title="Eliminar correo"
                    >
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
      <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
    </svg>
                      Eliminar
                    </button>
                  </div>
                </div>
                
                <div v-else class="text-center py-5">
                  <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                  <p class="text-muted mb-0">No hay correos registrados</p>
                  <small class="text-muted">Agrega el primer correo usando el formulario</small>
                </div>
              </div>
            </div>
          </div>
        </div>
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