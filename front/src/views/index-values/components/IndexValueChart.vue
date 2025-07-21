<template>
  <v-card class="mb-4">
    <v-card-title class="text-h6 d-flex align-center">
      <span>Gráfico de Valores de Índice</span>
      <v-spacer />
      <v-chip size="small" color="info" variant="flat" class="me-2">
        Últimos 12 meses
      </v-chip>
      <v-select
        v-model="selectedIndexType"
        :items="indexTypeOptions"
        item-title="name"
        item-value="id"
        label="Tipo de Índice"
        style="max-width: 200px"
        @update:model-value="fetchChartData"
      />
    </v-card-title>
    <v-card-text>
      <div v-if="loading" class="text-center pa-8">
        <v-progress-circular indeterminate color="primary" />
        <div class="mt-2">Cargando datos...</div>
      </div>

      <div v-else-if="chartData.length === 0" class="text-center pa-8">
        <v-icon size="48" color="grey">mdi-chart-line</v-icon>
        <div class="mt-2 text-medium-emphasis">No hay datos para mostrar</div>
      </div>

      <canvas v-else ref="chartCanvas" height="300"></canvas>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import indexValueService from '@/services/indexValueService'
import Chart from 'chart.js/auto'

const props = defineProps({
  indexTypeId: {
    type: Number,
    default: null
  }
})

const chartCanvas = ref(null)
const chart = ref(null)
const loading = ref(false)
const chartData = ref([])
const indexTypeOptions = ref([])
const selectedIndexType = ref(null)

// Función helper para calcular la fecha de hace 12 meses
const getTwelveMonthsAgo = () => {
  const date = new Date()
  date.setMonth(date.getMonth() - 12)
  return date.toISOString().split('T')[0]
}

const fetchIndexTypes = async () => {
  try {
    const { data } = await indexValueService.getIndexTypes()
    indexTypeOptions.value = data

    // Seleccionar el primer tipo por defecto
    if (data.length > 0 && !selectedIndexType.value) {
      selectedIndexType.value = data[0].id
    }
  } catch (e) {
    console.error('Error cargando tipos de índice:', e)
  }
}

const fetchChartData = async () => {
  if (!selectedIndexType.value) return

  loading.value = true
  try {
    // Calcular la fecha de hace 12 meses
    const twelveMonthsAgoStr = getTwelveMonthsAgo()

    const { data } = await indexValueService.getIndexValues({
      index_type_id: selectedIndexType.value,
      effective_date_from: twelveMonthsAgoStr,
      per_page: 1000
    })
    chartData.value = data

    // Ordenar datos por effective_date
    chartData.value.sort((a, b) => {
      return new Date(a.effective_date) - new Date(b.effective_date)
    })

    updateChart()
  } catch (e) {
    console.error('Error cargando datos del gráfico:', e)
  } finally {
    loading.value = false
  }
}

const updateChart = () => {
  if (!chartCanvas.value || chartData.value.length === 0) return

  // Destruir gráfico anterior si existe
  if (chart.value) {
    chart.value.destroy()
  }

  const ctx = chartCanvas.value.getContext('2d')

  const labels = chartData.value.map(item => {
    return new Date(item.effective_date).toLocaleDateString('es-ES', {
      month: 'short',
      year: 'numeric'
    })
  })

  const values = chartData.value.map(item => {
    return item.value
  })

  const selectedType = indexTypeOptions.value.find(t => t.id === selectedIndexType.value)
  const isPercentage = selectedType?.calculation_mode === 'percentage'

  chart.value = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: isPercentage ? 'Porcentaje (%)' : 'Valor',
        data: values,
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        tension: 0.1,
        pointBackgroundColor: 'rgb(75, 192, 192)',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        title: {
          display: true,
          text: `${selectedType?.name || 'Índice'} - Evolución Temporal (Últimos 12 meses)`
        },
        legend: {
          display: true,
          position: 'top'
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return isPercentage ? context.parsed.y + '%' : context.parsed.y
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: isPercentage ? 'Porcentaje (%)' : 'Valor'
          }
        },
        x: {
          title: {
            display: true,
            text: 'Tiempo'
          }
        }
      },
      interaction: {
        intersect: false,
        mode: 'index'
      }
    }
  })
}

// Observar cambios en el tipo de índice seleccionado
watch(() => props.indexTypeId, (newValue) => {
  if (newValue) {
    selectedIndexType.value = newValue
  }
}, { immediate: true })

// Observar cambios en el tipo seleccionado
watch(selectedIndexType, () => {
  if (selectedIndexType.value) {
    fetchChartData()
  }
})

onMounted(() => {
  fetchIndexTypes()

  // Escuchar evento para refrescar el gráfico
  window.addEventListener('refresh-index-values', fetchChartData)
})

onUnmounted(() => {
  if (chart.value) {
    chart.value.destroy()
  }

  // Limpiar event listener
  window.removeEventListener('refresh-index-values', fetchChartData)
})
</script>
