const db = require('../config/database');

exports.receiveData = async (req, res) => {
  try {
    const { sensor, value, device_id } = req.body;

    if (!sensor || value === undefined) {
      return res.status(400).json({ error: 'Datos incompletos' });
    }

    const query = 'INSERT INTO sensor_data (sensor_type, value, device_id) VALUES (?, ?, ?)';
    const [results] = await db.query(query, [sensor, value, device_id || 'ESP32']);
    
    res.status(200).json({ 
      message: 'Datos recibidos correctamente',
      id: results.insertId 
    });
  } catch (error) {
    console.error('Error insertando datos:', error);
    res.status(500).json({ 
      error: 'Error en la base de datos',
      details: error.message 
    });
  }
};

exports.getData = async (req, res) => {
  try {
    const query = 'SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 100';
    const [results] = await db.query(query);
    
    res.status(200).json(results);
  } catch (error) {
    console.error('Error obteniendo datos:', error);
    res.status(500).json({ 
      error: 'Error en la base de datos',
      details: error.message 
    });
  }
};