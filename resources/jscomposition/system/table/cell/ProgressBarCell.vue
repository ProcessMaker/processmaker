<template>
  <ProgressBar
    :percentage="percentage"
    :color="color"
  />
</template>

<script setup>
import { computed } from "vue";
import ProgressBar from "../../ProgressBar.vue";

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

const percentage = computed(() => props.row[props.column.field]);

const color = computed(() => {
  if (percentage.value < 100) {
    return "green";
  }
  return "blue";
});
</script>
