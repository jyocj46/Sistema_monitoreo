// App.jsx
import { useEffect, useMemo, useState } from "react";
import { io } from "socket.io-client";
import axios from "axios";
import "./App.css"; // <= usa el CSS de diseño que ya te pasé

// === Configuración ===
const SOCKET_URL = import.meta.env.VITE_SOCKET_URL || "http://localhost:3000";
const API_BASE = import.meta.env.VITE_API_BASE_URL || "http://localhost:3000/api";
const DEFAULT_SENSOR_ID = Number(import.meta.env.VITE_DEFAULT_SENSOR_ID || 1);

// === Iconos SVG (mismos del diseño) ===
const ThermIcon = () => (
  <svg viewBox="0 0 24 24" className="icon">
    <path d="M14 14.76V5a2 2 0 10-4 0v9.76a4 4 0 106.66 3.08A4 4 0 0014 14.76zM12 2a3 3 0 013 3v9.1a5.5 5.5 0 11-6 0V5a3 3 0 013-3z" />
  </svg>
);
const SignalIcon = () => (
  <svg viewBox="0 0 24 24" className="icon">
    <path d="M3 17h2v4H3zM7 13h2v8H7zM11 9h2v12h-2zM15 5h2v16h-2zM19 1h2v20h-2z" />
  </svg>
);
const BatteryIcon = () => (
  <svg viewBox="0 0 24 24" className="icon">
    <path d="M16 7H3a2 2 0 00-2 2v6a2 2 0 002 2h13a2 2 0 002-2V9a2 2 0 00-2-2zm5 3v4" />
  </svg>
);
const CheckIcon = () => (
  <svg viewBox="0 0 24 24" className="icon check">
    <path d="M20 6L9 17l-5-5" />
  </svg>
);
const DotsIcon = () => (
  <svg viewBox="0 0 24 24" className="icon">
    <circle cx="5" cy="12" r="2" />
    <circle cx="12" cy="12" r="2" />
    <circle cx="19" cy="12" r="2" />
  </svg>
);

// === Utilidades ===
const toNumberOrUndef = (v) =>
  v !== undefined && v !== null && v !== "" && !Number.isNaN(Number(v))
    ? Number(v)
    : undefined;

const procesarDatos = (data) =>
  !data
    ? data
    : {
        ...data,
        temperatura_c: toNumberOrUndef(data.temperatura_c),
        humedad_pct: toNumberOrUndef(data.humedad_pct),
      };

function fromNow(dateStr) {
  if (!dateStr) return "";
  const ms = Date.now() - new Date(dateStr).getTime();
  if (!Number.isFinite(ms)) return "";
  const s = Math.floor(ms / 1000);
  if (s < 60) return "hace unos segundos";
  const m = Math.floor(s / 60);
  if (m < 60) return `hace ${m} min`;
  const h = Math.floor(m / 60);
  if (h < 24) return `hace ${h} h`;
  const d = Math.floor(h / 24);
  return `hace ${d} d`;
}

export default function App() {
  const [conectado, setConectado] = useState(false);
  const [lecturas, setLecturas] = useState([]); // historial reciente (varios sensores/cuartos)
  const [actual, setActual] = useState(null); // última lectura recibida (cualquiera)
  const [error, setError] = useState(null);

  // Socket con reconexión automática
  const socket = useMemo(() => {
    return io(SOCKET_URL, {
      transports: ["websocket", "polling"],
      reconnection: true,
      reconnectionAttempts: Infinity,
      reconnectionDelay: 1000,
      timeout: 10000,
      autoConnect: false, // conectamos manualmente
    });
  }, []);

  // Eventos de socket
  useEffect(() => {
    if (!socket) return;

    const onConnect = () => {
      setConectado(true);
      setError(null);
    };
    const onDisconnect = () => setConectado(false);
    const onConnectError = (err) => {
      setConectado(false);
      setError(`Error de conexión: ${err.message}`);
    };
    const onNuevaLectura = (data) => {
      const d = procesarDatos(data);
      setActual(d);
      
      setLecturas((prev) => [d, ...prev].slice(0, 300));
    };

    socket.on("connect", onConnect);
    socket.on("disconnect", onDisconnect);
    socket.on("connect_error", onConnectError);
    socket.on("nueva-lectura", onNuevaLectura);

    socket.connect();

    return () => {
      socket.off("connect", onConnect);
      socket.off("disconnect", onDisconnect);
      socket.off("connect_error", onConnectError);
      socket.off("nueva-lectura", onNuevaLectura);
      socket.disconnect();
    };
  }, [socket]);

  // Historial inicial (nota: filtra por DEFAULT_SENSOR_ID; si quieres TODOS, quita ese parámetro)
  useEffect(() => {
    (async () => {
      try {
        const { data } = await axios.get(`${API_BASE}/lecturas`, {
          params: { sensor_id: DEFAULT_SENSOR_ID, limit: 200 },
          timeout: 7000,
        });

        const arr = Array.isArray(data) ? data : data?.data;
        if (Array.isArray(arr)) {
          const procesados = arr.map(procesarDatos);
          setLecturas(procesados);
          setActual(procesados[0] ?? null);
        } else {
          setError("Formato de datos inesperado del servidor");
        }
      } catch (e) {
        setError(`Error cargando datos: ${e.message}`);
      }
    })();
  }, []);

  // Última lectura por CUARTO (o por sensor si no hay cuarto_id)
  const ultimasPorCuarto = useMemo(() => {
    const map = new Map();
    for (const r of lecturas) {
      const key = r?.cuarto_id ?? r?.sensor_id ?? r?.id;
      if (key === undefined || key === null) continue;
      if (!map.has(key)) {
        map.set(key, r);
      } else {
        const prev = map.get(key);
        const tPrev = new Date(prev?.tomado_en_utc || 0).getTime();
        const tNow = new Date(r?.tomado_en_utc || 0).getTime();
        if (tNow > tPrev) map.set(key, r);
      }
    }
    const arr = Array.from(map.values());
    // orden por cuarto_id -> sensor_id -> id
    arr.sort((a, b) =>
      String(a?.cuarto_id ?? a?.sensor_id ?? a?.id).localeCompare(
        String(b?.cuarto_id ?? b?.sensor_id ?? b?.id)
      )
    );
    return arr;
  }, [lecturas]);

  return (
    <div className="layout">
      {/* Sidebar */}
      <aside className="sidebar">
        <div className="brand">Grupo Detpon</div>
        <nav className="menu">
          <a className="active">Dashboard</a>
          <a>Historial</a>
          <a>Rangos</a>
          <a>Aplicación de Productos</a>
          <a>Cultivos</a>
          <a>Productos</a>
          <a>Administración</a>
        </nav>
      </aside>

      {/* Contenido */}
      <main className="main">
        {/* Topbar */}
        <header className="topbar">
          <div className="left">
            <div className="crumbs">
              <span>Monitoreo en tiempo real</span>
            </div>
          </div>
          <div className="right" style={{ alignItems: "center", display: "flex", gap: 12 }}>
            <div
              style={{
                padding: "4px 10px",
                borderRadius: 999,
                background: conectado ? "#e6ffed" : "#ffeeee",
                color: conectado ? "#137333" : "#a50e0e",
                fontSize: 12,
                border: "1px solid #ddd",
              }}
            >
              Socket: {conectado ? "Conectado" : "Desconectado"}
            </div>
            <div className="userdot" />
          </div>
        </header>

        {/* Grid de tarjetas por cuarto */}
        <section className="cards">
          {ultimasPorCuarto.map((r) => {
            const roomName =
              r?.cuarto_nombre ||
              (r?.cuarto_id ? `CUARTO ${r.cuarto_id}` : r?.sensor_id ? `SENSOR ${r.sensor_id}` : "—");

            return (
              <article key={`card-${r.cuarto_id ?? r.sensor_id ?? r.id}`} className="card">
                <div className="card-head">
                  <div className="head-left">
                    <span className="badge">
                      {r?.codigo ?? r?.id ?? `S${r?.sensor_id ?? "?"}`}
                    </span>
                    <span className="ago">{fromNow(r?.tomado_en_utc)}</span>
                  </div>
                  <div className="head-right">
                    <SignalIcon />
                    <BatteryIcon />
                    <DotsIcon />
                  </div>
                </div>

                <div className="card-body">
                  <ThermIcon />
                  <div className="temp">
                    <div className="value">
                      {r?.temperatura_c !== undefined && r?.temperatura_c !== null
                        ? r.temperatura_c.toFixed(2)
                        : "--"}
                    </div>
                    <div className="unit">°C</div>
                  </div>
                  <CheckIcon />
                </div>

                <div className="card-foot">
                  <div>
                    <div className="room">{roomName}</div>
                    <span className="pill">
                      Humedad:{" "}
                      {r?.humedad_pct !== undefined && r?.humedad_pct !== null
                        ? `${r.humedad_pct.toFixed(2)} %`
                        : "--"}
                    </span>
                  </div> 
                  <button className="link" title={r?.tomado_en_utc || ""}>
                    Ver más
                  </button>
                </div>
              </article>
            );
          })}

          {ultimasPorCuarto.length === 0 && (
            <article className="card" style={{ gridColumn: "1 / -1" }}>
              <div className="card-body" style={{ gridTemplateColumns: "1fr" }}>
                <div className="temp">
                  <div className="value">—</div>
                </div>
              </div>
              <div className="card-foot">
                <div className="room" style={{ color: "#666" }}>
                  {error ? "Error cargando datos" : "Esperando lecturas..."}
                </div>
              </div>
            </article>
          )}
        </section>

        {/* Debug opcional (igual que tu versión) */}
        <details
          style={{
            margin: "0 22px 22px",
            background: "#f5f5f5",
            padding: "8px",
            borderRadius: "8px",
            color: "#334155",
          }}
        >
          <summary style={{ cursor: "pointer" }}>Información de depuración</summary>
          <div style={{ fontSize: "12px", marginTop: "8px" }}>
            <div>Socket URL: {SOCKET_URL}</div>
            <div>API Base: {API_BASE}</div>
            <div>Sensor ID (inicial): {DEFAULT_SENSOR_ID}</div>
            <div>Lecturas en memoria: {lecturas.length}</div>
            <div>Última lectura cruda: {JSON.stringify(actual)}</div>
          </div>
        </details>
      </main>
    </div>
  );
}
