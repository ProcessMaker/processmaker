<template>
  <div>
    <template v-if="hasData">
      <div class="base-chart h-85 w-85 custom-settings">
        <component
          ref="chart"
          :is="chartComponent"
          :saved-search-type="chart.saved_search.type"
          :data="chartData"
          :config="chartConfig"
          :options="chartOptions"
          :additionalPmql="additionalPmql"
          :height="height"
          :width="width"
          :styles="styles"
        >
        </component>
      </div>
    </template>
    <div v-else>
      <div class="image-container">
        <img
          src="/img/launchpad-images/defaultImage.svg"
          alt="Chart"
          style="width: 90%; height: 90%; object-fit: cover; margin-top: 10px"
        >
      </div>
    </div>
  </div>
</template>

<script>
import ChartDataMixin from "./mixins/ChartData.js";
import BarHorizontalChart from "./charts/BarHorizontalChart.vue";
import BarVerticalChart from "./charts/BarVerticalChart.vue";
import DoughnutChart from "./charts/DoughnutChart.vue";
import LineChart from "./charts/LineChart.vue";
import PieChart from "./charts/PieChart.vue";
import CountChart from "./charts/CountChart.vue";
import ListChart from "./charts/ListChart.vue";

export default {
  mixins: [ChartDataMixin],
  components: {
    BarHorizontalChart,
    BarVerticalChart,
    DoughnutChart,
    LineChart,
    PieChart,
    CountChart,
    ListChart,
  },
  props: {
    value: {
      type: Object,
    },
    additionalPmql: {
      type: String,
      default: null,
    },
    responsive: {
      type: Boolean,
      default: true,
    },
    height: {
      type: Number,
      default: 400,
    },
    width: {
      type: Number,
      default: 260,
    },
    process: {
      type: Object,
    },
  },
  data() {
    return {
      chart: null,
      chartData: null,
      chartConfig: null,
      chartOptions: null,
      error: null,
      hint: null,
      styles: {
        position: "relative",
      },
      options: {
        responsive: this.responsive,
        maintainAspectRatio: false,
        legend: {
          display: true,
        },
      },
      hasData: false,
      chartId: "",
      chartName: "",
    };
  },
  computed: {
    chartComponent() {
      if (!this.chart) {
        return null;
      }

      switch (this.chart.type) {
        case "list":
          return "list-chart";
        case "count":
          return "count-chart";
        case "doughnut":
          return "doughnut-chart";
        case "pie":
          return "pie-chart";
        case "line":
          return "line-chart";
        case "bar-vertical":
          return "bar-vertical-chart";
        case "bar":
        default:
          return "bar-horizontal-chart";
      }
    },
  },
  beforeMount() {
    this.setDefaults();
  },
  watch: {
    value: {
      handler: function (value) {
        this.transform(value);
      },
      deep: true,
    },
    chart: {
      handler: function (value) {
        this.$emit("input", value);
      },
      deep: true,
    },
  },
  mounted() {
    this.getChartSettings();
    ProcessMaker.EventBus.$on("getChartId", () => {
      this.getChartSettings();
    });
  },
  methods: {
    /**
     * This method is validating installation of Package-savedsearch with package-collections
     * Both packages go always together
     */
    fetchChart(idChart) {
      if (!ProcessMaker.packages.includes("package-collections") || !idChart) return;
      ProcessMaker.apiClient
        .get(`saved-searches/charts/${idChart}`, { timeout: 0 })
        .then((response) => {
          this.charts = response.data;
          this.transform(this.charts);
        })
        .catch((error) => {
          console.error("Error", error);
        });
    },
    getChartSettings() {
      ProcessMaker.apiClient
        .get(`processes/${this.process.id}/media`)
        .then((response) => {
          const firstResponse = response.data.data.shift();
          const launchpadProperties = JSON.parse(
            firstResponse?.launchpad_properties
          );

          if (
            launchpadProperties &&
            Object.keys(launchpadProperties).length > 0
          ) {
            this.selectedSavedChart = launchpadProperties.saved_chart_title
              ? launchpadProperties.saved_chart_title
              : "";
            this.selectedSavedChartId = launchpadProperties.saved_chart_id;
          } else {
            this.selectedSavedChart = "";
            this.selectedSavedChartId = 0;
          }
          this.fetchChart(this.selectedSavedChartId);
        })
        .catch((error) => {
          console.error("Error getting chart id", error);
        });
    },
    refresh() {
      this.transform(this.chart);
    },
    setDefaults() {
      Chart.defaults.global.defaultFontFamily = "'Open Sans'";
      Chart.defaults.global.defaultFontSize = 12;
      Chart.defaults.global.defaultFontStyle = "bold";
      Chart.defaults.global.layout.padding = 1;

      Chart.scaleService.updateScaleDefaults("linear", {
        ticks: {
          min: 0,
        },
      });
    },
    setupChartOptions() {
      const { options } = this;
      if (this.chart.config.display) {
        if (this.chart.config.display.legend) {
          options.legend.display = true;
          options.legend.position = this.chart.config.display.legend;
        } else {
          options.legend.display = false;
        }

        if (["bar", "bar-vertical", "line"].includes(this.chart.type)) {
          options.scales = {
            xAxes: [
              {
                stacked: this.chart.config.display.stacked,
              },
            ],
            yAxes: [
              {
                stacked: this.chart.config.display.stacked,
              },
            ],
          };
        } else {
          options.scales = {
            xAxes: [
              {
                display: false,
              },
            ],
            yAxes: [
              {
                display: false,
              },
            ],
          };
        }
      }

      return options;
    },
    transform(data) {
      this.chart = data;
      if (this.chart.chart_data && this.chart.config) {
        this.chartData = this.transformChartData(this.chart);
        this.chartConfig = this.chart.config;
        this.chartOptions = this.setupChartOptions();
        this.hasData = true;
        this.error = null;
        this.hint = null;
      } else {
        this.hasData = false;
        if (this.chart.chart_error) {
          this.error = this.chart.chart_error;
          this.hint = this.chart.chart_hint;
        }
      }
    },
  },
};
</script>
<style scoped>
.image-container {
  width: 100%;
  padding-top: 100%;
  position: relative;
  overflow: hidden;
}

.image-container img {
  position: absolute;
  width: 90%;
  height: 90%;
  top: 0%;
  left: 0%;
  object-fit: cover;
}

.custom-settings {
  margin-top: 10px;
  width: 90%;
  height: 90%;
  background-color: white;
  max-height: 250px;
}
</style>
