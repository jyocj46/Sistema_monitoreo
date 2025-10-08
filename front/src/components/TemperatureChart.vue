// src/components/TemperatureChart.vue
<template>
  <div class="chart-container">
    <Line v-if="chartData" :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup>
import { Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';

// Registramos los componentes necesarios de Chart.js
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
);

// El componente recibe los datos de la gráfica como una propiedad (prop)
defineProps({
  chartData: {
    type: Object,
    required: true,
  },
});

// Opciones de configuración para el estilo de la gráfica
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: {
        color: 'white', // Color del texto de la leyenda
      },
    },
    title: {
      display: true,
      text: 'Promedio de Temperaturas Diarias (°C)',
      color: 'white', // Color del título
    },
  },
  scales: {
    x: {
      ticks: {
        color: 'rgba(255, 255, 255, 0.7)', // Color del texto del eje X
      },
      grid: {
        color: 'rgba(255, 255, 255, 0.2)', // Color de las líneas de la cuadrícula X
      },
    },
    y: {
      ticks: {
        color: 'rgba(255, 255, 255, 0.7)', // Color del texto del eje Y
      },
      grid: {
        color: 'rgba(255, 255, 255, 0.2)', // Color de las líneas de la cuadrícula Y
      },
    },
  },
};
</script>

<style scoped>
.chart-container {
  position: relative;
  height: 400px;
  background-color: #1e2a1f; /* Verde oscuro/gris de tu tema */
  padding: 20px;
  border-radius: 14px;
  box-shadow: 0 10px 18px rgba(16, 24, 40, 0.08);
  margin-bottom: 22px;
}
</style>