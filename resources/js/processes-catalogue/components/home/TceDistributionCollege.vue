<template>
  <div class="tw-flex tw-flex-col tw-space-y-4 tw-h-full tw-w-full">
    <process-collapse-info
      :process="process"
      :ellipsis-permission="ellipsisPermission"
      :my-tasks-columns="myTasksColumns"
      :my-cases-columns="myCasesColumns"
      @toggle-info="toggleInfo"
      @goBackCategory="emit('goBackCategory')" />

    <BaseCardButtonGroup
      v-if="data.length > 0"
      :key="dataKey + 'button'"
      :data="data"
      @change="onChangeMetric" />

    <PercentageCardButtonGroup
      :key="dataKey + 'subpercentage'"
      :data="stages"
      @change="onChangeStage" />

    <CustomHomeTableSection
      ref="childRef"
      :key="dataKey + 'table'"
      :advanced-filter="advancedFilter"
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
import BaseCardButtonGroup from "./ButtonGroup/BaseCardButtonGroup.vue";
import ProcessCollapseInfo from "../ProcessCollapseInfo.vue";
import PercentageCardButtonGroup from "./PercentageButtonGroup/PercentageCardButtonGroup.vue";
import { ellipsisPermission } from "../variables";
import ProcessInfo from "./ProcessInfo.vue";
import { getMetrics, getStages } from "../api";
import { buildMetrics, buildStages, updateActiveStage, verifyResponseMetrics, buildAdvancedFilter } from "./config/metrics";

const childRef = ref(null)

const props = defineProps({
  process: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["goBackCategory"]);

const myTasksColumns = ref([]);
const myCasesColumns = ref([]);

const data = ref([]);
const advancedFilter = ref([]);
const dataKey = ref(0);
const stages = ref([]);

const hookMetrics = async () => {
  try {
    const metricsResponse = await getMetrics({ processId: props.process.id });
    if (!metricsResponse.data) {
      ProcessMaker.alert("Error: METRICS_API_ENDPOINT Not Found or Invalid", "danger");
      return;
    }

    const responseMetrics = buildMetrics(metricsResponse.data);
    const verifyMetrics = verifyResponseMetrics(responseMetrics);

    if (!verifyMetrics) {
      ProcessMaker.alert("Issue with METRICS_API_ENDPOINT: Invalid API Response!", "danger");
      return;
    }

    data.value = responseMetrics;
  } catch (error) {
    console.error(error);
  }
};

const hookStages = async () => {
  const stagesResponse = await getStages({ processId: props.process.id });
  stages.value = buildStages(stagesResponse.data.stages);
};

const showProcessInfo = ref(false);

const toggleInfo = () => {
  showProcessInfo.value = !showProcessInfo.value;
};

const onChangeStage = async (stage, idxItem) => {
  hookMetrics();
  await hookStages();

  updateActiveStage(stages.value, stage);

  dataKey.value += 1;
  advancedFilter.value = buildAdvancedFilter(stages.value);
};

const onChangeMetric = (stage, idxItem) => {
  data.value.forEach((item, index) => {
    index === idxItem ? item.active = true : item.active = false;
  });
  dataKey.value += 1;
  advancedFilter.value = buildAdvancedFilter(stages.value);
};

onMounted(() => {
  hookMetrics();
  hookStages();
  myCasesColumns.value = childRef.value?.getColumns();
});
</script>
