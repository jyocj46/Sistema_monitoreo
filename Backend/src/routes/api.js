// src/routes/api.js
const express = require('express');
const { crearLectura, listarLecturas } = require('../controllers/dataController');

const router = express.Router();

router.get('/health', (req, res) => res.json({ ok: true, ts: new Date().toISOString() }));
router.post('/lecturas', crearLectura);
router.get('/lecturas', listarLecturas);

module.exports = router;
