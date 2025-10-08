// src/views/Reports.vue

<script setup>
import { ref, onMounted } from 'vue';
import TemperatureChart from '../components/TemperatureChart.vue';
import ExportButtons from '../components/ExportButtons.vue';
import '../assets/reports.css'; 

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api';


const fechaInicio = ref('');
const fechaFin = ref('');
const filtroCuartoId = ref(''); // ID del cuarto seleccionado. Vacío para "Todos".
const sortOrder = ref('DESC'); // 'DESC' (recientes) o 'ASC' (antiguos).

const cuartos = ref([]); 
const resultados = ref([]);
const promediosData = ref(null);
const cargando = ref(false);
const error = ref(null);
const reporteGenerado = ref(false);

// --- Lógica Nueva ---

// Función para cargar los cuartos cuando el componente se monta
const cargarCuartos = async () => {
  try {
    const response = await fetch(`${API_BASE}/cuartos`);
    const data = await response.json();
    if (data.success) {
      cuartos.value = data.data;
    }
  } catch (e) {
    console.error("Error al cargar la lista de cuartos:", e);
  }
};

onMounted(() => {
  cargarCuartos();
});

// Función para cambiar el orden y volver a generar el reporte si ya hay resultados
const toggleSortOrder = () => {
  sortOrder.value = sortOrder.value === 'DESC' ? 'ASC' : 'DESC';
  if (resultados.value.length > 0) {
    generarReporte();
  }
};

const formatChartData = (promedios) => {
  if (!promedios || promedios.length === 0) return null;

  const labels = [...new Set(promedios.map(p => p.fecha))].sort();
  const datasets = [];
  const cuartosEnData = [...new Set(promedios.map(p => p.cuarto_id))];

  // Paleta de colores para las líneas de la gráfica
  const colors = ['#6ac17b', '#3b82f6', '#ef4444', '#f97316', '#8b5cf6'];

  cuartosEnData.forEach((cuartoId, index) => {
    const datosDelCuarto = promedios.filter(p => p.cuarto_id === cuartoId);
    datasets.push({
      label: datosDelCuarto[0].cuarto_nombre,
      data: labels.map(label => {
        const datoParaFecha = datosDelCuarto.find(p => p.fecha === label);
        return datoParaFecha ? datoParaFecha.temp_promedio : null;
      }),
      borderColor: colors[index % colors.length],
      backgroundColor: colors[index % colors.length],
      tension: 0.1,
    });
  });

  return { labels, datasets };
};

const generarReporte = async () => {
  if (!fechaInicio.value || !fechaFin.value) {
    error.value = 'Por favor, selecciona ambas fechas.';
    return;
  }

  cargando.value = true;
  error.value = null;
  resultados.value = [];
  promediosData.value = null;
  reporteGenerado.value = true;

  try {
    // --- Construimos las URLs para ambas peticiones ---
    let baseUrl = `?fecha_inicio=${fechaInicio.value}&fecha_fin=${fechaFin.value}`;
    if (filtroCuartoId.value) {
      baseUrl += `&cuarto_id=${filtroCuartoId.value}`;
    }

    const urlTabla = `${API_BASE}/lecturas${baseUrl}&sort=${sortOrder.value}`;
    const urlGrafica = `${API_BASE}/lecturas/promedios${baseUrl}`;

    // --- Hacemos las dos peticiones en paralelo para mayor eficiencia ---
    const [responseTabla, responseGrafica] = await Promise.all([
      fetch(urlTabla),
      fetch(urlGrafica)
    ]);

    if (!responseTabla.ok) throw new Error(`Error al cargar tabla: ${responseTabla.statusText}`);
    if (!responseGrafica.ok) throw new Error(`Error al cargar gráfica: ${responseGrafica.statusText}`);
    
    // --- Procesamos los resultados de la tabla ---
    const apiResponseTabla = await responseTabla.json();
    resultados.value = (apiResponseTabla.data || []).map(lectura => ({
      ...lectura,
      temperatura_c: parseFloat(lectura.temperatura_c),
      humedad_pct: parseFloat(lectura.humedad_pct),
    }));

    // --- Procesamos los resultados de la gráfica ---
    const apiResponseGrafica = await responseGrafica.json();
    if (apiResponseGrafica.success) {
      promediosData.value = formatChartData(apiResponseGrafica.data);
    }

  } catch (e) {
    error.value = `No se pudo generar el reporte: ${e.message}`;
  } finally {
    cargando.value = false;
  }
};
</script>


<template>
  <div class="reports-container">
    <div class="header-section">
      <h2>Generador de Reportes</h2>
      <p>Selecciona un rango de fechas para generar el reporte de monitoreo</p>
    </div>

    <div class="filters-section">
      <div class="filters">
        <div class="form-group">
          <label for="fechaInicio">Fecha de Inicio</label>
          <input type="date" id="fechaInicio" v-model="fechaInicio" />
        </div>
        <div class="form-group">
          <label for="fechaFin">Fecha de Fin</label>
          <input type="date" id="fechaFin" v-model="fechaFin" />
        </div>

      <div class="form-group">
        <label for="filtroCuarto">Filtrar por Cuarto:</label>
        <select id="filtroCuarto" v-model="filtroCuartoId">
          <option value="">-- Todos --</option>
          <option v-for="cuarto in cuartos" :key="cuarto.id" :value="cuarto.id">
            {{ cuarto.nombre }} ({{ cuarto.codigo }})
          </option>
        </select>
      </div>

        <button @click="generarReporte" :disabled="cargando" class="generate-btn">
          {{ cargando ? 'Generando...' : 'Generar Reporte' }}
        </button>
      </div>
    </div>

    <div v-if="error" class="error-message">{{ error }}</div>

    <div v-if="reporteGenerado && !cargando">
    <TemperatureChart v-if="promediosData" :chart-data="promediosData" />
      <div v-if="resultados.length > 0">
        <div class="results-table">
          </div>
      </div>
    </div>

    <div v-if="resultados.length > 0" class="results-section">
      <div class="results-header">
        <h3>Resultados del Reporte</h3>
        <ExportButtons :data="resultados" filename="reporte_monitoreo" />
      </div>
      
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th @click="toggleSortOrder" class="sortable">
                Fecha y Hora
                <span v-if="sortOrder === 'DESC'">↓</span>
                <span v-else>↑</span>
              </th>
              <th>Sensor/Cuarto</th>
              <th>Temperatura (°C)</th>
              <th>Humedad (%)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="lectura in resultados" :key="lectura.id">
              <td>{{ new Date(lectura.tomado_en_utc).toLocaleString() }}</td>
              <td>{{ lectura.cuarto_nombre || `Sensor ${lectura.sensor_id}` }}</td>
              <td>{{ lectura.temperatura_c.toFixed(2) }}</td>
              <td>{{ lectura.humedad_pct.toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else-if="!cargando && !error && reporteGenerado" class="no-results">
      <div class="no-results-content">
        <span class="no-results-icon">📊</span>
        <p>No se encontraron registros para el rango de fechas seleccionado.</p>
      </div>
    </div>
  </div>
</template>
