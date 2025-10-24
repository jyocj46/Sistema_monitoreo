<!-- src/views/Reports.vue -->

<script setup>
    import { ref, onMounted, computed, watch } from 'vue';
    import TemperatureChart from '../components/TemperatureChart.vue';
    import ExportButtons from '../components/ExportButtons.vue';
    import '../assets/reports.css'; 

    const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api';


    const fechaInicio = ref('');
    const fechaFin = ref('');
    const filtroCuartoId = ref(''); 
    const sortOrder = ref('DESC'); 
    const cuartos = ref([]); 
    const resultados = ref([]);
    const promediosData = ref(null); // Datos originales de la gráfica en Celsius
    const cargando = ref(false);
    const error = ref(null);
    const reporteGenerado = ref(false);
    const displayUnit = ref('F'); 
    const currentPage = ref(1);
    const itemsPerPage = 20;
    const pageInput = ref(1);
    const horaInicio = ref('');
    const horaFin = ref('');

    const toFahrenheit = (celsius) => (celsius * 9 / 5) + 32;

    const chartDataConverted = computed(() => {
      // Si no hay datos o la unidad es Celsius, devuelve los datos originales
      if (!promediosData.value || displayUnit.value === 'C') {
        return promediosData.value;
      }
      
      // Si la unidad es Fahrenheit, crea una copia profunda y convierte los datos
      const convertedData = JSON.parse(JSON.stringify(promediosData.value));
      convertedData.datasets.forEach(dataset => {
        dataset.data = dataset.data.map(tempC => {
          if (tempC === null) return null;
          return toFahrenheit(tempC);
        });
      });
      
      return convertedData;
    });

    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
      }
    };

    const totalPages = computed(() => {
      return Math.ceil(resultados.value.length / itemsPerPage);
    });

    const goToPageFromInput = () => {
      goToPage(pageInput.value);
    };

    const paginatedResultados = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage;
      const end = start + itemsPerPage;
      return resultados.value.slice(start, end);
    });

    watch(currentPage, (newPage) => {
      pageInput.value = newPage;
    });

    const exportData = computed(() => {
      if (!resultados.value.length) return [];

      return resultados.value.map(l => ({
        'Fecha y Hora': new Date((l.ingresado_local ?? l.ingresado_en)).toLocaleString(),
        'Cuarto/Sensor': l.cuarto_nombre || `Sensor ${l.sensor_id}`,

        [`Temperatura (°${displayUnit.value})`]: displayTemp(l.temperatura_c),
        'Humedad (%)': l.humedad_pct.toFixed(2)
      }));
    });

    const displayTemp = (celsius) => {
      if (celsius === undefined || celsius === null) return '--';
      return (displayUnit.value === 'F' ? toFahrenheit(celsius) : celsius).toFixed(2);
    };

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

    
    const toggleSortOrder = () => {
      sortOrder.value = sortOrder.value === 'DESC' ? 'ASC' : 'DESC';
      generarReporte(); 
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
    currentPage.value = 1; 
    pageInput.value = 1;

    try {

      let baseUrl = `?fecha_inicio=${fechaInicio.value}&fecha_fin=${fechaFin.value}`;

      if (horaInicio.value) {
        baseUrl += `&hora_inicio=${horaInicio.value}`;
      }
      if (horaFin.value) {
        baseUrl += `&hora_fin=${horaFin.value}`;
      }

      if (filtroCuartoId.value) {
        baseUrl += `&cuarto_id=${filtroCuartoId.value}`;
      }

      const urlTabla = `${API_BASE}/lecturas${baseUrl}&sort=${sortOrder.value}`;
      const urlGrafica = `${API_BASE}/lecturas/promedios${baseUrl}`;


      const [responseTabla, responseGrafica] = await Promise.all([
        fetch(urlTabla),
        fetch(urlGrafica)
      ]);

      if (!responseTabla.ok) throw new Error(`Error al cargar tabla: ${responseTabla.statusText}`);
      if (!responseGrafica.ok) throw new Error(`Error al cargar gráfica: ${responseGrafica.statusText}`);
      

      const apiResponseTabla = await responseTabla.json();
      resultados.value = (apiResponseTabla.data || []).map(lectura => ({
        ...lectura,
        temperatura_c: parseFloat(lectura.temperatura_c),
        humedad_pct: parseFloat(lectura.humedad_pct),
      }));


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

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

const limpiarFiltros = () => {
  fechaInicio.value = '';
  fechaFin.value = '';
  horaInicio.value = '';
  horaFin.value = '';
  filtroCuartoId.value = '';

  sortOrder.value = 'DESC';
  displayUnit.value = 'F'; // pon 'C' si prefieres Celsius por defecto

  resultados.value = [];
  promediosData.value = null;
  error.value = null;
  reporteGenerado.value = false;

  currentPage.value = 1;
  pageInput.value = 1;
};

</script>


<template>
  <div class="reports-container container py-3">

    <div class="header-section">
      <h2 class="fw-semibold">Generador de Reportes</h2>
      <p class="text-muted">Selecciona un rango de fechas para generar el reporte de monitoreo</p>
    </div>

    <div class="filters-section">
      <div class="card filters-card">
        <div class="card-header">
          <h5 class="mb-0">Filtros del Reporte</h5>
        </div>
        <div class="filters-container">
          <div class="row filters-row">
            <div class="col-12 col-sm-6 col-md-4 form-group">
              <label for="fechaInicio" class="form-label">Fecha de Inicio</label>
              <input type="date" id="fechaInicio" v-model="fechaInicio" class="form-control" />
            </div>

            <div class="col-12 col-sm-6 col-md-4 form-group">
              <label for="fechaFin" class="form-label">Fecha de Fin</label>
              <input type="date" id="fechaFin" v-model="fechaFin" class="form-control" />
            </div>
       
            <div class="col-12 col-sm-6 col-md-4 form-group">
              <label for="horaInicio" class="form-label">Hora de Inicio</label>
              <input type="time" id="horaInicio" v-model="horaInicio" class="form-control" />
            </div>

            <div class="col-12 col-sm-6 col-md-4 form-group">
              <label for="horaFin" class="form-label">Hora de Fin</label>
              <input type="time" id="horaFin" v-model="horaFin" class="form-control" />
            </div>

            <div class="col-12 col-sm-6 col-md-4 form-group">
              <label for="filtroCuarto" class="form-label">Filtrar por Cuarto</label>
              <select id="filtroCuarto" v-model="filtroCuartoId" class="form-select">
                <option value="">-- Todos los cuartos --</option>
                <option v-for="cuarto in cuartos" :key="cuarto.id" :value="cuarto.id">
                  {{ cuarto.nombre }} ({{ cuarto.codigo }})
                </option>
              </select>
            </div>

            <div class="col-12">
              <div class="generate-btn-container d-flex gap-2 justify-content-end">
                <button @click="generarReporte" :disabled="cargando" class="generate-btn">
                  {{ cargando ? 'Generando...' : 'Generar Reporte' }}
                </button>
                <button @click="limpiarFiltros" :disabled="cargando" class="btn btn-outline-secondary">
                  Limpiar filtros
                </button>
              </div>
            </div>
            
          </div>
        </div>
      </div>
    </div>

    
    <div v-if="error" class="alert alert-danger text-center">{{ error }}</div>

    
    <div v-if="reporteGenerado && !cargando" class="chart-section">
      <div class="unit-toggle">
        <button :class="['btn btn-sm', displayUnit === 'C' ? 'btn-primary' : 'btn-outline-primary']"
                @click="displayUnit = 'C'">°C</button>
        <button :class="['btn btn-sm', displayUnit === 'F' ? 'btn-primary' : 'btn-outline-secondary']"
                @click="displayUnit = 'F'">°F</button>
      </div>

      <div class="chart-responsive">
        <TemperatureChart v-if="chartDataConverted"
                          :chart-data="chartDataConverted"
                          :display-unit="displayUnit" />
      </div>
    </div>

    
    <div v-if="resultados.length > 0" class="results-section">
      <div class="results-header d-flex flex-wrap justify-content-between align-items-center">
        <h3 class="h5 mb-0">Resultados del Reporte</h3>
        <div class="results-actions">
          <ExportButtons :data="exportData" filename="reporte_monitoreo" />
        </div>
      </div>

      <!-- Tabla -->
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-success">
            <tr>
              <th @click="toggleSortOrder" class="sortable">
                Fecha y Hora
                <span v-if="sortOrder === 'DESC'">↓</span>
                <span v-else>↑</span>
              </th>
              <th>Cuarto</th>
              <th>Temperatura (°{{ displayUnit }})</th>
              <th>Humedad (%)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="lectura in paginatedResultados" :key="lectura.id">
              <td>{{ new Date(lectura.ingresado_local ?? lectura.ingresado_en).toLocaleString() }}</td>
              <td>{{ lectura.cuarto_nombre || `Sensor ${lectura.sensor_id}` }}</td>
              <td class="fw-semibold">{{ displayTemp(lectura.temperatura_c) }}</td>
              <td>{{ lectura.humedad_pct.toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="totalPages > 1" class="pagination-controls">
        <button @click="goToPage(1)" :disabled="currentPage === 1" class="btn btn-outline-secondary btn-sm">
          &laquo;&laquo;
        </button>
        <button @click="prevPage" :disabled="currentPage === 1" class="btn btn-outline-secondary btn-sm">
          Anterior
        </button>

        <span class="page-indicator d-flex align-items-center gap-2">
          <span>Página</span>
          <input type="number" v-model.number="pageInput" @keyup.enter="goToPageFromInput"
                 class="form-control form-control-sm text-center" style="width:70px;" />
          <span>de {{ totalPages }}</span>
        </span>

        <button @click="nextPage" :disabled="currentPage === totalPages" class="btn btn-outline-secondary btn-sm">
          Siguiente
        </button>
        <button @click="goToPage(totalPages)" :disabled="currentPage === totalPages" class="btn btn-outline-secondary btn-sm">
          &raquo;&raquo;
        </button>
      </div>
    </div>

    <!-- Sin resultados -->
    <div v-else-if="!cargando && !error && reporteGenerado" class="no-results">
      <div class="no-results-content text-center">
        <span class="no-results-icon">📊</span>
        <p>No se encontraron registros para el rango de fechas seleccionado.</p>
      </div>
    </div>
  </div>
</template>





