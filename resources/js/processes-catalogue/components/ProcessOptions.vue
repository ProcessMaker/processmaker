<template>
  <div class="tw-flex tw-flex-col tw-gap-4">
    <div class="tw-flex tw-flex-col tw-gap-4 tw-w-full">
      <process-description :process="process" />
      <process-counter
        v-if="collapsed"
        :process="process"
      />
    </div>
    <chart-save-search
      class="tw-w-full"
      :process="process"
    />
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
