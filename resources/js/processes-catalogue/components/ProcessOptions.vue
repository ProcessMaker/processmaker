<template>
  <div class="tw-flex tw-flex-col tw-gap-4">
    <div class="tw-flex tw-flex-col tw-gap-4 tw-w-full">
      <process-description :process="process" />
      <process-counter :process="process" />
    </div>
    <progress-bar-section :stages-summary="process.stagesSummary" />
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
import ProgressBarSection from "./progressBar/ProgressBarSection.vue";

export default {
  components: {
    ProcessCounter, ChartSaveSearch, ProcessDescription, ProgressBarSection,
  },
  props: {
    process: {
      type: Object,
      required: true,
    },
  },
};
</script>
