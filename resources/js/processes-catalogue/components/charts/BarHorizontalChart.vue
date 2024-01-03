<script>
import { HorizontalBar } from "vue-chartjs"

export default {
  extends: HorizontalBar,
  props: ["data", "options", "preview"],
  computed: {
    chartData: function() {
      return this.data;
    },
    previewData: function() {
      return {
        datasets: [{
          data: [
            5, 10, 15
          ]
        }],
        labels: [
          1,
          2,
          3
        ]
      };
    },
    previewOptions: function() {
      return {
        layout: {
          padding: {
            top: 1,
            right: 2,
            bottom: 1,
            left: 2,
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
            ticks: {
              max: 15,
            }
          }],
          yAxes: [{
            display: false,
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
      if (!this.preview) {
        this.renderChart(this.chartData, this.options);
      } else {
        this.renderChart(this.previewData, this.previewOptions);
      }
      this.$emit("render");
    },
    describe() {
      return this.$t("Horizontal Bar Graph");
    }
  },
  watch: {
    data: function() {
      this.render();
    }
  }
}
</script>
