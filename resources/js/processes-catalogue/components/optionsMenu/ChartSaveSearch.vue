<template>
  <div>
    <div class="title">
      <span>
        {{ $t('Analytics') }}
      </span>
      <button
        class="btn btn-link button-view"
        @click="openAnalytics"
      >
        <span>
          {{ $t('View More') }}
        </span>
        <i class="fas fa-external-link-alt" />
      </button>
    </div>
    <base-chart
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
  props: ["process"],
  data() {
    return {
      chart: true,
      selectedSavedChart: "",
      selectedSavedChartId: "",
    };
  },
  methods: {
    openAnalytics() {
      window.open('../analytics/process-intelligence', '_blank').focus();
    },
  },
};
</script>

<style scoped>
.title {
  display: flex;
  justify-content: space-between;
  align-content: center;
  color: #1572C2;
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.02em;
  font-family: Open Sans, 'sans-serif';
  line-height: 27px;
  letter-spacing: -0.02em;
}

.button-view {
  color: #1572C2;
  text-transform: capitalize;
  font-family: Open Sans, 'sans-serif';
  font-size: 16px;
  font-weight: 400;
  line-height: 24px;
  letter-spacing: -0.02em;
  padding: 0px;
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

@media (width < 1360px) and (width > 768px) {
  .title {
    margin-left: 32px;
  }
}
@media (min-width: 641px) and (max-width: 768px) {
  .default-chart {
    margin-right: 32px;
    margin-top: 24px;
  }
}
</style>
