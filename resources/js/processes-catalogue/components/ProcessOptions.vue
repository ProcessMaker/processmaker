<template>
  <div>
    <buttons-start :process="process" />
    <process-counter />
    <!--
      TODO WIZARD SECTION AS COMPONENT IF IS POSSIBLE
    -->
    <chart-save-search />
  </div>
</template>

<script>
import ButtonsStart from "./optionsMenu/ButtonsStart.vue";
import ProcessCounter from "./optionsMenu/ProcessCounter.vue";
import ChartSaveSearch from "./optionsMenu/ChartSaveSearch.vue";

export default {
  components: { ButtonsStart, ProcessCounter, ChartSaveSearch },
  props: ["process"],
  methods: { },
};
</script>

<style scoped>
</style>
