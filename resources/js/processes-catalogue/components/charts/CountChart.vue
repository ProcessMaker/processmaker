<template>
  <div
    v-if="rendered"
    class="count-chart w-100 h-100"
    :class="{ animated: animated }"
  >
    <div
      v-if="preview"
      class="count-chart-preview d-flex align-items-center justify-content-center w-100 h-100 rounded-sm text-center"
    >
      <p class="count-chart-text count-chart-metric m-0 font-weight-bold">
        {{ chartData.datasets[0].data[0] }}
      </p>
    </div>
    <b-card
      v-else
      header-class="count-chart-header d-flex align-items-center justify-content-center border-0"
      :text-variant="textVariant"
      class="h-100 w-100 d-flex flex-row card-border rounded-lg border-0"
      body-class="d-flex align-items-center"
      :style="{ backgroundColor: backgroundColor }"
      tabindex="0"
      role="figure"
      :aria-label="describe()"
    >
      <i
        slot="header"
        class="count-chart-icon fas fa-fw fa-4x"
        :class="iconClass"
      ></i>
      <div
        class="count-chart-text"
        :class="`text-${textVariant}`"
      >
        <p class="count-chart-metric m-0 font-weight-bold">
          {{ chartData.datasets[0].data[0] }}
        </p>
        <p class="count-chart-label mx-0 mt-1 mb-0">
          {{ chartData.datasets[0].label }}
        </p>
      </div>
    </b-card>
  </div>
</template>

<script>
export default {
  props: ["data", "options", "preview"],
  data: function () {
    return {
      rendered: false,
      animated: false,
      chartData: null,
      chartOptions: null,
    };
  },
  computed: {
    previewData: function () {
      return {
        datasets: [
          {
            data: [42],
            label: "Preview",
            icon: "chart-line",
          },
        ],
      };
    },
    previewOptions: function () {
      return {};
    },
    iconClass: function () {
      let icon = this.chartData.datasets[0].icon;
      return `fa-${icon}`;
    },
    backgroundColor: function () {
      if (this.chartData.datasets[0].backgroundColor[0] === "#fff") {
        return "#eaeaea";
      }

      return this.chartData.datasets[0].backgroundColor[0];
    },
    textVariant: function () {
      if (this.chartData.datasets[0].backgroundColor[0] === "#fff") {
        return "muted";
      }

      return "white";
    },
  },
  mounted() {
    this.render();
  },
  methods: {
    render() {
      if (!this.preview) {
        this.renderChart(this.data, this.options);
      } else {
        this.renderChart(this.previewData, this.previewOptions);
      }
      this.$emit("render");
    },
    describe() {
      return this.$t("Count Chart");
    },
    renderChart(data, options) {
      this.rendered = false;
      this.animated = this.preview;
      this.$nextTick(() => {
        this.chartData = data;
        this.chartOptions = options;
        this.rendered = true;
        setTimeout(() => {
          this.animated = true;
        }, 1);
      });
    },
  },
  watch: {
    data: function () {
      this.render();
    },
  },
};
</script>

<style lang="scss" scoped>
$animationLength: 500ms;

.count-chart {
  .count-chart-header {
    padding: 0;
    width: 120px;
  }

  .count-chart-icon {
    opacity: 0;
    transform: scale(1.75);
    transition: all $animationLength ease-in;
  }

  &[max-width~="220px"] {
    .count-chart-header {
      display: none !important;
    }
  }

  .count-chart-text {
    cursor: default;
    line-height: 1;
    opacity: 0;
    transform: translate(30px, 0);
    transition: all $animationLength ease-in;
  }

  .count-chart-metric {
    font-size: 3rem;
  }

  .count-chart-label {
    font-size: 1rem;
  }

  .count-chart-preview {
    background: #1f78b4;
    color: #a6cee3;
    font-weight: bold;
    .count-chart-metric {
      font-size: 1rem;
    }
  }

  &.animated {
    .count-chart-icon {
      opacity: 1;
      transform: scale(1);
    }

    .count-chart-text {
      opacity: 1;
      transform: translate(0, 0);
    }
  }
}
</style>
