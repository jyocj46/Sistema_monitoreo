import { ref, onMounted, onUnmounted } from 'vue'
import * as mqtt from 'mqtt/dist/mqtt.min.js'

export function useRealtime({
  mqttUrl = import.meta.env.VITE_MQTT_URL,
  topic = import.meta.env.VITE_MQTT_TOPIC || 'cuartos_frios/lecturas',
  username = import.meta.env.VITE_MQTT_USER || undefined,
  password = import.meta.env.VITE_MQTT_PASS || undefined,
  clientId,
} = {}) {
  const conectado = ref(false)
  const error = ref(null)
  const lecturas = ref([])
  const actual = ref(null)
  const ultimasPorCuarto = ref([])

  const porCuarto = new Map()
  let client = null

  function fromNow(iso) {
    if (!iso) return '—'
    try {
      const d = new Date(iso)
      const sec = Math.floor((Date.now() - d.getTime()) / 1000)
      if (sec < 60) return `${sec}s`
      const m = Math.floor(sec / 60)
      if (m < 60) return `${m}m`
      const h = Math.floor(m / 60)
      if (h < 24) return `${h}h`
      const d2 = Math.floor(h / 24)
      return `${d2}d`
    } catch { return '—' }
  }

  function applyReading(msg) {
    lecturas.value.push(msg)
    if (lecturas.value.length > 1000) lecturas.value.shift()

    const key = msg.cuarto_id ?? msg.sensor_id ?? Math.random()
    porCuarto.set(key, msg)
    ultimasPorCuarto.value = Array.from(porCuarto.values())
    actual.value = msg
  }

  onMounted(() => {
    error.value = null
    const opts = {
      clientId: clientId || `web_${Math.random().toString(16).slice(2)}`,
      clean: true,
      reconnectPeriod: 2000,
      keepalive: 60,
      connectTimeout: 30_000,
      username,
      password,
    }

    client = mqtt.connect(mqttUrl, opts)

    client.on('connect', () => {
      conectado.value = true
      error.value = null
      client.subscribe(topic, { qos: 0 }, (err) => {
        if (err) error.value = `Error al suscribir: ${err.message || err}`
      })
    })
    client.on('reconnect', () => { conectado.value = false })
    client.on('close', () => { conectado.value = false })
    client.on('error', (e) => { error.value = e?.message || 'Error MQTT' })

    client.on('message', (_t, payload) => {
      try {
        const msg = JSON.parse(payload.toString())
        applyReading(msg)
      } catch (e) {
        error.value = `Payload inválido: ${e?.message || e}`
      }
    })
  })

  onUnmounted(() => {
    if (client) { try { client.end(true) } catch {} client = null }
  })

  return { conectado, error, lecturas, actual, ultimasPorCuarto, fromNow }
}
