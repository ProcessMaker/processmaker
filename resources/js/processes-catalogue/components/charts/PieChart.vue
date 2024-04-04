<script>
import { Pie } from "vue-chartjs";

export default {
  extends: Pie,
  props: ["data", "options", "preview"],
  computed: {
    chartData() {
      return this.data;
    },
    previewData() {
      return {
        datasets: [{
          data: [25, 75],
          borderWidth: 0,
        }],
        labels: [1, 2],
      };
    },
    previewOptions() {
      return {
        layout: {
          padding: {
            top: 2,
            right: 0,
            bottom: 2,
            left: 0,
          },
        },
        legend: {
          display: false,
        },
        maintainAspectRatio: true,
        responsive: true,
        tooltips: {
          enabled: false,
        },
        scales: {
          xAxes: [{
            display: false,
          }],
          yAxes: [{
            display: false,
          }],
        },
      };
    },
  },
  watch: {
    data() {
      this.render();
    },
  },
  mounted() {
    this.render();
  },
  methods: {
    render() {
      if (!this.preview) {
        this.renderChart(this.chartData, this.options);
      } else {
        this.renderChart(this.previewData, this.previewOptions);
      }
      this.$emit("render");
    },
    describe() {
      return this.$t("Pie Chart");
    },
  },
};
</script>
