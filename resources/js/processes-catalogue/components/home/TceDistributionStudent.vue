<template>
  <div class="tw-flex tw-flex-col tw-space-y-4 tw-h-full tw-w-full">
    <process-collapse-info
      :process="process"
      :ellipsis-permission="ellipsisPermission"
      :my-tasks-columns="myTasksColumns"
      @toggle-info="toggleInfo"
      @goBackCategory="emit('goBackCategory')" />
    <BaseCardButtonGroup :data="data" />

    <PercentageCardButtonGroup :data="percentageData" />
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
import { ref } from "vue";
import CustomHomeTableSection from "./CustomHomeTableSection/CustomHomeTableSection.vue";
import BaseCardButtonGroup from "./ButtonGroup/BaseCardButtonGroup.vue";
import ProcessCollapseInfo from "../ProcessCollapseInfo.vue";
import PercentageCardButtonGroup from "./PercentageButtonGroup/PercentageCardButtonGroup.vue";
import { ellipsisPermission } from "../variables";
import ProcessInfo from "./ProcessInfo.vue";

const props = defineProps({
  process: {
    type: Object,
    required: true,
  },
});
const emit = defineEmits(["goBackCategory"]);

const myTasksColumns = ref([]);

const data = ref([
  {
    id: "1",
    header: "Max amount available",
    body: "Across 10 aplicants",
    icon: "fas fa-reply",
    content: "84K",
    active: true,
    className: "tw-bg-white hover:tw-bg-gray-200",
  },
  {
    id: "2",
    header: "Application awarded",
    body: "30% of all submitted",
    icon: "fas fa-user",
    content: "3",
    className: "tw-bg-amber-100 hover:tw-bg-amber-200",
    active: false,
  },
  {
    id: "3",
    header: "Total amount awarded",
    body: "Across 3 aplicants",
    icon: "fas fa-user",
    content: "46K+",
    className: "tw-bg-green-100 hover:tw-bg-green-200",
    active: false,
  },
]);

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

const toggleInfo = () => {
  showProcessInfo.value = !showProcessInfo.value;
};
</script>
