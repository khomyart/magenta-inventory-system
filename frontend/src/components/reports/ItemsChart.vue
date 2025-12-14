<template>
  <div class="items-chart">
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
  name: "ItemsChart",
  components: { BarChart },
  props: {
    itemsData: {
      type: Array,
      required: true,
    },
  },
  setup(props) {
    const chartData = computed(() => {
      if (!props.itemsData || props.itemsData.length === 0) {
        return {
          labels: [],
          datasets: [],
        };
      }

      // Create labels with item info
      const labels = props.itemsData.map((item) => {
        let label = item.item_title || item.item_article;
        const details = [];
        if (item.item_type) details.push(item.item_type);
        if (item.item_color) details.push(item.item_color);
        if (item.item_size) details.push(item.item_size);
        if (details.length > 0) {
          label += ` (${details.join(", ")})`;
        }
        return label;
      });

      const ordersCount = props.itemsData.map((item) => item.orders_count);
      const totalQuantity = props.itemsData.map((item) => item.total_quantity);

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
            font: {
              size: 10,
            },
          },
        },
        y: {
          type: "linear",
          display: true,
          position: "left",
          beginAtZero: true,
          grace: "5%",
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
.items-chart {
  height: 400px;
}
</style>
