<!-- // src/components/TemperatureChart.vue -->
<template>
  <div class="chart-container">
     <Line :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup>
import { Line } from 'vue-chartjs';
import { computed } from 'vue';
import { Chart as ChartJS, Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement);


const props = defineProps({
  chartData: { type: Object, required: true },
  displayUnit: { type: String, default: 'C' }
});

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top', labels: { color: 'white' } },
    title: {
      display: true,
      text: `Promedio de Temperaturas Diarias (°${props.displayUnit})`,
      color: 'white',
      font: { size: 16 }
    }
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
}));
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