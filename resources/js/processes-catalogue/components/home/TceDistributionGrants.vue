<template>
  <div class="tw-flex tw-flex-col tw-space-y-4 tw-h-full tw-w-full">
    <process-collapse-info
      :process="process"
      :ellipsis-permission="ellipsisPermission"
      :my-tasks-columns="myTasksColumns"
      @toggle-info="toggleInfo"
      @goBackCategory="emit('goBackCategory')" />

    <div class="tw-w-full tw-flex tw-flex-row tw-space-x-4">
      <ArrowButtonHome
        class="tw-w-60 tw-bg-emerald-200"
        header="200"
        body="Cases" />

      <ArrowButtonGroup
        v-if="arrowData.length > 0"
        class="tw-flex-grow tw-overflow-auto"
        :data="arrowData"
        color="orange" />

      <ArrowButtonHome
        class="tw-w-60 tw-bg-blue-200"
        header="5"
        body="Completed" />
    </div>
    <CustomHomeTableSection
      class="tw-w-full tw-flex tw-flex-col
      tw-overflow-hidden tw-grow tw-p-4 tw-bg-white tw-rounded-lg tw-shadow-md tw-border tw-border-gray-200"
      :process="process" />

    <ProcessInfo
      :process="process"
      :show-process-info="showProcessInfo"
      :ellipsis-permission="ellipsisPermission"
      @update:showProcessInfo="showProcessInfo = $event" />
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import CustomHomeTableSection from "./CustomHomeTableSection/CustomHomeTableSection.vue";
import ProcessCollapseInfo from "../ProcessCollapseInfo.vue";
import ArrowButtonHome from "./ArrowButtonGroup/ArrowButtonHome.vue";
import ArrowButtonGroup from "./ArrowButtonGroup/ArrowButtonGroup.vue";
import ProcessInfo from "./ProcessInfo.vue";
import { ellipsisPermission } from "../variables";
import { getStages, getMetrics } from "../api";

const props = defineProps({
  process: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["goBackCategory"]);

const myTasksColumns = ref([]);

const metrics = ref();
const stages = ref();

const arrowData = ref([]);

const showProcessInfo = ref(false);

const toggleInfo = () => {
  showProcessInfo.value = !showProcessInfo.value;
};

const hookMetrics = async () => {
  const metricsResponse = await getMetrics({ processId: props.process.id });
  metrics.value = metricsResponse;
};

const hookStages = async () => {
  const stagesResponse = await getStages({ processId: props.process.id });
  stages.value = stagesResponse;

  arrowData.value = stages.value.map((stage) => ({
    id: stage.stage_id,
    body: stage.stage_name,
    header: stage.percentage_format,
    float: stage.agregation_sum,
    percentage: stage.percentage,
  }));
};

onMounted(() => {
  hookMetrics();
  hookStages();
});
</script>
