<template>
  <div class="services-chart">
    <BarChart :data="chartData" :options="chartOptions" />
  </div>
</template>

<script>
import { computed } from "vue";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from "chart.js";
import { Bar as BarChart } from "vue-chartjs";

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
);

export default {
  name: "ServicesChart",
  components: { BarChart },
  props: {
    servicesData: {
      type: Array,
      required: true,
    },
  },
  setup(props) {
    const chartData = computed(() => {
      if (!props.servicesData || props.servicesData.length === 0) {
        return {
          labels: [],
          datasets: [],
        };
      }

      const labels = props.servicesData.map((s) => s.service_title);
      const ordersCount = props.servicesData.map((s) => s.orders_count);
      const totalQuantity = props.servicesData.map((s) => s.total_quantity);

      return {
        labels,
        datasets: [
          {
            label: "Кількість замовлень",
            data: ordersCount,
            backgroundColor: "rgba(33, 150, 243, 0.6)",
            borderColor: "#2196f3",
            borderWidth: 1,
            yAxisID: "y",
          },
          {
            label: "Загальна кількість",
            data: totalQuantity,
            backgroundColor: "rgba(76, 175, 80, 0.6)",
            borderColor: "#4caf50",
            borderWidth: 1,
            yAxisID: "y",
          },
        ],
      };
    });

    const chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: "top",
        },
        tooltip: {
          mode: "index",
          intersect: false,
        },
      },
      scales: {
        x: {
          ticks: {
            autoSkip: false,
            maxRotation: 45,
            minRotation: 45,
          },
        },
        y: {
          type: "linear",
          display: true,
          position: "left",
          beginAtZero: true,
          title: {
            display: true,
            text: "Кількість",
          },
        },
      },
      interaction: {
        mode: "index",
        intersect: false,
      },
    };

    return {
      chartData,
      chartOptions,
    };
  },
};
</script>

<style scoped>
.services-chart {
  height: 400px;
}
</style>
