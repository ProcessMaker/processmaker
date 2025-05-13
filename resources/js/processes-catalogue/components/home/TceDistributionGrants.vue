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
import { ref } from "vue";
import CustomHomeTableSection from "./CustomHomeTableSection/CustomHomeTableSection.vue";
import ProcessCollapseInfo from "../ProcessCollapseInfo.vue";
import ArrowButtonHome from "./ArrowButtonGroup/ArrowButtonHome.vue";
import ArrowButtonGroup from "./ArrowButtonGroup/ArrowButtonGroup.vue";
import ProcessInfo from "./ProcessInfo.vue";
import { ellipsisPermission } from "../variables";

const props = defineProps({
  process: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["goBackCategory"]);

const myTasksColumns = ref([]);

const arrowData = ref([
  // {
  //   id: "1",
  //   body: "Grants",
  //   header: "40%",
  //   float: "28K",
  //   percentage: 40,
  //   content: "28,678",
  //   color: "amber",
  // },
  // {
  //   id: "2",
  //   body: "Scholarships",
  //   header: "20%",
  //   float: "4K",
  //   percentage: 20,
  //   content: "4,678",
  //   color: "green",
  // },
  // {
  //   id: "3",
  //   body: "Loans",
  //   header: "15%",
  //   float: "11K",
  //   percentage: 15,
  //   content: "11,678",
  //   color: "blue",
  // },
  // {
  //   id: "4",
  //   body: "Out of pocket remaining",
  //   header: "25%",
  //   float: "17K",
  //   percentage: 25,
  //   content: "17,649",
  //   color: "red",
  // },
  // {
  //   id: "4",
  //   body: "Out of pocket remaining",
  //   header: "25%",
  //   float: "17K",
  //   percentage: 25,
  //   content: "17,649",
  //   color: "red",
  // },
  // {
  //   id: "4",
  //   body: "Out of pocket remaining",
  //   header: "25%",
  //   float: "17K",
  //   percentage: 25,
  //   content: "17,649",
  //   color: "red",
  // },
]);

const showProcessInfo = ref(false);

const toggleInfo = () => {
  showProcessInfo.value = !showProcessInfo.value;
};
</script>
