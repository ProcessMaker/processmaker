<template>
  <div class="tw-flex tw-flex-col tw-space-y-4 tw-h-full tw-w-full tw-py-4">
    <div class="tw-grid tw-grid-cols-1 tw-gap-4">
      <div class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow">
        <apexchart type="bar" height="350" :options="dataA.chartOptions" :series="dataA.series"></apexchart>
      </div>
    </div>
    <div class="tw-grid tw-grid-cols-4">
      <div class="tw-bg-white tw-col-span-3 tw-p-4 tw-rounded-lg tw-shadow">
        <BaseCardButtonGroup v-if="data.length > 0" :key="dataKey + 'button'" :data="data"/>
      </div>
    </div>

    <div class="tw-grid tw-grid-cols-2">
      <div class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow">
        <apexchart type="bar" height="300" :options="dataB.chartOptions" :series="dataB.series"></apexchart>
      </div>

      <div class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow">
        <apexchart type="bubble" height="300" :options="dataC.chartOptions" :series="dataC.series"></apexchart>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { dataA, dataB, dataC } from "./Apexchart";
import { getMetrics } from "../api";
import { buildMetrics } from "./config/metrics";
import BaseCardButtonGroup from "./ButtonGroup/BaseCardButtonGroup.vue";

const props = defineProps({
  process: {
    type: Object,
    required: true,
  },
});

const data = ref([]);
const dataKey = ref(0);

const hookMetrics = async () => {
  const metricsResponse = await getMetrics({ processId: props.process.id });
  data.value = buildMetrics(metricsResponse.data);
};
onMounted(() => {
  hookMetrics();
});
</script>
