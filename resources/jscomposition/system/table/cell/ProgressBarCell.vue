<template>
  <div
    class="tw-text-nowrap tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis tw-p-3">
    <div :class="`tw-w-full tw-bg-${color}-200 tw-rounded-full`">
      <div
        :class="`tw-bg-${color}-500 tw-text-white tw-rounded-full tw-text-center tw-h-2`"
        role="progressbar"
        :style="{ width: percentage + '%' }"
        :aria-valuenow="percentage"
        aria-valuemin="0"
        aria-valuemax="100">
      &nbsp;
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

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
  data: {
    type: Function,
    default: () => {},
  },
  color: {
    type: String,
    default: "green",
  },
});

const percentage = computed(() => props.data(props.row, props.column, props.columns));

</script>
