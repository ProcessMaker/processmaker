<template>
  <div>
    <div class="d-flex justify-content-between align-items-center">
      <!-- Title -->
      <span class="title">
        {{ $t('Analytics') }}
      </span>

      <!-- View More Link -->
      <a
        v-if="isProcessIntelligenceEnabled"
        :href="viewMoreUrl"
        class="btn btn-link title-case"
      >
        <span>
          {{ $t('View More') }}
        </span>
        <i class="fas fa-external-link-alt" />
      </a>
    </div>
    <BaseChart
      ref="baseChart"
      :process="process"
    />
  </div>
</template>

<script>
import BaseChart from "../BaseChart.vue";

export default {
  components: {
    BaseChart,
  },
  props: {
    process: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      chart: true,
      selectedSavedChart: "",
      selectedSavedChartId: "",
      isProcessIntelligenceEnabled: window.ProcessMaker.isProcessIntelligenceEnabled,
    };
  },
  computed: {
    viewMoreUrl() {
      const processId = encodeURIComponent(this.process.name);
      return `/analytics/process-intelligence?process_id=${processId}`;
    },
  },
};
</script>

<style scoped>
.title {
  color: #1572C2;
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

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
  top: 5%;
  left: 5%;
  object-fit: cover;
}

.title-case {
  text-transform: capitalize;
}
</style>
