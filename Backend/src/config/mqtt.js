// src/config/mqtt.js
const db = require('../config/database');
require('dotenv').config();

module.exports = function initMqtt(io) {
  const mqtt = require('mqtt');

  const BROKER = process.env.MQTT_BROKER || 'mqtt://broker.hivemq.com';
  const TOPIC  = process.env.MQTT_TOPIC  || 'cuartos_frios/lecturas';

  const client = mqtt.connect(BROKER);

  client.on('connect', () => {
    console.log('✅ Conectado a MQTT:', BROKER);
    client.subscribe(TOPIC, (err) => {
      if (err) console.error('❌ Error al suscribirse:', err);
      else console.log('📡 Suscrito a', TOPIC);
    });
  });

  client.on('message', async (_topic, message) => {
    try {
      const data = JSON.parse(message.toString()); // {cuarto_id, sensor_id, temperatura_c, humedad_pct}
      const { cuarto_id, sensor_id, temperatura_c, humedad_pct } = data;

      // Reglas simples de estado
      let estado = 'OK';
      if (temperatura_c < -40 || temperatura_c > 80 || humedad_pct < 0 || humedad_pct > 100) estado = 'SOSPECHOSA';

      // Guarda en BD como origen 'MQTT'
      const sql = `
        INSERT INTO lectura (cuarto_id, sensor_id, temperatura_c, humedad_pct, origen, estado, tomado_en_utc)
        VALUES (?, ?, ?, ?, 'MQTT', ?, UTC_TIMESTAMP())
      `;
      const params = [cuarto_id, sensor_id, temperatura_c, humedad_pct, estado];
      const [result] = await db.execute(sql, params);

      // Emite al front (incluye id insertado y timestamp aprox)
      const payload = {
        id: result.insertId,
        cuarto_id, sensor_id, temperatura_c, humedad_pct,
        estado,
        tomado_en_utc: new Date().toISOString()
      };
      io.emit('nueva-lectura', payload);
    } catch (e) {
      console.error('❌ Error manejando mensaje MQTT:', e.message);
    }
  });

  client.on('error', (e) => console.error('MQTT error:', e.message));
  return client;
};
