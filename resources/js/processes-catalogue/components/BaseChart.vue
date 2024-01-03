<template>
  <div class="base-chart h-100 w-100">
      <template v-if="hasData">
        <component ref="chart" :is="chartComponent" :saved-search-type="chart.saved_search.type" :data="chartData" :config="chartConfig" :options="chartOptions" :additionalPmql="additionalPmql" :height="height" :width="width" :styles="styles"></component>
        <!-- <fallback-chart v-if="displayFallback" ref="fallback" :data="chartData" class="sr-only"></fallback-chart> -->
      </template>
      <div v-else class="chart-error d-flex align-items-center justify-content-center text-muted text-center h-100 w-100">
        <div>
          <h3 class="display-6">{{ $t('Unable to Display Chart') }}</h3>
          <p class="lead" v-if="error">{{ error }}</p>
          <p class="lead" v-else>{{ $t('Either there is no available data or the chart is misconfigured.') }}</p>
          <p class="pt-1" v-if="hint"><i class="fas fa-info-circle"></i> {{ hint }}</p>
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
//import ListChart from "./charts/ListChart.vue";
// import FallbackChart from "./FallbackChart.vue";

export default {
  mixins: [ ChartDataMixin ],
  components: {
    BarHorizontalChart,
    BarVerticalChart,
    DoughnutChart,
    LineChart,
    PieChart,
    CountChart,
    // ListChart,
    // FallbackChart,
  },
  props: {
    value: {
      type: Object
    },
    additionalPmql: {
      type: String,
      default: null
    },
    responsive: {
      type: Boolean,
      default: true
    },
    height: {
      type: Number,
      default: 300
    },
    width: {
      type: Number,
      default: 260
    }
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
        position: 'relative',
      },
      options: {
        responsive: this.responsive,
        maintainAspectRatio: false,
        legend: {
          display: true,
        }
      },
      hasData: false,
    };
  },
  watch: {
    value: {
      handler: function(value) {
        this.transform(value);
      },
      deep: true
    },
    chart: {
      handler: function(value) {
        this.$emit('input', value);
      },
      deep: true
    }
  },
  computed: {
    displayFallback() {
      if (! ['list', 'count'].includes(this.chart.type)) {
        return true;
      } else {
        return false;
      }
    },
    chartComponent() {
      console.log("imprime chart: ", this.chart);
      if (!this.chart) {
        return null;
      }

      switch (this.chart.type) {
        case 'list':
          return 'list-chart';
        case 'count':
          return 'count-chart';
        case 'doughnut':
          return 'doughnut-chart';
        case 'pie':
          return 'pie-chart';
        case 'line':
          return 'line-chart';
        case 'bar-vertical':
          return 'bar-vertical-chart';
        case 'bar':
        default:
          return 'bar-horizontal-chart';
      }
    }
  },
  beforeMount() {
    this.setDefaults();
    //this.transform(this.value);
    this.fetchAll();
  },
  mounted() {
    console.log("En BaseChart");
    let $chart = _.get(this.$refs, 'chart.$el');
    if ($chart instanceof Element) {
      let $canvas = $chart.querySelector('canvas');
      let $fallback = this.$refs.fallback.$el;
      if ($canvas && $canvas instanceof Element && $fallback && $fallback instanceof Element) {
        $canvas.setAttribute('role', 'figure');
        $canvas.setAttribute('tabindex', 0);
        $canvas.ariaLabel = this.$refs.chart.describe();
        $canvas.appendChild($fallback);
      }
    }
  },
  methods: {
    fetchAll() {
        // ProcessMaker.apiClient.get(`/saved-searches/${this.savedSearchId}/charts?`, {timeout: 0}).then(response => {
        // ProcessMaker.apiClient.get(`/saved-searches/15/charts?`, {timeout: 0}).then(response => {
          // ProcessMaker.apiClient.get(`saved-searches/charts/${saved_search_chart}`, {timeout: 0}).then(response => {
            ProcessMaker.apiClient.get(`saved-searches/charts/2`, {timeout: 0}).then(response => {
          // this.charts = response.data.data;
          this.charts = response.data;
            console.log("FETCH ALL BASE CHART ", response.data);
          this.transform(this.charts);
        });
  
    },
    refresh() {
      this.transform(this.chart);
    },
    setDefaults() {
      Chart.defaults.global.defaultFontFamily = "'Open Sans'";
      Chart.defaults.global.defaultFontSize = 12;
      Chart.defaults.global.defaultFontStyle = 'bold';
      Chart.defaults.global.layout.padding = 1;

      Chart.scaleService.updateScaleDefaults('linear', {
          ticks: {
              min: 0
          }
      });
    },
    setupChartOptions() {
      let options = this.options;
      if (this.chart.config.display) {
        if (this.chart.config.display.legend) {
          options.legend.display = true;
          options.legend.position = this.chart.config.display.legend;
        } else {
          options.legend.display = false;
        }
        
        if (['bar', 'bar-vertical', 'line'].includes(this.chart.type)) {
          options.scales = {
            xAxes: [{
              stacked: this.chart.config.display.stacked,
            }],
            yAxes: [{
              stacked: this.chart.config.display.stacked,
            }]
          };
        } else {
          options.scales = {
            xAxes: [{
              display: false,
            }],
            yAxes: [{
              display: false,
            }]
          };
        }
      }

      return options;
    },
    transform(data) {
      this.chart = data;
    console.log("en Transform: ", this.chart);
      //if (this.chart.chart_data && this.chart.config) {
        this.chartData = this.transformChartData(this.chart);
        console.log("ChartData: ", this.chartData);
        this.chartConfig = this.chart.config;
        console.log("ChartConfig: ", this.chartConfig);
        this.chartOptions = this.setupChartOptions();
        console.log("chartOptions: ", this.chartOptions);
        this.hasData = true;
        this.error = null;
        this.hint = null;
      // } else {
      //   this.hasData = false;
      //   if (this.chart.chart_error) {
      //     this.error = this.chart.chart_error;
      //     this.hint = this.chart.chart_hint;
      //   }
      // }
    }
  }
}
</script>
