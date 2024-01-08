<script>
import { Bar } from "vue-chartjs"

export default {
  extends: Bar,
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
          enabled: false
        },
        scales: {
          xAxes: [{
            display: false,
          }],
          yAxes: [{
            display: false,
            ticks: {
              max: 15,
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
      return this.$t("Vertical Bar Graph");
    }
  },
  watch: {
    data: function() {
      this.render();
    }
  }
}
</script>
