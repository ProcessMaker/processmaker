<template>
  <div>
    <template v-if="chartId !== null && chartId !== 0 && chart !== null">
      <div class="base-chart custom-settings">
        <component
          :is="chartComponent"
          ref="chart"
          :saved-search-type="chart.saved_search.type"
          :data="chartData"
          :config="chartConfig"
          :options="chartOptions"
          :additional-pmql="additionalPmql"
          :height="height"
          :width="width"
          :styles="styles"
        />
      </div>
    </template>
    <template v-else>
      <div class="default-chart">
        <pie-chart
          :data="defaultData"
          :options="defaultOptions"
          :width="width"
          :height="height"
        />
      </div>
    </template>
  </div>
</template>

<script>
import ChartDataMixin from "./mixins/ChartData";
import BarHorizontalChart from "./charts/BarHorizontalChart.vue";
import BarVerticalChart from "./charts/BarVerticalChart.vue";
import DoughnutChart from "./charts/DoughnutChart.vue";
import LineChart from "./charts/LineChart.vue";
import PieChart from "./charts/PieChart.vue";
import CountChart from "./charts/CountChart.vue";
import ListChart from "./charts/ListChart.vue";

export default {
  components: {
    BarHorizontalChart,
    BarVerticalChart,
    DoughnutChart,
    LineChart,
    PieChart,
    CountChart,
    ListChart,
  },
  mixins: [ChartDataMixin],
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
      default: 274,
    },
    width: {
      type: Number,
      default: 294,
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
      chartId: null,
      chartName: "",
      defaultData: {},
      defaultOptions: {
        responsive: false,
        legend: {
          position: "bottom",
          labels: {
            // This more specific font property overrides the global property
            fontSize: 12,
            fontStyle: "normal",
            fontFamily: "'Open Sans', sans-serif",
            usePointStyle: true,
            padding: 30,
          },
        },
        title: {
          display: true,
          fontSize: 16,
          fontFamily: "'Open Sans', sans-serif",
          text: this.$t("Active vs Closed Cases"),
        },
      },
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
  watch: {
    value: {
      handler(value) {
        this.transform(value);
      },
      deep: true,
    },
    chart: {
      handler(value) {
        this.$emit("input", value);
      },
      deep: true,
    },
  },
  beforeMount() {
    this.setDefaults();
  },
  mounted() {
    ProcessMaker.EventBus.$on("getChartId", (newChartId) => {
      this.chartId = newChartId;
      this.fetchChart();
    });
    this.fetchProcessConfigLaunchpad();
  },
  methods: {
    /**
     * This method is validating installation of Package-savedsearch with package-collections
     * Both packages go always together
     */
    fetchChart() {
      if (!ProcessMaker.packages.includes("package-collections") && this.chartId === null && this.chartId === 0) {
        this.getDefaultData();
        return;
      }
      ProcessMaker.apiClient
        .get(`saved-searches/charts/${this.chartId}`, { timeout: 0 })
        .then((response) => {
          this.transform(response.data);
        })
        .catch(() => {
          this.getDefaultData();
        });
    },
    /**
     * Draw the default chart
     */
    getDefaultData() {
      ProcessMaker.apiClient
        .get(`requests/${this.process.id}/default-chart`)
        .then((response) => {
          const { data } = response.data;
          this.defaultData = {
            labels: data.labels,
            datasets: [
              {
                label: data.datasets.label,
                data: data.datasets.data,
                backgroundColor: Object.values(data.datasets.backgroundColor),
              },
            ],
          };
        })
        .catch((error) => {
          console.error("Error", error);
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
      // Reset chart data
      this.resetChartData();

      if (this.chart.chart_data && this.chart.config) {
        this.chartData = this.transformChartData(this.chart);
        this.chartConfig = this.chart.config;
        this.chartOptions = this.setupChartOptions();
        this.error = null;
        this.hint = null;
      }
      if (this.chart?.chart_error) {
        this.error = this.chart.chart_error;
        this.hint = this.chart.chart_hint;
      }
    },
    resetChartData() {
      this.chartData = null;
      this.chartConfig = null;
      this.chartOptions = null;
      this.error = null;
      this.hint = null;
    },
    fetchProcessConfigLaunchpad() {
      ProcessMaker.apiClient
        .get(`process_launchpad/${this.process.id}`)
        .then((response) => {
          const firstResponse = response.data.shift();
          const unparseProperties = firstResponse?.launchpad?.properties;
          const launchpadProperties = unparseProperties
            ? JSON.parse(unparseProperties)
            : "";
          this.chartId = launchpadProperties.saved_chart_id;
          this.fetchChart();
        })
        .catch((error) => {
          this.getDefaultData();
          console.error("Error: ", error);
        });
    },
  },
};
</script>
<style scoped>
.default-chart {
  width: 325px;
  margin-top: 24px;
  padding: 20px 0;
  border-radius: 16px;
  border: 0.88px solid #CDDDEE;
  background: white;
  display: flex;
  justify-content: center;
  align-items: center;
  box-shadow: 0.88px 0.88px 7.06px 1.77px #4A6F9D1A;
}
.custom-settings {
  margin-top: 32px;
  background-color: white;
}
@media (width < 1360px) and (width > 768px) {
  .default-chart {
    margin-left: 32px;
  }
}
@media (min-width: 641px) and (max-width: 768px) {
  .default-chart {
    width: 100%;
    max-width: 100%;
    margin-right: 32px;
  }
}
</style>
