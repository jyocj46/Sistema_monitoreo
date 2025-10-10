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
import autoTable from 'jspdf-autotable';
import '../assets/ExportButtons.css';


const props = defineProps({
  data: { type: Array, required: true },
  filename: { type: String, default: 'reporte' },
});


const headers = Object.keys(props.data[0] || {});
const rows = props.data.map(item => headers.map(header => item[header]));

const copyData = () => {
  const { headers, rows } = prepareDataForExport();
  if (rows.length === 0) return;
  const textData = [headers.join('\t'), ...rows.map(row => row.join('\t'))].join('\n');
  navigator.clipboard.writeText(textData).then(() => alert('¡Datos copiados!'));
};


const downloadFile = (content, mimeType, filename) => {
  const blob = new Blob([content], { type: mimeType });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename;
  link.click();
  URL.revokeObjectURL(link.href);
};


const exportCSV = () => {
  const { headers, rows } = prepareDataForExport();
  if (rows.length === 0) return;
  const csvContent = [headers.join(','), ...rows.map(row => row.join(','))].join('\n');
  downloadFile(csvContent, 'text/csv;charset=utf-8;', `${props.filename}.csv`);
};


const exportExcel = () => {
  const { headers, rows } = prepareDataForExport();
  if (rows.length === 0) return;
  const excelContent = [headers.join('\t'), ...rows.map(row => row.join('\t'))].join('\n');
  downloadFile(excelContent, 'application/vnd.ms-excel;charset=utf-8;', `${props.filename}.xls`);
};


const exportPDF = () => {
  const { headers, rows } = prepareDataForExport();
  if (rows.length === 0) return;

  try {
    const doc = new jsPDF();
    autoTable(doc, {
      head: [headers],
      body: rows.map(r => r.map(c => (c != null ? String(c) : ''))), 
    });
    doc.save(`${props.filename}.pdf`);
  } catch (e) {
    console.error("Error al generar el PDF:", e);
    alert("No se pudo generar el PDF. Revisa la consola.");
  }
};

const prepareDataForExport = () => {
  if (!props.data || props.data.length === 0) {
    alert("No hay datos para exportar.");
    return { headers: [], rows: [] };
  }
  const headers = Object.keys(props.data[0]);
  const rows = props.data.map(item => headers.map(header => item[header]));
  return { headers, rows };
};

const printData = () => {
  const { headers, rows } = prepareDataForExport();
  if (rows.length === 0) return;
  
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
