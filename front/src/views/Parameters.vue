<!---->
<template>
  <div class="container-fluid parameters-container">
    <div class="header-section mb-4">
      <h2>Parámetros de Alertas por Cuarto</h2>
      <p class="text-muted">Configura los rangos de temperatura y humedad para cada cuarto.</p>
    </div>

    <div v-if="cargando" class="alert alert-info">Cargando parámetros...</div>
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="row">
      <div v-for="param in parametros" :key="param.cuarto_id" class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-4">
        <div class="card param-card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ param.cuarto_nombre }}</h5>
            <span class="badge bg-secondary">{{ param.cuarto_codigo }}</span>
          </div>

      <div class="card-body parameters-body">
        <div class="row mb-3">
          <div class="col-6">
            <label :for="`temp_min_${param.cuarto_id}`" class="form-label">Temp. Mín (°C)</label>
            <input type="number" class="form-control" :id="`temp_min_${param.cuarto_id}`" v-model.number="param.temp_min_c">
          </div>
          <div class="col-6">
            <label :for="`temp_max_${param.cuarto_id}`" class="form-label">Temp. Máx (°C)</label>
            <input type="number" class="form-control" :id="`temp_max_${param.cuarto_id}`" v-model.number="param.temp_max_c">
          </div>
        </div>
        
        <div class="row">
          <div class="col-6">
            <label :for="`hum_min_${param.cuarto_id}`" class="form-label">Hum. Mín (%)</label>
            <input type="number" class="form-control" :id="`hum_min_${param.cuarto_id}`" v-model.number="param.hum_min_pct">
          </div>
          <div class="col-6">
            <label :for="`hum_max_${param.cuarto_id}`" class="form-label">Hum. Máx (%)</label>
            <input type="number" class="form-control" :id="`hum_max_${param.cuarto_id}`" v-model.number="param.hum_max_pct">
          </div>
        </div>
      </div>

          <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
              <button 
                @click="guardarParametro(param)" 
                :disabled="param.isSaving"
                class="btn btn-primary"
              >
                {{ param.isSaving ? 'Guardando...' : 'Guardar Cambios' }}
              </button>
              <span v-if="param.saveStatus" :class="`save-status ${param.saveStatus.type}`">
                {{ param.saveStatus.message }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import '../assets/Parameters.css'; 

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api';
const parametros = ref([]);
const cargando = ref(true);
const error = ref(null);

onMounted(async () => {
  try {
    const response = await fetch(`${API_BASE}/parametros`);
    const result = await response.json();
    if (result.success) {
      // Añadimos propiedades reactivas para el estado de guardado
      parametros.value = result.data.map(p => ({ ...p, isSaving: false, saveStatus: null }));
    } else {
      throw new Error(result.message || 'Error en la API');
    }
  } catch (e) {
    error.value = `No se pudieron cargar los parámetros: ${e.message}`;
  } finally {
    cargando.value = false;
  }
});

const guardarParametro = async (param) => {
  param.isSaving = true;
  param.saveStatus = null;

  try {
    const response = await fetch(`${API_BASE}/parametros/${param.cuarto_id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        temp_min_c: param.temp_min_c,
        temp_max_c: param.temp_max_c,
        hum_min_pct: param.hum_min_pct,
        hum_max_pct: param.hum_max_pct,
      })
    });
    const result = await response.json();
    if (result.success) {
      param.saveStatus = { type: 'success', message: '¡Guardado!' };
    } else {
      throw new Error(result.message || 'Error al guardar');
    }
  } catch (e) {
    param.saveStatus = { type: 'error', message: 'Error' };
  } finally {
    param.isSaving = false;
    // Oculta el mensaje de estado después de 3 segundos
    setTimeout(() => { param.saveStatus = null; }, 3000);
  }
};
</script>

