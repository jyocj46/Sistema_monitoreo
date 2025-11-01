Sistema de Monitoreo de Cuartos Fríos
Este es un proyecto integral de IoT (Internet de las Cosas) para el monitoreo en tiempo real de temperatura y humedad, diseñado específicamente para la supervisión de cuartos fríos.

El sistema utiliza hardware ESP32 para la recolección de datos, una API en PHP como backend, una base de datos MySQL para la persistencia, y un dashboard interactivo en Vue.js para la visualización y gestión.

[Imagen del Dashboard Principal]

Características Principales
Dashboard en Tiempo Real: Visualización instantánea de las últimas lecturas de todos los sensores.

Gráficas Interactivas: Gráfica de área principal que muestra la evolución de la temperatura de todos los cuartos, con la capacidad de mostrar/ocultar series.

Conversión de Unidades: Toda la aplicación permite al usuario cambiar la visualización de temperaturas entre Celsius (°C) y Fahrenheit (°F).

Sistema de Alertas: Lógica de backend que compara automáticamente cada lectura entrante con los parámetros (umbrales) definidos por el usuario.

Notificaciones Visuales: Las tarjetas del dashboard cambian de color a rojo si un cuarto está en estado de alerta.

Gestión de Parámetros: Una vista dedicada para configurar los rangos de operación (temp/hum mín. y máx.) para cada cuarto individualmente.

Reportes Detallados: Un módulo para generar reportes históricos por rango de fechas y por cuarto, incluyendo:

Gráfica de promedios diarios.

Tabla de datos completa con paginación avanzada.

Exportación de datos a PDF, CSV y Excel.

Arquitectura Optimizada: El ESP32 envía datos por dos canales: MQTT (para actualizaciones rápidas del dashboard) y HTTP (para guardado seguro en la base de datos).

Diseño Responsivo: La interfaz se adapta a dispositivos móviles, tablets y escritorio.

Arquitectura y Stack Tecnológico
El sistema sigue una arquitectura desacoplada que asegura eficiencia y escalabilidad. El flujo de datos principal es el siguiente:

[Imagen del flujo de datos del sistema]

Hardware (IoT): ESP32

Backend (API): PHP 8+ (API RESTful sin frameworks)

Frontend: Vue 3 (compilado con Vite)

Base de Datos: MySQL

Comunicación en Tiempo Real: MQTT (Broker)

Librerías Frontend: ApexCharts (gráficas), jsPDF (reportes).

Instalación y Puesta en Marcha
Para poner en funcionamiento el sistema, se deben configurar los tres componentes principales:

1. Backend (Servidor API)
Sube los contenidos de la carpeta api/ y src/ a tu servidor web (ej. /home/user/public_html/).

Apunta tu servidor web a la carpeta api/ (esta será la raíz pública de la API).

Base de Datos: Importa tu script .sql para crear las tablas (cuarto, sensor, lectura, parametro_cuarto, alerta).

Configuración: Renombra /src/config/db.php.example a db.php y añade tus credenciales de la base de datos.

Asegúrate de que el servidor tenga permisos de escritura para los logs, si es necesario.

2. Frontend (Aplicación Vue)
Navega a la carpeta del frontend (ej. front/).

Instala las dependencias: npm install.

Crea un archivo .env en la raíz de front/ con las siguientes variables (basado en lo que configuramos):

Fragmento de código

# La URL pública de tu API (sin la barra al final)
VITE_API_BASE_URL=https://drover.detpon.com/api

# La URL de tu broker MQTT (usa wss:// para WebSocket seguro)
VITE_MQTT_URL=wss://tu-broker-mqtt.com
VITE_MQTT_USERNAME=tu_usuario_mqtt
VITE_MQTT_PASSWORD=tu_clave_mqtt
Para desarrollo: Inicia el servidor local: npm run dev.

Para producción: Compila los archivos estáticos: npm run build. Sube el contenido de la carpeta dist/ resultante a tu servidor web (ej. al directorio raíz del dominio).

3. Hardware (ESP32)
Abre el proyecto en tu IDE preferido (ej. Arduino IDE o PlatformIO).

Configura las credenciales de WiFi.

Configura las credenciales del servidor MQTT (debe coincidir con el .env del frontend).

Configura el endpoint de la API para los envíos por HTTP (ej. https://drover.detpon.com/api/lecturas).

Flashea el firmware al dispositivo ESP32.

Estructura del Proyecto (Simplificada)
/
├── api/                # Raíz pública del backend (contiene index.php)
├── src/                # Lógica del backend (PHP)
│   ├── config/
│   │   └── db.php      # Configuración de la base de datos
│   ├── controllers/
│   │   ├── TemperatureController.php
│   │   ├── ParameterController.php
│   │   └── AlertController.php
│   └── models/
│       ├── Temperature.php
│       ├── Parameter.php
│       └── Alerta.php
│
├── front/              # Código fuente del frontend (Vue.js)
│   ├── public/
│   ├── src/
│   │   ├── assets/     # CSS e imágenes
│   │   ├── components/ # Componentes reutilizables (Modal, ExportButtons, etc.)
│   │   ├── views/      # Vistas principales (Dashboard, Reports, Parameters)
│   │   ├── router/     # Definición de rutas (index.js)
│   │   └── App.vue     # Layout principal con el menú
│   └── index.html
│
└── (Archivos del ESP32)
Endpoints de la API (Resumen)
GET /cuartos: Obtiene la lista de todos los cuartos.

GET /lecturas: Obtiene un historial de lecturas con filtros (fechas, cuarto, etc.).

POST /lecturas: Recibe una nueva lectura (usado por el ESP32).

GET /ultimas: Obtiene la última lectura de cada cuarto (usado por el dashboard).

GET /lecturas/grafica: Obtiene datos formateados para la gráfica principal.

GET /lecturas/promedios: Obtiene promedios diarios para los reportes.

GET /parametros: Obtiene todos los parámetros de configuración.

POST /parametros/{id}: Actualiza los parámetros de un cuarto específico.

GET /alertas/activas: Obtiene todas las alertas con estado ABIERTA.

Licencia
Este proyecto está bajo la Licencia MIT.