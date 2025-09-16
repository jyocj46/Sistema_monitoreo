// src/controllers/dataController.js
const db = require('../config/database');

// POST /api/lecturas
// Body: { cuarto_id, sensor_id, temperatura_c, humedad_pct, tomado_en_utc? }
async function crearLectura(req, res) {
  try {
    const { cuarto_id, sensor_id, temperatura_c, humedad_pct, tomado_en_utc } = req.body || {};

    if (!Number.isInteger(cuarto_id) || !Number.isInteger(sensor_id)) {
      return res.status(400).json({ ok: false, error: 'cuarto_id y sensor_id deben ser enteros' });
    }
    if (typeof temperatura_c !== 'number' || typeof humedad_pct !== 'number') {
      return res.status(400).json({ ok: false, error: 'temperatura_c y humedad_pct deben ser numéricos' });
    }

    // Estado básico (ajusta reglas si quieres)
    let estado = 'OK';
    if (temperatura_c < -40 || temperatura_c > 80 || humedad_pct < 0 || humedad_pct > 100) {
      estado = 'SOSPECHOSA';
    }

    // Si no mandas tomado_en_utc, usamos UTC_TIMESTAMP() en SQL
    const sqlCon = `
      INSERT INTO lectura (cuarto_id, sensor_id, temperatura_c, humedad_pct, origen, estado, tomado_en_utc)
      VALUES (?, ?, ?, ?, 'HTTP', ?, CONVERT_TZ(?, '+00:00', '+00:00'))
    `;
    const sqlSin = `
      INSERT INTO lectura (cuarto_id, sensor_id, temperatura_c, humedad_pct, origen, estado, tomado_en_utc)
      VALUES (?, ?, ?, ?, 'HTTP', ?, UTC_TIMESTAMP())
    `;

    const paramsCon = [cuarto_id, sensor_id, temperatura_c, humedad_pct, estado, tomado_en_utc];
    const paramsSin = [cuarto_id, sensor_id, temperatura_c, humedad_pct, estado];

    const [result] = await db.execute(tomado_en_utc ? sqlCon : sqlSin, tomado_en_utc ? paramsCon : paramsSin);
    return res.json({ ok: true, id: result.insertId });
  } catch (err) {
    console.error('Error crearLectura:', err);
    return res.status(500).json({ ok: false, error: 'Error de servidor' });
  }
}

// (Opcional) GET /api/lecturas?sensor_id=1&limit=50
async function listarLecturas(req, res) {
  try {
    const sensorId = req.query.sensor_id ? Number(req.query.sensor_id) : null;
    const limit = req.query.limit ? Math.min(Number(req.query.limit), 500) : 50;

    const where = [];
    const params = [];
    if (Number.isInteger(sensorId)) { where.push('sensor_id = ?'); params.push(sensorId); }

    const sql = `
      SELECT id, cuarto_id, sensor_id, temperatura_c, humedad_pct, origen, estado, tomado_en_utc, ingresado_en
      FROM lectura
      ${where.length ? 'WHERE ' + where.join(' AND ') : ''}
      ORDER BY tomado_en_utc DESC
      LIMIT ?
    `;
    params.push(limit);

    const [rows] = await db.execute(sql, params);
    return res.json({ ok: true, data: rows });
  } catch (err) {
    console.error('Error listarLecturas:', err);
    return res.status(500).json({ ok: false, error: 'Error de servidor' });
  }
}

module.exports = { crearLectura, listarLecturas };
