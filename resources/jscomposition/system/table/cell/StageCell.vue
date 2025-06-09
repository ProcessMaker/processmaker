<template>
  <div class="tw-text-nowrap tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis tw-p-3">
    <span class="tw-text-inherit tw-no-underline">{{ value }}</span>
  </div>
</template>
<script setup>
import { defineProps, computed } from "vue";
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

const value = computed(() => get(props.row, props.column?.field) || "");

</script>
