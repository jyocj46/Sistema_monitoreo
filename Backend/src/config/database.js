const mysql = require('mysql2');
require('dotenv').config();

// Pool de conexiones para mejor performance
const pool = mysql.createPool({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME || 'coldrooms',
  port: process.env.DB_PORT || 3306,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
  connectTimeout: 60000,
  acquireTimeout: 60000,
  timeout: 60000,
  timezone: 'local',
  charset: 'utf8mb4'
});

// Promisify para usar async/await
const promisePool = pool.promise();

// Verificar conexión
promisePool.getConnection()
  .then(connection => {
    console.log('✅ Conectado a MySQL - Base de datos ColdRooms');
    connection.release();
  })
  .catch(err => {
    console.error('❌ Error conectando a MySQL:', err.message);
  });

module.exports = promisePool;