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
      default: 294,
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
        },
        title: {
          display: true,
          text: this.$t("Case by Status"),
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
    const unparseProperties = this.process.launchpad?.properties || null;
    if (unparseProperties !== null) {
      this.chartId = JSON.parse(unparseProperties)?.saved_chart_id || null;
    }
    this.fetchChart();
    ProcessMaker.EventBus.$on("getChartId", (newChartId) => {
      this.chartId = newChartId;
      this.fetchChart();
    });
  },
  methods: {
    /**
     * This method is validating installation of Package-savedsearch with package-collections
     * Both packages go always together
     */
    fetchChart() {
      if (ProcessMaker.packages.includes("package-collections") && this.chartId !== null && this.chartId !== 0) {
        ProcessMaker.apiClient
          .get(`saved-searches/charts/${this.chartId}`, { timeout: 0 })
          .then((response) => {
            this.charts = response.data;
            this.transform(this.charts);
          })
          .catch((error) => {
            console.error("Error", error);
          });
      } else {
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
          });
      }
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
        this.error = null;
        this.hint = null;
      }
      if (this.chart?.chart_error) {
        this.error = this.chart.chart_error;
        this.hint = this.chart.chart_hint;
      }
    },
  },
};
</script>
<style scoped>
.default-chart {
  width: 294px;
  margin-top: 32px;
  padding: 16px 0;
  border-radius: 16px;
  border: 0.883px solid rgba(205, 221, 238, 0.50);
  background: linear-gradient(0deg, rgba(255, 255, 255, 0.92) 0%, rgba(255, 255, 255, 0.92) 100%), #57D490;
}
.custom-settings {
  margin-top: 32px;
  background-color: white;
}
@media (width < 1200px) {
  .default-chart {
    margin-top: 0;
    margin-left: 32px;
  }
}
</style>
