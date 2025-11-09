<!-- src/views/Dashboard.vue -->
<template>

    <div class="global-controls">
      <div class="unit-toggle">
        <button :class="{ active: displayUnit === 'C' }" @click="displayUnit = 'C'">°C</button>
        <button :class="{ active: displayUnit === 'F' }" @click="displayUnit = 'F'">°F</button>
      </div>
    </div>

      <section class="chart-container">
        <div class="chart-header">
          <h3>Evolución de Temperatura (Tiempo Real)</h3>
        </div>
        
        <div class="chart-wrapper">
          <apexchart
            v-if="series.length > 0"
            type="area"
            height="350"
            :options="chartOptions"
            :series="chartSeries"  
            @legend-click="handleLegendClick"
          ></apexchart>
          
          <div v-else class="loading-chart">
            {{ cargandoGrafica ? 'Cargando datos históricos...' : 'No hay datos para mostrar.' }}
          </div>
        </div>
      </section>

      <section class="cards">
        <article v-for="r in ultimasPorCuarto" :class="['card', 'border-0',
          { 'alert-media': r.prioridad === 'MEDIA' },
          { 'alert-alta': r.prioridad === 'ALTA' },
          { 'alert-ia': r.prioridad === 'IA' },  { 'shadow-sm': !r.prioridad }
        ]"
        :key="`card-${r.cuarto_id ?? r.sensor_id ?? r.id}`"
        class="card"
        >
          <div class="card-head">
            <div class="head-left">
                <span  class="badge" :class="r.prioridad === 'ALTA' ? 'bg-danger': r.prioridad === 'MEDIA' ? 'bg-warning text-dark' : 'bg-primary'">
                  {{ r?.codigo ?? r?.id ?? `S${r?.sensor_id ?? '?'}` }}</span>
                <span class="ago text-muted">{{ fromNow(r?.tomado_en_utc) }}</span>
            </div>
            <div class="head-right">
              <svg v-if="r.prioridad === 'IA'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="icon text-primary" title="Anomalía detectada por IA">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM9.5 14.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm5 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM12 18c-2.28 0-4.22-1.66-5-4h10c-.78 2.34-2.72 4-5 4z"/>
              </svg>
              <svg viewBox="0 0 24 24" class="icon" :class="r.en_alerta ? 'text-danger' : 'text-secondary'">
                  <path d="M3 17h2v4H3zM7 13h2v8H7zM11 9h2v12h-2zM15 5h2v16h-2zM19 1h2v20h-2z"/>
              </svg>
            </div>
          </div>

          <div class="card-body">
            <svg viewBox="0 0 24 24" class="icon"><path d="M14 14.76V5a2 2 0 10-4 0v9.76a4 4 0 106.66 3.08A4 4 0 0014 14.76zM12 2a3 3 0 013 3v9.1a5.5 5.5 0 11-6 0V5a3 3 0 013-3z"/></svg>
            <div class="temp">
              <div class="value">
                {{ displayTemp(r?.temperatura_c) }}
              </div>
              <div class="unit">°{{ displayUnit }}</div>
            </div>
            <svg viewBox="0 0 24 24" class="icon check"><path d="M20 6L9 17l-5-5"/></svg>
          </div>

          <div class="card-foot">
            <div>
              <div class="room fw-semibold">{{ roomName(r) }}</div>
                <span class="pill" :class="r.prioridad === 'ALTA' 
                        ? 'bg-danger-subtle text-danger-emphasis'
                        : r.prioridad === 'MEDIA'
                        ? 'bg-warning-subtle text-warning-emphasis'
                        : r.prioridad === 'IA'
                        ? 'bg-primary-subtle text-primary-emphasis'  
                        : 'bg-light text-secondary'">
                  Humedad:
                  {{
                    r?.humedad_pct !== undefined && r?.humedad_pct !== null
                      ? `${r.humedad_pct.toFixed(2)} %`
                      : '--'
                  }}
                </span>
            </div>
             <button class="link btn btn-sm" :class="r.prioridad === 'ALTA' ? 'btn-outline-danger' : (r.prioridad === 'MEDIA' ? 'btn-outline-warning' : 'btn-outline-secondary')" @click="openModalChart(r)">Ver más</button>
          </div>
        </article>

        <!-- Estado vacío -->
        <article v-if="ultimasPorCuarto.length === 0" class="card" style="grid-column: 1 / -1">
          <div class="card-body" style="grid-template-columns: 1fr">
            <div class="temp">
              <div class="value">—</div>
            </div>
          </div>
          <div class="card-foot">
            <div class="room" style="color: #666">
              {{ error ? 'Error cargando datos' : 'Esperando lecturas...' }}
            </div>
          </div>
        </article>
      </section>

    <Modal v-if="selectedCuarto" @close="selectedCuarto = null">
      <div v-if="selectedCuarto">
        <h3>Historial de Temperatura - {{ roomName(selectedCuarto) }}</h3>
        <apexchart
          type="area"
          height="400"
          :options="individualChartOptions"
          :series="individualChartSeries"
        ></apexchart>
      </div>
    </Modal>

</template>


<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import mqtt from '../mqtt-shim.js'
import VueApexCharts from 'vue3-apexcharts';
import '../assets/dashboard.css'; 
import Modal from '../components/Modal.vue'; 
const apexchart = VueApexCharts;

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api'
const MQTT_URL = import.meta.env.VITE_MQTT_URL || 'ws://localhost:9001'
const MQTT_USER = import.meta.env.VITE_MQTT_USERNAME || undefined
const MQTT_PASS = import.meta.env.VITE_MQTT_PASSWORD || undefined 
const MQTT_TOPIC = import.meta.env.VITE_MQTT_TOPIC || 'cuartos_frios/lecturas'

const alertasActivas = ref([]);
let alertPollingInterval = null;
const conectado = ref(false);
const lecturas = ref([]);
const actual = ref(null);
const error = ref(null);
const cuartoNames = ref({});
let client = null;
const selectedCuarto = ref(null);
const individualSeries = ref([]);
const MAX_DATAPOINTS = 30;
const series = ref([]);
const visibleCuartos = ref(new Set());
const cargandoGrafica = ref(true);

const displayUnit = ref('F'); 
const toFahrenheit = (celsius) => (celsius * 9 / 5) + 32;

const individualChartOptions = computed(() => ({
  chart: { type: 'area', height: 400, toolbar: { tools: { download: true, selection: false, zoom: false, zoomin: true, zoomout: true, pan: false, reset: false } } },
  xaxis: { type: 'datetime', labels: { datetimeUTC: false } },
  yaxis: { title: { text: `Temperatura (°${displayUnit.value})` }, labels: { formatter: (val) => { return val.toFixed(1);}}},
  stroke: { curve: 'smooth' },
  dataLabels: { enabled: false }
}));

const chartOptions = computed(() => ({
  chart: { type: 'area', height: 350, zoom: { enabled: false }, toolbar: { show: true }, animations: { enabled: true, easing: 'linear', dynamicAnimation: { speed: 1000 } } },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth' },
  xaxis: { type: 'datetime', labels: { datetimeUTC: false } },
  yaxis: {
    title: { text: `Temperatura (°${displayUnit.value})` }, 
    labels: { formatter: (val) => val.toFixed(1) }
  },
  tooltip: { x: { format: 'dd MMM yyyy - HH:mm:ss' } },
  legend: { position: 'top', onItemClick: { toggleDataSeries: false }, onItemHover: { highlightDataSeries: true } },
  colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0', '#546E7A', '#26a69a'], 
}));

const individualChartSeries = computed(() => {
  if (displayUnit.value === 'C' || !individualSeries.value.length) return individualSeries.value;
  return [{
    ...individualSeries.value[0],
    data: individualSeries.value[0].data.map(([ts, tempC]) => [ts, toFahrenheit(tempC)])
  }];
});

const visibleSeries = computed(() => {
  return series.value.map(s => {
    const isVisible = visibleCuartos.value.has(s.cuarto_id);
    return {
      ...s,
      data: isVisible ? s.data : [],
      color: isVisible ? s.color : '#CCCCCC' 
    };
  });
});

const chartSeries = computed(() => {
  const source = visibleSeries.value;
  if (displayUnit.value === 'C') return source;
  return source.map(serie => ({
    ...serie,
    data: serie.data.map(([ts, tempC]) => [ts, toFahrenheit(tempC)])
  }));
});

const ultimasPorCuarto = computed(() => {
  const map = new Map()

  for (const r of lecturas.value) {
    const key = r?.cuarto_id ?? r?.sensor_id ?? r?.id
    if (key === undefined || key === null) continue
    if (!map.has(key)) {
      map.set(key, r)
    } else {
      const prev = map.get(key)
      const tPrev = new Date(prev?.tomado_en_utc || 0).getTime()
      const tNow = new Date(r?.tomado_en_utc || 0).getTime()
      if (tNow > tPrev) map.set(key, r)
    }
  }


  const arr = Array.from(map.values())
  
  
  arr.sort((a, b) =>
    String(a?.cuarto_id ?? a?.sensor_id ?? a?.id).localeCompare(
      String(b?.cuarto_id ?? b?.sensor_id ?? b?.id)
    )
  )

return arr.map(lectura => {
  const alerta = alertasActivas.value.find(a => 
    (a.cuarto_id === lectura.cuarto_id) && 
    (a.variable === 'TEMPERATURA' || a.variable === 'HUMEDAD') // Aseguramos que sea una alerta de variable
  );
  return { 
    ...lectura, 
    en_alerta: !!alerta, // Sigue siendo útil
    prioridad: alerta ? alerta.prioridad : null // ¡AQUÍ ESTÁ LA MAGIA!
  };
});
});


onMounted(async () => { 
  await cargarNombresDeCuartos(); 
  await cargarDatosGrafica();
  await cargarDatosCards();

  client = mqtt.connect(MQTT_URL, {
    username: MQTT_USER,
    password: MQTT_PASS,
    keepalive: 60,
    reconnectPeriod: 1000, // reconexión auto
  });

  client.on('connect', () => {
    conectado.value = true;
    error.value = null;
    client.subscribe(MQTT_TOPIC, { qos: 0 }, (err) => {
      if (err) error.value = `Error suscribiendo al tópico: ${err.message}`;
    });
    console.log('Pidiendo actualización inmediata...');
    client.publish('cuartos_frios/request_latest', '1', { qos: 0 });
  });
  client.on('reconnect', () => { /* opcional */ })
  client.on('close', () => { conectado.value = false })
  client.on('error', (err) => { error.value = `MQTT error: ${err.message}` })

  client.on('message', (_topic, payload) => {
    try {
      const data = JSON.parse(payload.toString());
      const d = procesarDatos(data);
      if (!d.tomado_en_utc) d.tomado_en_utc = new Date().toISOString();
      
      actual.value = d;
      lecturas.value = [d, ...lecturas.value].slice(0, 300);

      const seriesIndex = series.value.findIndex(s => s.cuarto_id === d.cuarto_id);
      if (seriesIndex !== -1) {
        const newDataPoint = [
          new Date().getTime(),
          d.temperatura_c
        ];

        series.value[seriesIndex].data.push(newDataPoint);
        if (series.value[seriesIndex].data.length > MAX_DATAPOINTS) {
          series.value[seriesIndex].data.shift();
        }
      }

     if (selectedCuarto.value && d.cuarto_id === selectedCuarto.value.cuarto_id && individualSeries.value.length > 0) {
      const newDataPoint = [new Date().getTime(), d.temperatura_c];
      individualSeries.value[0].data.push(newDataPoint);
      if (individualSeries.value[0].data.length > 200) {
        individualSeries.value[0].data.shift();
      }
    }

    } catch (e) {
      console.error('Mensaje MQTT inválido', e);
    }
  });
      fetchAlertasActivas(); 
    alertPollingInterval = setInterval(fetchAlertasActivas, 10000);
});

onUnmounted(() => {
  if (client) client.end(true);
  clearInterval(alertPollingInterval); 
});

const cargarDatosCards = async () => {
  try {
    const response = await fetch(`${API_BASE}/ultimas?by=cuarto`);
    const result = await response.json();
    if (result.success && Array.isArray(result.data)) {
      const procesados = result.data.map(procesarDatos);
      lecturas.value = procesados;
      actual.value = procesados[0] ?? null;

      for (const cuarto of procesados) {
        if (cuarto.cuarto_id && cuarto.room_name) {
          cuartoNames.value[cuarto.cuarto_id] = cuarto.room_name;
        }
      }  

    } else {
      error.value = 'Formato de datos inesperado del servidor';
    }
  } catch (e) {
    error.value = `Error cargando datos para las tarjetas: ${e.message}`;
  }
};

const cargarDatosGrafica = async () => {
  cargandoGrafica.value = true;
  try {
    const response = await fetch(`${API_BASE}/lecturas/grafica`);
    const result = await response.json();
    if (result.success) {
      const datosAgrupados = result.data;
      const nuevasSeries = [];
      for (const cuarto_id in datosAgrupados) {
        nuevasSeries.push({
          cuarto_id: parseInt(cuarto_id),
          name: datosAgrupados[cuarto_id][0]?.cuarto_nombre || `Cuarto ${cuarto_id}`,
          data: datosAgrupados[cuarto_id].map(lectura => [
            new Date(lectura.ingresado_en).getTime(),
            parseFloat(lectura.temperatura_c)
          ])
        });
      }
      series.value = nuevasSeries;
      visibleCuartos.value.clear();
      nuevasSeries.forEach(s => visibleCuartos.value.add(s.cuarto_id));
    }
  } catch (e) {
    console.error("Error cargando datos para la gráfica:", e);
  } finally {
    cargandoGrafica.value = false; 
  }
};

const procesarDatos = (data) => {
  if (!data) return data;
  const toNumberOrUndef = (v) => (v !== null && v !== '' && !isNaN(Number(v))) ? Number(v) : undefined;
  return { ...data, temperatura_c: toNumberOrUndef(data.temperatura_c), humedad_pct: toNumberOrUndef(data.humedad_pct) };
};
      
const fromNow = (dateStr) => {
  if (!dateStr) return ''
  const ms = Date.now() - new Date(dateStr).getTime()
  if (!Number.isFinite(ms)) return ''
  const s = Math.floor(ms / 1000)
  if (s < 60) return 'hace unos segundos'
  const m = Math.floor(s / 60)
  if (m < 60) return `hace ${m} min`
  const h = Math.floor(m / 60)
  if (h < 24) return `hace ${h} h`
  const d = Math.floor(h / 24)
  return `hace ${d} d`
};

const roomName = (r) => {
  if (!r) return '—';
  if (r.cuarto_id && cuartoNames.value[r.cuarto_id]) {
    return cuartoNames.value[r.cuarto_id];
  }
  if (r.room_name) {
    return r.room_name;
  }
  return r.cuarto_id ? `CUARTO ${r.cuarto_id}` : `SENSOR ${r.sensor_id}`;
};

function handleLegendClick(chartContext, seriesIndex, config) {
  
  const cuartoId = series.value[seriesIndex].cuarto_id;
  if (visibleCuartos.value.has(cuartoId)) {
    visibleCuartos.value.delete(cuartoId); 
  } else {
    visibleCuartos.value.add(cuartoId);
  }
  const seriesName = config.globals.seriesNames[seriesIndex];
  
  chartContext.toggleSeries(seriesName);
}

async function openModalChart(cuarto) {
  selectedCuarto.value = cuarto;
  individualSeries.value = []; 

 try {
    const hoy = new Date();
    const ayer = new Date();
    ayer.setDate(hoy.getDate() - 1); 

    const formatoFecha = (fecha) => fecha.toISOString().split('T')[0];
    const fechaInicio = formatoFecha(ayer);
    const fechaFin = formatoFecha(hoy);
    
    const url = `${API_BASE}/lecturas?cuarto_id=${cuarto.cuarto_id}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&sort=ASC`;
    

    const response = await fetch(url);
    const result = await response.json();
    if (result.success && Array.isArray(result.data)) {
      individualSeries.value = [{
        name: roomName(cuarto),
        data: result.data.map(lectura => [
          new Date(lectura.ingresado_en).getTime(),
          parseFloat(lectura.temperatura_c)
        ])
      }];
    }
  } catch(e) {
    console.error("Error cargando datos para la gráfica individual:", e);
  }
}

const cargarNombresDeCuartos = async () => {
  try {
    const response = await fetch(`${API_BASE}/cuartos`);
    const result = await response.json();
    
    if (result.success && Array.isArray(result.data)) {   
      const namesMap = {};         
      for (const cuarto of result.data) {   
        namesMap[cuarto.id] = cuarto.nombre;
      }        
      cuartoNames.value = namesMap;
    }
  } catch (e) {
    console.error("Error crítico al cargar los nombres de los cuartos:", e);
  }
};

const displayTemp = (celsius) => {
  if (celsius === undefined || celsius === null) return '--';
  return (displayUnit.value === 'F' ? toFahrenheit(celsius) : celsius).toFixed(2);
};

const fetchAlertasActivas = async () => {
  try {
    const response = await fetch(`${API_BASE}/alertas/activas`);
    const result = await response.json();
    if (result.success) {
      alertasActivas.value = result.data;
    }
  } catch (e) {
    console.error("Error al obtener alertas activas:", e);
  }
};
</script>