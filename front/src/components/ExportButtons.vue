// src/components/ExportButtons.vue
<template>
  <div class="export-buttons">
    <button type="button" @click="copyData">Copiar</button>
    <button type="button" @click="exportCSV">CSV</button>
    <button type="button" @click="exportExcel">Excel</button>
    <button type="button" @click="exportPDF">PDF</button>
    <button type="button" @click="printData">Imprimir</button>
  </div>
</template>

<script setup>
import { jsPDF } from 'jspdf';
import 'jspdf-autotable';
import '../assets/ExportButtons.css';

// Props: el componente recibe los datos y el nombre del archivo
const props = defineProps({
  data: {
    type: Array,
    required: true,
  },
  filename: {
    type: String,
    default: 'reporte',
  },
});

// Extrae las cabeceras y las filas de los datos
const headers = Object.keys(props.data[0] || {});
const rows = props.data.map(item => headers.map(header => item[header]));

// --- Funciones de Exportación ---

// 1. Copiar al Portapapeles
const copyData = () => {
  const textData = [
    headers.join('\t'),
    ...rows.map(row => row.join('\t'))
  ].join('\n');
  navigator.clipboard.writeText(textData).then(() => {
    alert('¡Datos copiados al portapapeles!');
  });
};

// Función genérica para descargar archivos
const downloadFile = (content, mimeType, filename) => {
  const blob = new Blob([content], { type: mimeType });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename;
  link.click();
  URL.revokeObjectURL(link.href);
};

// 2. Exportar a CSV
const exportCSV = () => {
  const csvContent = [
    headers.join(','),
    ...rows.map(row => row.join(','))
  ].join('\n');
  downloadFile(csvContent, 'text/csv;charset=utf-8;', `${props.filename}.csv`);
};

// 3. Exportar a Excel (realmente un CSV con extensión .xls)
const exportExcel = () => {
  const excelContent = [
    headers.join('\t'), // Usar tabulación para mejor compatibilidad con Excel
    ...rows.map(row => row.join('\t'))
  ].join('\n');
  downloadFile(excelContent, 'application/vnd.ms-excel;charset=utf-8;', `${props.filename}.xls`);
};

// 4. Exportar a PDF
const exportPDF = () => {
  try {
    const doc = new jsPDF();

    // Convertimos todas las celdas a string para evitar errores
    const bodyData = rows.map(row => 
      row.map(cell => (cell !== null && cell !== undefined) ? String(cell) : '')
    );

    doc.autoTable({
      head: [headers],
      body: bodyData,
      styles: {
        font: 'helvetica', // Usamos una fuente más estándar
        fontSize: 8,
      },
    });

    doc.save(`${props.filename}.pdf`);
  } catch (error) {
    console.error("Error al generar el PDF:", error);
    alert("No se pudo generar el PDF. Revise la consola para más detalles.");
  }
};

// 5. Imprimir
const printData = () => {
  const printWindow = window.open('', '_blank');
  let tableHtml = `<style>table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ddd;padding:8px;}</style><table><thead><tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>`;
  rows.forEach(row => {
    tableHtml += `<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`;
  });
  tableHtml += '</tbody></table>';
  printWindow.document.write(tableHtml);
  printWindow.document.close();
  printWindow.print();
};
</script>
