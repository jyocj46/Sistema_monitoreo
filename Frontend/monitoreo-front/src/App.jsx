import { useEffect, useMemo, useState } from 'react'
import { io } from 'socket.io-client'
import axios from 'axios'

const SOCKET_URL = import.meta.env.VITE_SOCKET_URL || 'http://localhost:3000'
const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3000/api'
const DEFAULT_SENSOR_ID = Number(import.meta.env.VITE_DEFAULT_SENSOR_ID || 1)

console.log('Configuración:', { SOCKET_URL, API_BASE, DEFAULT_SENSOR_ID })

export default function App() {
  const [conectado, setConectado] = useState(false)
  const [lecturas, setLecturas] = useState([])
  const [actual, setActual] = useState(null)
  const [error, setError] = useState(null)

  // Socket con reconexión automática
  const socket = useMemo(() => {
    console.log('Creando socket para:', SOCKET_URL)
    return io(SOCKET_URL, {
      transports: ['websocket', 'polling'],
      reconnection: true,
      reconnectionAttempts: Infinity,
      reconnectionDelay: 1000,
      timeout: 10000
    })
  }, [])

  // Función para procesar datos y convertir strings a números
  const procesarDatos = (data) => {
    if (!data) return data;
    
    return {
      ...data,
      temperatura_c: data.temperatura_c !== undefined ? Number(data.temperatura_c) : undefined,
      humedad_pct: data.humedad_pct !== undefined ? Number(data.humedad_pct) : undefined
    };
  };

  // Efecto para eventos del socket
  useEffect(() => {
    if (!socket) return

    const onConnect = () => {
      console.log('✅ Conectado al servidor Socket.IO')
      setConectado(true)
      setError(null)
    }

    const onDisconnect = () => {
      console.log('❌ Desconectado del servidor Socket.IO')
      setConectado(false)
    }

    const onConnectError = (err) => {
      console.error('❌ Error de conexión Socket.IO:', err.message)
      setConectado(false)
      setError(`Error de conexión: ${err.message}`)
    }

    const onNuevaLectura = (data) => {
      console.log('📊 Nueva lectura:', data)
      const datosProcesados = procesarDatos(data)
      setActual(datosProcesados)
      setLecturas(prev => [datosProcesados, ...prev.slice(0, 49)])
    }

    // Configurar event listeners
    socket.on('connect', onConnect)
    socket.on('disconnect', onDisconnect)
    socket.on('connect_error', onConnectError)
    socket.on('nueva-lectura', onNuevaLectura)

    // Conectar manualmente
    socket.connect()

    // Limpieza
    return () => {
      socket.off('connect', onConnect)
      socket.off('disconnect', onDisconnect)
      socket.off('connect_error', onConnectError)
      socket.off('nueva-lectura', onNuevaLectura)
      socket.disconnect()
    }
  }, [socket])

  // Cargar historial inicial
  useEffect(() => {
    const cargarHistorial = async () => {
      try {
        console.log('📦 Cargando historial desde:', `${API_BASE}/lecturas`)
        
        const { data } = await axios.get(`${API_BASE}/lecturas`, {
          params: { 
            sensor_id: DEFAULT_SENSOR_ID, 
            limit: 20
          },
          timeout: 5000
        })

        console.log('📦 Respuesta del servidor:', data)

        if (data && data.ok && Array.isArray(data.data)) {
          // Procesar datos para convertir strings a números
          const datosProcesados = data.data.map(procesarDatos)
          setLecturas(datosProcesados)
          setActual(datosProcesados[0])
        } else if (Array.isArray(data)) {
          // Si la respuesta es directamente un array
          const datosProcesados = data.map(procesarDatos)
          setLecturas(datosProcesados)
          setActual(datosProcesados[0])
        } else {
          console.warn('Formato de respuesta inesperado:', data)
          setError('Formato de datos inesperado del servidor')
        }
      } catch (error) {
        console.error('❌ Error cargando historial:', error.message)
        setError(`Error cargando datos: ${error.message}`)
      }
    }

    cargarHistorial()
  }, [API_BASE, DEFAULT_SENSOR_ID])

  return (
    <div style={{ fontFamily: 'system-ui, Arial', padding: 16, maxWidth: 900, margin: '0 auto' }}>
      <header style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 16 }}>
        <h1 style={{ margin: 0 }}>Monitoreo en tiempo real</h1>
        <span style={{
          padding: '4px 10px', borderRadius: 999,
          background: conectado ? '#e6ffed' : '#ffeeee',
          color: conectado ? '#137333' : '#a50e0e', 
          fontSize: 12, 
          border: '1px solid #ddd'
        }}>
          Socket: {conectado ? 'Conectado' : 'Desconectado'}
        </span>
      </header>

      {error && (
        <div style={{
          background: '#ffebee',
          color: '#c62828',
          padding: '12px',
          borderRadius: '8px',
          marginBottom: '16px',
          border: '1px solid #ffcdd2'
        }}>
          ⚠️ {error}
        </div>
      )}

      {/* Tarjetas con la última lectura */}
      <section style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 12, marginBottom: 16 }}>
        <Card title="Temperatura">
          <Big>
            {actual?.temperatura_c !== undefined && actual.temperatura_c !== null 
              ? actual.temperatura_c.toFixed(2) 
              : '--'} °C
          </Big>
        </Card>
        <Card title="Humedad">
          <Big>
            {actual?.humedad_pct !== undefined && actual.humedad_pct !== null 
              ? actual.humedad_pct.toFixed(2) 
              : '--'} %
          </Big>
        </Card>
        <Card title="Estado">
          <Big style={{ 
            color: actual?.estado === 'OK' ? 'green' : actual?.estado === 'ERROR' ? 'red' : 'orange' 
          }}>
            {actual?.estado ?? '--'}
          </Big>
          <small style={{ color: '#666' }}>
            {actual?.tomado_en_utc ? new Date(actual.tomado_en_utc).toLocaleString() : ''}
          </small>
        </Card>
      </section>

      {/* Información de debug */}
      <details style={{ marginBottom: '16px', background: '#f5f5f5', padding: '8px', borderRadius: '4px' }}>
        <summary style={{ cursor: 'pointer' }}>Información de depuración</summary>
        <div style={{ fontSize: '12px', marginTop: '8px' }}>
          <div>Socket URL: {SOCKET_URL}</div>
          <div>API Base: {API_BASE}</div>
          <div>Sensor ID: {DEFAULT_SENSOR_ID}</div>
          <div>Lecturas cargadas: {lecturas.length}</div>
          <div>Última lectura: {JSON.stringify(actual)}</div>
        </div>
      </details>

      {/* Tabla de últimas lecturas */}
      <section>
        <h3 style={{ marginTop: 0 }}>Últimas lecturas ({lecturas.length})</h3>
        <div style={{ overflow: 'auto', border: '1px solid #eee', borderRadius: 8 }}>
          <table width="100%" cellPadding={8} style={{ borderCollapse: 'collapse' }}>
            <thead style={{ background: '#fafafa' }}>
              <tr>
                <th align="left">ID</th>
                <th align="left">Sensor</th>
                <th align="left">Cuarto</th>
                <th align="left">Temperatura (°C)</th>
                <th align="left">Humedad (%)</th>
                <th align="left">Estado</th>
                <th align="left">Fecha</th>
              </tr>
            </thead>
            <tbody>
              {lecturas.map((r, index) => (
                <tr key={`row-${r.id}-${index}`} style={{ borderTop: '1px solid #f0f0f0' }}>
                  <td>{r.id ?? '--'}</td>
                  <td>{r.sensor_id ?? '--'}</td>
                  <td>{r.cuarto_id ?? '--'}</td>
                  <td>
                    {r.temperatura_c !== undefined && r.temperatura_c !== null 
                      ? Number(r.temperatura_c).toFixed(2) 
                      : '--'}
                  </td>
                  <td>
                    {r.humedad_pct !== undefined && r.humedad_pct !== null 
                      ? Number(r.humedad_pct).toFixed(2) 
                      : '--'}
                  </td>
                  <td>
                    <span style={{ 
                      color: r.estado === 'OK' ? 'green' : r.estado === 'ERROR' ? 'red' : 'orange',
                      fontWeight: 'bold'
                    }}>
                      {r.estado ?? '--'}
                    </span>
                  </td>
                  <td style={{ whiteSpace: 'nowrap', fontSize: '12px' }}>
                    {r.tomado_en_utc ? new Date(r.tomado_en_utc).toLocaleString() : '--'}
                  </td>
                </tr>
              ))}
              {lecturas.length === 0 && (
                <tr>
                  <td colSpan="7" style={{ color: '#666', textAlign: 'center', padding: '20px' }}>
                    {error ? 'Error cargando datos' : 'Cargando datos...'}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  )
}

function Card({ title, children }) {
  return (
    <div style={{ border: '1px solid #eee', borderRadius: 12, padding: 16 }}>
      <div style={{ color: '#666', fontSize: 14, marginBottom: 6 }}>{title}</div>
      <div>{children}</div>
    </div>
  )
}

function Big({ children, style }) {
  return <div style={{ fontSize: 28, fontWeight: 700, ...style }}>{children}</div>
}