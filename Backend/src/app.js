// src/app.js
const express = require('express');
const cors = require('cors');
const http = require('http');
require('dotenv').config();

const api = require('./routes/api');

const app = express();
app.use(cors({ origin: ['http://localhost:5173', 'http://localhost:3000'] })); // agrega tu dominio si es remoto
app.use(express.json());
app.use('/api', api);

// ---- socket.io sobre http server ----
const server = http.createServer(app);
const { Server } = require('socket.io');
const io = new Server(server, {
  cors: { origin: ['http://localhost:5173', 'http://localhost:3000'], methods: ['GET', 'POST'] }
});

// Inicializa el listener MQTT y pásale io para emitir eventos al front
require('./config/mqtt')(io);

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`🚀 API + Socket.IO en http://localhost:${PORT}`);
});
