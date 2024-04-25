<template>
  <div class="section-options">
    <process-counter :process="process" />
    <chart-save-search :process="process" />
  </div>
</template>

<script>
import ProcessCounter from "./optionsMenu/ProcessCounter.vue";
import ChartSaveSearch from "./optionsMenu/ChartSaveSearch.vue";

export default {
  components: { ProcessCounter, ChartSaveSearch },
  props: ["process"],
};
</script>

<style scoped>
.section-options {
  display: flex;
  flex-direction: column;
  align-items: center;
}
@media (width < 1460px) {
  .section-options {
    align-items: flex-start;
    flex-direction: row;
  }
}
</style>
