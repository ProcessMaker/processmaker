<script>
import { Line } from "vue-chartjs"

export default {
  extends: Line,
  props: ["data", "options", "preview"],
  computed: {
    chartData: function() {
      return this.data;
    },
    previewData: function() {
      return {
        datasets: [{
          data: [
            5, 7, 4, 15, 10
          ],
          fill: false,
          pointRadius: 0,
          borderWidth: 2,
        }],
        labels: [
          1,
          2,
          3,
          4,
          5,
        ]
      };
    },
    previewOptions: function() {
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
          enabled: false
        },
        scales: {
          xAxes: [{
            display: false,
          }],
          yAxes: [{
            display: false,
            ticks: {
              max: 18,
            }
          }],
        }
      }
    }
  },
  mounted () {
    this.render();
  },
  methods: {
    render() {
      if (! this.preview) {
        this.renderChart(this.chartData, this.options);
      } else {
        this.renderChart(this.previewData, this.previewOptions);
      }
      this.$emit("render");
    },
    describe() {
      return this.$t("Line Graph");
    }
  },
  watch: {
    data: function() {
      this.render();
    }
  }
}
</script>
