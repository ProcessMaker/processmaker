<template>
  <div class="tw-text-nowrap tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis tw-p-3">
    <span class="tw-text-inherit tw-no-underline">{{ value }}</span>
  </div>
</template>
<script setup>
import { defineProps, computed } from "vue";
import { t } from "i18next";
import { get } from "lodash";

const props = defineProps({
  columns: {
    type: Array,
    default: () => [],
  },
  column: {
    type: Object,
    default: () => ({}),
  },
  row: {
    type: Object,
    default: () => ({}),
  },
});

const defaultStages = {
  COMPLETED: {
    label: `${t("Completed")}`,
  },
  CANCELED: {
    label: `${t("Canceled")}`,
  },
  ERROR: {
    label: `${t("Error")}`,
  },
  IN_PROGRESS: {
    label: `${t("In Progress")}`,
  },
};

const value = computed(() => {
  const fieldValue = get(props.row, props.column?.field) || "";
  
  if (!fieldValue) {
    return "";
  }

  // Check if the value matches any of the default stages
  const matchedStage = defaultStages[fieldValue];
  
  if (matchedStage) {
    return matchedStage.label;
  }
  
  // If no match found, return the original value
  return fieldValue;
});
</script>
