// src/config/database.js
const mysql = require('mysql2');
require('dotenv').config();

const pool = mysql.createPool({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,   // usa el mismo nombre en .env
  database: process.env.DB_NAME,
  port: Number(process.env.DB_PORT || 3306),
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
  connectTimeout: 60000,
  acquireTimeout: 60000,
  timeout: 60000,
  // Trabaja en UTC para que 'tomado_en_utc' sea consistente
  timezone: 'Z',
  charset: 'utf8mb4'
});

const db = pool.promise();

// Probar conexión una vez
db.getConnection()
  .then(conn => { console.log('✅ Conectado a MySQL'); conn.release(); })
  .catch(err => { console.error('❌ Error conectando a MySQL:', err.message); });

module.exports = db;
