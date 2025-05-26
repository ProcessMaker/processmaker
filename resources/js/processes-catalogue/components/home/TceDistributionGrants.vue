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
        v-if="lastStage"
        :key="dataStagesKey + 'first'"
        class="tw-w-60"
        color="blue"
        :header="firstStage.header"
        :body="firstStage.body"
        :active="firstStage.active"
        @click="onClickFirstStage" />

      <ArrowButtonGroup
        v-if="dataStages.length > 0"
        :key="dataStagesKey + 'group'"
        class="tw-flex-grow tw-overflow-auto"
        :data="dataStages"
        active-color="orange"
        color="tangerine"
        @change="updateDataStages" />

      <ArrowButtonHome
        v-if="lastStage"
        :key="dataStagesKey + 'last'"
        class="tw-w-60"
        color="emerald"
        :header="lastStage.header"
        :body="lastStage.body"
        :active="lastStage.active"
        @click="onClickLastStage" />
    </div>

    <CustomHomeTableSection
      :key="dataStagesKey + 'table'"
      class="tw-w-full tw-flex tw-flex-col
      tw-overflow-hidden tw-grow tw-p-4 tw-bg-white tw-rounded-lg tw-shadow-md tw-border tw-border-gray-200"
      :advanced-filter="advancedFilter"
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
import { getStages } from "../api";
import { buildStages } from "./config/metrics";

const props = defineProps({
  process: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["goBackCategory"]);

const myTasksColumns = ref([]);
const stages = ref();
const dataStages = ref([]);
const lastStage = ref(null);
const firstStage = ref(null);
const showProcessInfo = ref(false);
const dataStagesKey = ref(0);
const toggleInfo = () => {
  showProcessInfo.value = !showProcessInfo.value;
};

const advancedFilter = ref([]);

const hookStages = async () => {
  const stagesResponse = await getStages({ processId: props.process.id });
  stages.value = stagesResponse.data;
  const stagesFormatted = buildStages(stages.value);

  lastStage.value = stagesFormatted.pop();
  firstStage.value = stagesFormatted.shift();
  dataStages.value = stagesFormatted;
};

const buildAdvancedFilters = (stage) => {
  advancedFilter.value = [{
    subject: {
      type: "Field",
      value: "stage",
    },
    operator: "=",
    value: stage.id,
  },
  ];
};

const updateDataStages = (data) => {
  lastStage.value.active = false;
  firstStage.value.active = false;
  dataStages.value = data;
  dataStagesKey.value += 1;
  buildAdvancedFilters(dataStages.value.find((stage) => stage.active));
};

const onClickLastStage = () => {
  dataStages.value.forEach((stage) => {
    stage.active = false;
  });
  dataStagesKey.value += 1;
  firstStage.value.active = false;
  lastStage.value.active = true;
  buildAdvancedFilters(lastStage.value);
};

const onClickFirstStage = () => {
  dataStages.value.forEach((stage) => {
    stage.active = false;
  });
  firstStage.value.active = true;
  dataStagesKey.value += 1;
  lastStage.value.active = false;
  buildAdvancedFilters(firstStage.value);
};

onMounted(() => {
  hookStages();
});
</script>
