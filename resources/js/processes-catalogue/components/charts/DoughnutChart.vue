<script>
import { Doughnut } from "vue-chartjs"

export default {
  extends: Doughnut,
  props: ["data", "options", "preview"],
  computed: {
    chartData: function() {
      return this.data;
    },
    previewData: function() {
      return {
        datasets: [{
          data: [ 67, 33 ],
          borderWidth: 0,
        }],
        labels: [ 1, 2 ]
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
      return this.$t("Doughnut Chart");
    },
  },
  watch: {
    data: function() {
      this.render();
    }
  }
}
</script>
