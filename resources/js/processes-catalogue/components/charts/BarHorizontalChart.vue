<template>
  <Bar
    :data="displayData"
    :options="displayOptions"
    :height="height"
    :width="width"
  />
</template>

<script>
import { Bar } from "vue-chartjs";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from "chart.js";

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

export default {
  name: "BarHorizontalChart",
  components: { Bar },
  props: {
    data: {
      type: Object,
      default: null,
    },
    options: {
      type: Object,
      default: null,
    },
    preview: {
      type: Boolean,
      default: false,
    },
    height: {
      type: Number,
      default: undefined,
    },
    width: {
      type: Number,
      default: undefined,
    },
  },
  computed: {
    displayData() {
      if (this.preview) {
        return this.previewData;
      }
      return this.data || { labels: [], datasets: [] };
    },
    displayOptions() {
      const base = this.preview ? this.previewOptions : (this.options || {});
      return {
        indexAxis: "y",
        ...base,
      };
    },
    previewData() {
      return {
        datasets: [{
          data: [5, 10, 15],
        }],
        labels: [1, 2, 3],
      };
    },
    previewOptions() {
      return {
        layout: {
          padding: {
            top: 1,
            right: 2,
            bottom: 1,
            left: 2,
          },
        },
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            enabled: false,
          },
        },
        maintainAspectRatio: true,
        responsive: true,
        scales: {
          x: {
            display: false,
            max: 15,
          },
          y: {
            display: false,
          },
        },
      };
    },
  },
  watch: {
    data: {
      deep: true,
      handler() {
        this.$nextTick(() => this.$emit("render"));
      },
    },
  },
  mounted() {
    this.$nextTick(() => this.$emit("render"));
  },
  methods: {
    describe() {
      return this.$t("Horizontal Bar Graph");
    },
  },
};
</script>
