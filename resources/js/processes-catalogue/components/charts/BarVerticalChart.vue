<script>
import { Bar } from "vue-chartjs";

export default {
  extends: Bar,
  props: ["data", "options", "preview"],
  computed: {
    chartData() {
      return this.data;
    },
    previewData() {
      return {
        datasets: [{
          data: [
            5, 10, 15,
          ],
        }],
        labels: [
          1,
          2,
          3,
        ],
      };
    },
    previewOptions() {
      return {
        layout: {
          padding: {
            top: 2,
            right: 1,
            bottom: 2,
            left: 1,
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
            ticks: {
              max: 15,
            },
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
      return this.$t("Vertical Bar Graph");
    },
  },
};
</script>
