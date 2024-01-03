<template>
  <div>
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
  methods: {
    getChartSettings() {
      ProcessMaker.apiClient
        .get(`processes/${this.process.id}/media`)
        .then((response) => {
          const firstResponse = response.data.data.shift();
          const launchpadProperties = JSON.parse(
            firstResponse?.launchpad_properties,
          );

          if (launchpadProperties && Object.keys(launchpadProperties).length > 0) {
            this.selectedSavedChart = launchpadProperties.saved_chart_title
              ? launchpadProperties.saved_chart_title
              : "";
            this.selectedSavedChartId = launchpadProperties.saved_chart_id;
            this.chart = false;
          } else {
            this.selectedSavedChart = "";
            this.selectedSavedChartId = 0;
          }
        })
        .catch((error) => {
          console.error("Error getting chart id", error);
        });
    },
  }
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
  top: 5%;
  left: 5%; 
  object-fit: cover; 
}
</style>