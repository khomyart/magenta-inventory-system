<template>
  <div class="revenue-expense-chart">
    <LineChart :data="chartData" :options="chartOptions" />
  </div>
</template>

<script>
import { computed } from "vue";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from "chart.js";
import { Line as LineChart } from "vue-chartjs";

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

export default {
  name: "RevenueExpenseChart",
  components: { LineChart },
  props: {
    dailyData: {
      type: Object,
      required: true,
    },
  },
  setup(props) {
    const chartData = computed(() => {
      if (!props.dailyData || !props.dailyData.dates) {
        return {
          labels: [],
          datasets: [],
        };
      }

      return {
        labels: props.dailyData.dates,
        datasets: [
          {
            label: "Виручка",
            data: props.dailyData.revenue || [],
            borderColor: "#4caf50",
            backgroundColor: "rgba(76, 175, 80, 0.1)",
            fill: true,
            tension: 0.4,
          },
          {
            label: "Витрати",
            data: props.dailyData.expenses || [],
            borderColor: "#f44336",
            backgroundColor: "rgba(244, 67, 54, 0.1)",
            fill: true,
            tension: 0.4,
          },
          {
            label: "Прибуток",
            data: props.dailyData.profit || [],
            borderColor: "#2196f3",
            backgroundColor: "rgba(33, 150, 243, 0.1)",
            fill: true,
            tension: 0.4,
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
          callbacks: {
            label: function (context) {
              let label = context.dataset.label || "";
              if (label) {
                label += ": ";
              }
              if (context.parsed.y !== null) {
                label += new Intl.NumberFormat("uk-UA", {
                  style: "currency",
                  currency: "UAH",
                }).format(context.parsed.y);
              }
              return label;
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return new Intl.NumberFormat("uk-UA", {
                style: "currency",
                currency: "UAH",
                minimumFractionDigits: 0,
              }).format(value);
            },
          },
        },
      },
      interaction: {
        mode: "nearest",
        axis: "x",
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
.revenue-expense-chart {
  height: 400px;
}
</style>
