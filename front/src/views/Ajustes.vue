     <!-- src/views/Ajustes.vue -->
<template>
  <div class="settings-page container-fluid py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10 col-xl-8">
        <!-- Header Principal -->
        <div class="text-center mb-5">
          <h1 class="h1 fw-bold text-primary mb-3">Configuración del Sistema</h1>
          <p class="lead text-muted mb-0">
            Gestión de configuraciones generales
          </p>
          <div class="border-bottom mx-auto mt-4" style="max-width: 200px;"></div>
        </div>

        <!-- Acordeón de Bootstrap - TODOS CERRADOS -->
        <div class="accordion" id="settingsAccordion">
          
          <!-- Sección 1: Correos de Alerta - CERRADO -->
          <div class="accordion-item border-0 shadow-sm mb-3">
            <h2 class="accordion-header" id="headingCorreos">
              <button 
                class="accordion-button custom-accordion-header collapsed py-3" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#collapseCorreos" 
                aria-expanded="false" 
                aria-controls="collapseCorreos"
              >
                <i class="bi bi-envelope-check me-3 fs-5"></i>
                <span class="fw-bold">Gestión de Contactos (Email y WhatsApp)</span>
                <small class="text-muted ms-2">({{ contactos.length }} configurados)</small>
              </button>
            </h2>
            <div 
              id="collapseCorreos" 
              class="accordion-collapse collapse" 
              aria-labelledby="headingCorreos" 
              data-bs-parent="#settingsAccordion"
            >
              <div class="accordion-body p-0">
                <!-- Tu contenido de correos aquí -->
                <div class="row g-4 p-4">
                  <!-- Formulario -->
                  <div class="col-12 col-lg-6">
                    <div class="card border-0 h-100">
                      <div class="card-header bg-primary text-white py-3">
                        <h3 class="h5 mb-0">
                          <i class="bi bi-person-plus me-2"></i>
                          Añadir Nuevo Destinatario
                        </h3>
                      </div>
                      <div class="settings-card-body p-4">
                        <form @submit.prevent="addContacto" class="needs-validation" novalidate>
                          <div class="mb-3">
                            <label for="nombre" class="form-label fw-semibold">
                              Nombre <span class="text-danger">*</span>
                            </label>
                            <input 
                              type="text" 
                              id="nombre" 
                              class="form-control form-control-lg"
                              v-model="newContacto.nombre" 
                              required
                              placeholder="Ingresa el nombre"
                            >
                          </div>
                          
                          <div class="mb-3">
                            <label for="tipo" class="form-label fw-semibold">
                              Tipo <span class="text-danger">*</span>
                            </label>
                            <select id="tipo" class="form-select form-select-lg" v-model="newContacto.tipo">
                              <option value="EMAIL">Email</option>
                              <option value="WHATSAPP">WhatsApp</option>
                            </select>
                          </div>

                          <div class="mb-4">
                            <label for="valor" class="form-label fw-semibold">
                              Contacto (Email o Número) <span class="text-danger">*</span>
                            </label>
                              <input 
                                type="text" 
                                id="valor" 
                                class="form-control form-control-lg"
                                v-model="newContacto.valor" 
                                required
                                :placeholder="newContacto.tipo === 'WHATSAPP' ? '+50212345678' : 'ejemplo@correo.com'"
                              >
                          </div>
                          
                          <button 
                            type="submit" 
                            class="btn btn-primary btn-lg w-100 py-2"
                            :disabled="loading"
                          >
                            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                            {{ loading ? 'Guardando...' : 'Añadir Contacto' }}
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>

                  <!-- Lista -->
                  <div class="col-12 col-lg-6">
                    <div class="card border-0 h-100">
                      <div class="card-header py-3 custom-green-bg text-white">
                        <h3 class="h5 mb-0">
                          <i class="bi bi-list-ul me-2"></i>
                          Contactos Actuales
                          <span class="badge bg-light text-dark ms-2">{{ contactos.length }}</span>
                        </h3>
                      </div>
                      <div class="settings-card-body p-0">
                        <div v-if="contactos.length > 0" class="list-group list-group-flush">
                          <div 
                            v-for="dest in contactos" 
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
                                  <small class="text-muted">
                                    <i :class="dest.tipo === 'EMAIL' ? 'bi bi-envelope' : 'bi bi-whatsapp'"></i>
                                    {{ dest.valor }}
                                  </small>
                              </div>
                            </div>
                            <button 
                              @click="deleteContacto(dest.id)" 
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
                          <p class="text-muted mb-0">No hay contactos registrados</p>
                          <small class="text-muted">Agrega el primer contacto usando el formulario</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Sección 2: Configuración del Sistema - CERRADO -->
          <div class="accordion-item border-0 shadow-sm mb-3">
            <h2 class="accordion-header" id="headingSistema">
              <button 
                class="accordion-button custom-accordion-header collapsed py-3" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#collapseSistema" 
                aria-expanded="false" 
                aria-controls="collapseSistema"
              >
                <i class="bi bi-gear me-3 fs-5"></i>
                <span class="fw-bold">Configuración del Sistema</span>
              </button>
            </h2>
            <div 
              id="collapseSistema" 
              class="accordion-collapse collapse" 
              aria-labelledby="headingSistema" 
              data-bs-parent="#settingsAccordion"
            >
              <div class="accordion-body">
                <p class="text-muted">Aquí puedes agregar más configuraciones del sistema...</p>
              </div>
            </div>
          </div>

          <!-- Sección 3: Configuración de Notificaciones - CERRADO -->
          <div class="accordion-item border-0 shadow-sm mb-3">
            <h2 class="accordion-header" id="headingNotificaciones">
              <button 
                class="accordion-button custom-accordion-header collapsed py-3" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#collapseNotificaciones" 
                aria-expanded="false" 
                aria-controls="collapseNotificaciones"
              >
                <i class="bi bi-bell me-3 fs-5"></i>
                <span class="fw-bold">Notificaciones Push (Nativas)</span>
                <span v-if="isPushEnabled" class="badge bg-success-subtle text-success-emphasis ms-3">Activadas</span>
              </button>
            </h2>
            <div 
              id="collapseNotificaciones" 
              class="accordion-collapse collapse" 
              aria-labelledby="headingNotificaciones" 
              data-bs-parent="#settingsAccordion"
            >
              <div class="accordion-body p-4">
                <div class="card border-0">
                  <div class="settings-card-body p-3 text-center">
                    <i class="bi bi-phone-vibrate display-3 text-primary"></i>
                    <h5 class="fw-bold mt-3">Alertas Nativas en tu Dispositivo</h5>
                    <p class="text-muted">
                      Recibe alertas instantáneas en esta computadora o teléfono
                      incluso si la aplicación está cerrada.
                    </p>

                    <button 
                      @click="togglePush" 
                      class="btn btn-lg w-100 py-2"
                      :class="isPushEnabled ? 'btn-outline-danger' : 'btn-primary'"
                      :disabled="loadingPush"
                    >
                    <span v-if="isPushEnabled">Desactivar Notificaciones</span>
                    <span v-else>Activar en este Dispositivo</span>
                    </button>

                    <small class="text-muted d-block mt-2">
                      Recibirás notificaciones incluso si la app está cerrada.
                    </small>
                  </div>
                </div>
              </div>
              </div>
          </div>
           <!--Fin Sección 3-->
        </div>
      </div>
    </div>
  </div>
</template> 

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import '../assets/settings.css'
import { Collapse } from 'bootstrap'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api'

const contactos   = ref([])
const newContacto = ref({ nombre: '', tipo: 'EMAIL', valor: '' })
const loading     = ref(false)

const isPushEnabled = ref(false)
const loadingPush   = ref(false)

function getOneSignal() {
  return (window && window.OneSignal) ? window.OneSignal : null
}

async function togglePush() {
  const OneSignal = getOneSignal()
  if (!OneSignal) return

  loadingPush.value = true
  try {
    const isEnabled = (OneSignal.Notifications?.isPushEnabled)
      ? await OneSignal.Notifications.isPushEnabled()
      : await OneSignal.isPushNotificationsEnabled?.()

    if (isEnabled) {
      if (OneSignal.User?.Push?.disable) {
        await OneSignal.User.Push.disable()
      } else {
        await OneSignal.setSubscription(false)
      }
      isPushEnabled.value = false
    } else {
      if (OneSignal.Slidedown?.promptPush) {
        await OneSignal.Slidedown.promptPush()
      } else {
        await OneSignal.showSlidedownPrompt?.()
      }
      const now = (OneSignal.Notifications?.isPushEnabled)
        ? await OneSignal.Notifications.isPushEnabled()
        : await OneSignal.isPushNotificationsEnabled?.()
      isPushEnabled.value = !!now
    }
  } finally {
    loadingPush.value = false
  }
}


onMounted(async () => {
  await fetchContactos()
  await nextTick()
  initializeBootstrapComponents();

  const OneSignal = getOneSignal()
  if (!OneSignal) return

  await OneSignal.push(async () => {
    try {
      const enabled = (OneSignal.Notifications?.isPushEnabled)
        ? await OneSignal.Notifications.isPushEnabled()
        : await OneSignal.isPushNotificationsEnabled?.()
      isPushEnabled.value = !!enabled
      
      const playerId = (OneSignal.User?.getId)
        ? await OneSignal.User.getId()
        : await OneSignal.getUserId?.() 

      if (playerId) {
        await fetch(`${API_BASE}/subscribe_push`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ playerId })
        })
      }
    } catch (e) {
      console.warn('No se pudo consultar estado OneSignal:', e)
    }
  })
})

function initializeBootstrapComponents() {
  const collapseElementList = document.querySelectorAll('.collapse');
  [...collapseElementList].map(collapseEl => {
    if (!collapseEl._collapse) {
      return new Collapse(collapseEl, { 
        toggle: false
      });
    }
    return new Collapse(collapseEl, { toggle: false });
  });
}

async function fetchContactos() {
  loading.value = true
  try {
    const res = await fetch(`${API_BASE}/destinatarios`)
    const data = await res.json()
    if (data.success) contactos.value = data.data
  } catch (e) {
    console.error('Error al cargar destinatarios:', e)
  } finally {
    loading.value = false
  }
}

async function addContacto() {
  loading.value = true
  try {
    if (newContacto.value.tipo === 'WHATSAPP' && !newContacto.value.valor.startsWith('+')) {
      alert('El número de WhatsApp debe incluir el código de país (ej: +50212345678)')
      loading.value = false
      return
    }

    await fetch(`${API_BASE}/destinatarios`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(newContacto.value)
    })
    newContacto.value = { nombre: '', tipo: 'EMAIL', valor: '' }
    await fetchContactos()
  } catch (e) {
    console.error('Error al añadir contacto:', e)
  } finally {
    loading.value = false
  }
}

async function deleteContacto(id) {
  if (!confirm('¿Estás seguro de que quieres eliminar este correo?')) return
  loading.value = true
  try {
    await fetch(`${API_BASE}/destinatarios/${id}`, { method: 'DELETE' })
    await fetchContactos()
  } catch (e) {
    console.error('Error al eliminar email:', e)
  } finally {
    loading.value = false
  }
}

// Si necesitas usarlos fuera del template, expón:
defineExpose({
  togglePush,
  isPushEnabled,
  loadingPush
})
</script>