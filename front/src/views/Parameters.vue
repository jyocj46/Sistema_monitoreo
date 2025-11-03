<!-- src/views/Parameters.vue -->
<template>
  <div class="container-fluid parameters-container">

    <div class="unit-toggle-container">
      <div class="unit-toggle">
        <button :class="{ active: displayUnit === 'C' }" @click="displayUnit = 'C'">°C</button>
        <button :class="{ active: displayUnit === 'F' }" @click="displayUnit = 'F'">°F</button>
      </div>
    </div>

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

            <!-- Temperatura -->
            <div class="mb-2 fw-bold">Temperatura (°{{ displayUnit }})</div>

            <div class="row mb-3">
              <div class="col-12 text-muted small mb-1">Advertencia</div>
              <div class="col-6">
                <label :for="`temp_warn_min_${param.cuarto_id}`" class="parameters-form-label">Mín</label>
                <input
                  type="number"
                  class="form-control"
                  :id="`temp_warn_min_${param.cuarto_id}`"
                  :disabled="!param.isEditing"
                  :value="displayTemp(param.temp_warn_min_c)"
                  @input="updateTempValue(param, 'temp_warn_min_c', $event.target.value)"
                >
              </div>
              <div class="col-6">
                <label :for="`temp_warn_max_${param.cuarto_id}`" class="parameters-form-label">Máx</label>
                <input
                  type="number"
                  class="form-control"
                  :id="`temp_warn_max_${param.cuarto_id}`"
                  :disabled="!param.isEditing"
                  :value="displayTemp(param.temp_warn_max_c)"
                  @input="updateTempValue(param, 'temp_warn_max_c', $event.target.value)"
                >
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-12 text-muted small mb-1">Crítico</div>
              <div class="col-6">
                <label :for="`temp_crit_min_${param.cuarto_id}`" class="parameters-form-label">Mín</label>
                <input
                  type="number"
                  class="form-control"
                  :id="`temp_crit_min_${param.cuarto_id}`"
                  :disabled="!param.isEditing"
                  :value="displayTemp(param.temp_crit_min_c)"
                  @input="updateTempValue(param, 'temp_crit_min_c', $event.target.value)"
                >
              </div>
              <div class="col-6">
                <label :for="`temp_crit_max_${param.cuarto_id}`" class="parameters-form-label">Máx</label>
                <input
                  type="number"
                  class="form-control"
                  :id="`temp_crit_max_${param.cuarto_id}`"
                  :disabled="!param.isEditing"
                  :value="displayTemp(param.temp_crit_max_c)"
                  @input="updateTempValue(param, 'temp_crit_max_c', $event.target.value)"
                >
              </div>
            </div>

            <!-- Humedad -->
            <div class="mb-2 fw-bold">Humedad (%)</div>

            <div class="row mb-3">
              <div class="col-12 text-muted small mb-1">Advertencia</div>
              <div class="col-6">
                <label :for="`hum_warn_min_${param.cuarto_id}`" class="parameters-form-label">Mín</label>
                <input
                  type="number"
                  class="form-control"
                  :id="`hum_warn_min_${param.cuarto_id}`"
                  :disabled="!param.isEditing"
                  v-model.number="param.hum_warn_min_pct"
                >
              </div>
              <div class="col-6">
                <label :for="`hum_warn_max_${param.cuarto_id}`" class="parameters-form-label">Máx</label>
                <input
                  type="number"
                  class="form-control"
                  :id="`hum_warn_max_${param.cuarto_id}`"
                  :disabled="!param.isEditing"
                  v-model.number="param.hum_warn_max_pct"
                >
              </div>
            </div>

            <div class="row">
              <div class="col-12 text-muted small mb-1">Crítico</div>
              <div class="col-6">
                <label :for="`hum_crit_min_${param.cuarto_id}`" class="parameters-form-label">Mín</label>
                <input
                  type="number"
                  class="form-control"
                  :id="`hum_crit_min_${param.cuarto_id}`"
                  :disabled="!param.isEditing"
                  v-model.number="param.hum_crit_min_pct"
                >
              </div>
              <div class="col-6">
                <label :for="`hum_crit_max_${param.cuarto_id}`" class="parameters-form-label">Máx</label>
                <input
                  type="number"
                  class="form-control"
                  :id="`hum_crit_max_${param.cuarto_id}`"
                  :disabled="!param.isEditing"
                  v-model.number="param.hum_crit_max_pct"
                >
              </div>
            </div>

          </div>

          <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
              <button
                v-if="!param.isEditing"
                @click="activarEdicion(param)"
                class="btn btn-warning"
              >
                Editar Parámetros
              </button>

              <div v-else class="d-flex gap-2">
                <button
                  @click="guardarParametro(param)"
                  :disabled="param.isSaving"
                  class="btn btn-success"
                >
                  {{ param.isSaving ? 'Guardando...' : 'Guardar Cambios' }}
                </button>
                <button
                  @click="cerrarEdicion(param)"
                  :disabled="param.isSaving"
                  class="btn btn-secondary"
                >
                  Cerrar
                </button>
              </div>

              <span v-if="param.saveStatus" :class="`save-status ${param.saveStatus.type}`">
                {{ param.saveStatus.message }}
              </span>
            </div>
          </div>

        </div>
      </div>
    </div>
    <!-- Fin row cards -->
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import '../assets/Parameters.css';

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api';
const parametros = ref([]);
const cargando = ref(true);
const error = ref(null);

const displayUnit = ref('F');

const toFahrenheit = (celsius) => (celsius * 9 / 5) + 32;
const toCelsius = (fahrenheit) => (fahrenheit - 32) * 5 / 9;

const displayTemp = (celsius) => {
  if (celsius === null || celsius === undefined) return '';
  if (displayUnit.value === 'F') {
    const fahrenheit = toFahrenheit(celsius);
    return (fahrenheit % 1 === 0) ? fahrenheit : parseFloat(fahrenheit.toFixed(1));
  } else {
    return parseFloat(Number(celsius).toFixed(2));
  }
};

const updateTempValue = (param, field, inputValue) => {
  const numericValue = parseFloat(inputValue);
  if (isNaN(numericValue)) {
    param[field] = null;
    return;
  }
  if (displayUnit.value === 'F') {
    const celsiusValue = toCelsius(numericValue);
    param[field] = parseFloat(celsiusValue.toFixed(2));
  } else {
    param[field] = numericValue;
  }
};

onMounted(async () => {
  try {
    cargando.value = true;
    error.value = null;

    const response = await fetch(`${API_BASE}/parametros`);
    const result = await response.json();
    if (result.success) {
      parametros.value = result.data.map(p => ({
        ...p,
        // Temperatura (Celsius ya del backend)
        temp_warn_min_c: p.temp_warn_min_c !== null ? parseFloat(p.temp_warn_min_c) : null,
        temp_warn_max_c: p.temp_warn_max_c !== null ? parseFloat(p.temp_warn_max_c) : null,
        temp_crit_min_c: p.temp_crit_min_c !== null ? parseFloat(p.temp_crit_min_c) : null,
        temp_crit_max_c: p.temp_crit_max_c !== null ? parseFloat(p.temp_crit_max_c) : null,
        // Humedad
        hum_warn_min_pct: p.hum_warn_min_pct !== null ? parseFloat(p.hum_warn_min_pct) : null,
        hum_warn_max_pct: p.hum_warn_max_pct !== null ? parseFloat(p.hum_warn_max_pct) : null,
        hum_crit_min_pct: p.hum_crit_min_pct !== null ? parseFloat(p.hum_crit_min_pct) : null,
        hum_crit_max_pct: p.hum_crit_max_pct !== null ? parseFloat(p.hum_crit_max_pct) : null,
        isEditing: false,
        isSaving: false,
        saveStatus: null
      }));
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
        // Temperatura (8 valores totales sumando humedad)
        temp_warn_min_c: param.temp_warn_min_c,
        temp_warn_max_c: param.temp_warn_max_c,
        temp_crit_min_c: param.temp_crit_min_c,
        temp_crit_max_c: param.temp_crit_max_c,
        hum_warn_min_pct: param.hum_warn_min_pct,
        hum_warn_max_pct: param.hum_warn_max_pct,
        hum_crit_min_pct: param.hum_crit_min_pct,
        hum_crit_max_pct: param.hum_crit_max_pct
      })
    });
    const result = await response.json();
    if (result.success) {
      param.saveStatus = { type: 'success', message: '¡Guardado!' };
      param.isEditing = false;
    } else {
      throw new Error(result.message || 'Error al guardar');
    }
  } catch (e) {
    param.saveStatus = { type: 'error', message: 'Error' };
  } finally {
    param.isSaving = false;
    setTimeout(() => { param.saveStatus = null; }, 3000);
  }
};

const activarEdicion = (param) => {
  param.isEditing = true;
  param.valoresOriginales = {
    temp_warn_min_c: param.temp_warn_min_c,
    temp_warn_max_c: param.temp_warn_max_c,
    temp_crit_min_c: param.temp_crit_min_c,
    temp_crit_max_c: param.temp_crit_max_c,
    hum_warn_min_pct: param.hum_warn_min_pct,
    hum_warn_max_pct: param.hum_warn_max_pct,
    hum_crit_min_pct: param.hum_crit_min_pct,
    hum_crit_max_pct: param.hum_crit_max_pct
  };
};

const cerrarEdicion = (param) => {
  param.isEditing = false;
};
</script>
