<template>
  <div class="tw-flex tw-flex-col tw-space-y-4 tw-h-full tw-w-full">
    <process-collapse-info
      :process="process"
      :ellipsis-permission="ellipsisPermission"
      :my-tasks-columns="myTasksColumns"
      @toggle-info="toggleInfo"
      @goBackCategory="emit('goBackCategory')" />

    <BaseCardButtonGroup
      v-if="data.length > 0"
      :key="dataKey + 'button'"
      class="tw-px-2"
      :data="data"
      @change="onChangeMetric" />

    <PercentageCardButtonGroup
      v-if="percentageData.length > 0"
      :key="dataKey + 'subpercentage'"
      class="tw-px-2"
      :data="percentageData"
      @change="onChangeStage" />

    <CustomHomeTableSection
      :key="dataKey + 'table'"
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
import { buildMetrics } from "./config/metrics";

const props = defineProps({
  process: {
    type: Object,
    required: true,
  },
});
const emit = defineEmits(["goBackCategory"]);

const myTasksColumns = ref([]);

const stages = ref();
const data = ref([]);
const percentageData = ref([
  {
    id: "1",
    header: "Grants",
    body: "40%",
    percentage: 40,
    content: "28,678",
    color: "amber",
  },
  {
    id: "2",
    header: "Scholarships",
    body: "20%",
    percentage: 20,
    content: "4,678",
    color: "green",
  },
  {
    id: "3",
    header: "Loans",
    body: "15%",
    percentage: 15,
    content: "11,678",
    color: "blue",
  },
  {
    id: "4",
    header: "Out of pocket remaining",
    body: "25%",
    percentage: 25,
    content: "17,649",
    color: "red",
  },
]);

const showProcessInfo = ref(false);
const advancedFilter = ref([]);
const dataKey = ref(0);
const toggleInfo = () => {
  showProcessInfo.value = !showProcessInfo.value;
};

const buildAdvancedFilter = () => {
  const stage = percentageData.value.find((item) => item.active);
  const metric = data.value.find((item) => item.active);

  return [{
    subject: {
      type: "Field",
      value: "metric",
    },
    operator: "=",
    value: metric.id,
  },
  {
    subject: {
      type: "Field",
      value: "stage",
    },
    operator: "=",
    value: stage.id,
  }];
};

const hookMetrics = async () => {
  const metricsResponse = await getMetrics({ processId: props.process.id });
  data.value = buildMetrics(metricsResponse.data);
};

const hookStages = async () => {
  const stagesResponse = await getStages({ processId: props.process.id });
  stages.value = stagesResponse.data;
};

const onChangeStage = (stage, idxItem) => {
  percentageData.value.forEach((item, index) => {
    index === idxItem ? item.active = true : item.active = false;
  });
  dataKey.value += 1;
  advancedFilter.value = buildAdvancedFilter();
};

const onChangeMetric = (stage, idxItem) => {
  data.value.forEach((item, index) => {
    index === idxItem ? item.active = true : item.active = false;
  });
  dataKey.value += 1;
  advancedFilter.value = buildAdvancedFilter();
};

onMounted(() => {
  hookMetrics();
  hookStages();
});
</script>
