<template>
  <div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="brand">Grupo Detpon</div>
      <nav class="menu">
        <a class="active">Dashboard</a>
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
      <!-- Topbar -->
      <header class="topbar">
        <div class="left">
          <div class="crumbs">
            <span>Monitoreo en tiempo real</span>
          </div>
        </div>
        <div class="right" style="align-items: center; display: flex; gap: 12px">
          <div
            :style="{
              padding: '4px 10px',
              borderRadius: '999px',
              background: conectado ? '#e6ffed' : '#ffeeee',
              color: conectado ? '#137333' : '#a50e0e',
              fontSize: '12px',
              border: '1px solid #ddd'
            }"
          >
            Socket: {{ conectado ? 'Conectado' : 'Desconectado' }}
          </div>
          <div class="userdot"></div>
        </div>
      </header>

      <!-- Grid de tarjetas por cuarto -->
      <section class="cards">
        <article 
          v-for="r in ultimasPorCuarto" 
          :key="`card-${r.cuarto_id ?? r.sensor_id ?? r.id}`" 
          class="card"
        >
          <div class="card-head">
            <div class="head-left">
              <span class="badge">{{ r?.codigo ?? r?.id ?? `S${r?.sensor_id ?? '?'}` }}</span>
              <span class="ago">{{ fromNow(r?.tomado_en_utc) }}</span>
            </div>
            <div class="head-right">
              <!-- Iconos SVG -->
              <svg viewBox="0 0 24 24" class="icon"><path d="M3 17h2v4H3zM7 13h2v8H7zM11 9h2v12h-2zM15 5h2v16h-2zM19 1h2v20h-2z"/></svg>
              <svg viewBox="0 0 24 24" class="icon"><path d="M16 7H3a2 2 0 00-2 2v6a2 2 0 002 2h13a2 2 0 002-2V9a2 2 0 00-2-2zm5 3v4"/></svg>
              <svg viewBox="0 0 24 24" class="icon"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
            </div>
          </div>

          <div class="card-body">
            <svg viewBox="0 0 24 24" class="icon"><path d="M14 14.76V5a2 2 0 10-4 0v9.76a4 4 0 106.66 3.08A4 4 0 0014 14.76zM12 2a3 3 0 013 3v9.1a5.5 5.5 0 11-6 0V5a3 3 0 013-3z"/></svg>
            <div class="temp">
              <div class="value">
                {{ r?.temperatura_c !== undefined && r?.temperatura_c !== null ? r.temperatura_c.toFixed(2) : '--' }}
              </div>
              <div class="unit">°C</div>
            </div>
            <svg viewBox="0 0 24 24" class="icon check"><path d="M20 6L9 17l-5-5"/></svg>
          </div>

          <div class="card-foot">
            <div>
              <div class="room">{{ roomName(r) }}</div>
              <span class="pill">
                Humedad: {{ r?.humedad_pct !== undefined && r?.humedad_pct !== null ? `${r.humedad_pct.toFixed(2)} %` : '--' }}
              </span>
            </div>
            <button class="link" :title="r?.tomado_en_utc || ''">Ver más</button>
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
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import mqtt from './mqtt-shim.js'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api'
const MQTT_URL = import.meta.env.VITE_MQTT_URL || 'ws://localhost:9001' // ajusta al tuyo
const MQTT_USER = import.meta.env.VITE_MQTT_USERNAME || undefined
const MQTT_PASS = import.meta.env.VITE_MQTT_PASSWORD || undefined
const MQTT_TOPIC = import.meta.env.VITE_MQTT_TOPIC || 'cuartos_frios/lecturas'
const DEFAULT_SENSOR_ID = Number(import.meta.env.VITE_DEFAULT_SENSOR_ID || 1)

const conectado = ref(false)
const lecturas = ref([])
const actual = ref(null)
const error = ref(null)
let client = null

const toNumberOrUndef = (v) =>
  v !== undefined && v !== null && v !== '' && !Number.isNaN(Number(v))
    ? Number(v)
    : undefined

const procesarDatos = (data) =>
  !data
    ? data
    : {
        ...data,
        temperatura_c: toNumberOrUndef(data.temperatura_c),
        humedad_pct: toNumberOrUndef(data.humedad_pct),
      }

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
}

const roomName = (r) => 
  r?.cuarto_nombre ||
  (r?.cuarto_id ? `CUARTO ${r.cuarto_id}` : r?.sensor_id ? `SENSOR ${r.sensor_id}` : '—')

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
  return arr
})

onMounted(() => {
  // 1) Conectar a MQTT WebSocket
  client = mqtt.connect(MQTT_URL, {
    username: MQTT_USER,
    password: MQTT_PASS,
    keepalive: 60,
    reconnectPeriod: 1000, // reconexión auto
  })

  client.on('connect', () => {
    conectado.value = true
    error.value = null
    // Suscribirse al tópico
    client.subscribe(MQTT_TOPIC, { qos: 0 }, (err) => {
      if (err) error.value = `Error suscribiendo al tópico: ${err.message}`
    })
  })

  client.on('reconnect', () => { /* opcional */ })
  client.on('close', () => { conectado.value = false })
  client.on('error', (err) => { error.value = `MQTT error: ${err.message}` })

  client.on('message', (_topic, payload) => {
    try {
      const data = JSON.parse(payload.toString())
      const d = procesarDatos(data)
      // Si tu backend no añade timestamp, usa ahora:
      if (!d.tomado_en_utc) d.tomado_en_utc = new Date().toISOString()

      actual.value = d
      lecturas.value = [d, ...lecturas.value].slice(0, 300)
    } catch (e) {
      console.error('Mensaje MQTT inválido', e)
    }
  })

  // 2) Cargar historial inicial desde API
  cargarHistorialInicial()
})

onUnmounted(() => {
  if (client) {
    try { client.end(true) } catch {}
    client = null
  }
})

const cargarHistorialInicial = async () => {
  try {
    const response = await fetch(`${API_BASE}/lecturas?sensor_id=${DEFAULT_SENSOR_ID}&limit=200`)
    const data = await response.json()
    const arr = Array.isArray(data) ? data : data?.data
    if (Array.isArray(arr)) {
      const procesados = arr.map(procesarDatos)
      lecturas.value = procesados
      actual.value = procesados[0] ?? null
    } else {
      error.value = 'Formato de datos inesperado del servidor'
    }
  } catch (e) {
    error.value = `Error cargando datos: ${e.message}`
  }
}
</script>