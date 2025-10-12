<template>
  <div class="parameters-container">
    <div class="header-section">
      <h2>Parámetros de Alertas por Cuarto</h2>
      <p>Configura los rangos de temperatura y humedad para cada cuarto.</p>
    </div>

    <div v-if="cargando" class="loading-message">Cargando parámetros...</div>
    <div v-if="error" class="error-message">{{ error }}</div>

    <div class="parameters-grid">
      <div v-for="param in parametros" :key="param.cuarto_id" class="param-card">
        <div class="card-header">
          <h3>{{ param.cuarto_nombre }}</h3>
          <span class="room-code">{{ param.cuarto_codigo }}</span>
        </div>

        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label :for="`temp_min_${param.cuarto_id}`">Temp. Mín (°C)</label>
              <input type="number" :id="`temp_min_${param.cuarto_id}`" v-model.number="param.temp_min_c">
            </div>
            <div class="form-group">
              <label :for="`temp_max_${param.cuarto_id}`">Temp. Máx (°C)</label>
              <input type="number" :id="`temp_max_${param.cuarto_id}`" v-model.number="param.temp_max_c">
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label :for="`hum_min_${param.cuarto_id}`">Hum. Mín (%)</label>
              <input type="number" :id="`hum_min_${param.cuarto_id}`" v-model.number="param.hum_min_pct">
            </div>
            <div class="form-group">
              <label :for="`hum_max_${param.cuarto_id}`">Hum. Máx (%)</label>
              <input type="number" :id="`hum_max_${param.cuarto_id}`" v-model.number="param.hum_max_pct">
            </div>
          </div>
        </div>

        <div class="card-footer">
          <button @click="guardarParametro(param)" :disabled="param.isSaving">
            {{ param.isSaving ? 'Guardando...' : 'Guardar Cambios' }}
          </button>
          <span v-if="param.saveStatus" :class="`save-status ${param.saveStatus.type}`">
            {{ param.saveStatus.message }}
          </span>
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

