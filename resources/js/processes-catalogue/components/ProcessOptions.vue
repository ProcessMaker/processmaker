<template>
  <div class="section-options">
    <div class="left-column">
      <process-description :process="process" />
      <process-counter v-if="collapsed" :process="process" />
    </div>
    <chart-save-search class="section-chart" :process="process" />
  </div>
</template>

<script>
import ProcessCounter from "./optionsMenu/ProcessCounter.vue";
import ChartSaveSearch from "./optionsMenu/ChartSaveSearch.vue";
import ProcessDescription from "./optionsMenu/ProcessDescription.vue";

export default {
  components: { ProcessCounter, ChartSaveSearch, ProcessDescription },
  props: ["process", "collapsed"],
};
</script>

<style scoped>
.section-options {
  display: flex;
  flex-direction: column;
  width: 98.5%;
  margin-top: 20px;
}
.left-column {
  flex-direction: column;
  align-items: flex-start;
}
/*
  When Screen Width is less than 769px Chart section moves below Details
*/
@media (min-width: 769px) {
  .section-options {
    flex-direction: row;
    justify-content: space-between;
  }
}
@media (min-width: 641px) and (max-width: 768px) {
  .section-chart {
    margin-top:40px ;
    margin-left: 15px;
  }
}
</style>
