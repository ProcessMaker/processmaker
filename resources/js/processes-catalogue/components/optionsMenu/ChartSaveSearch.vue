<template>
  <div>
    <span class="title">
      {{ $t('Analytics') }}
    </span>
    <base-chart ref="baseChart" :process="process"></base-chart>
  </div>
</template>

<script>
import BaseChart from "../BaseChart.vue";
export default {
  props: ["process"],
  components: {
    BaseChart,
  },
  data() {
    return {
      chart: true,
      selectedSavedChart: "",
      selectedSavedChartId: "",
    };
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
</style>
