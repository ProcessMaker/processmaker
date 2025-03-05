<template>
  <div
    class="tw-text-nowrap tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis tw-p-3">
    <a
      v-if="href !== null"
      class="hover:tw-text-blue-400 tw-text-inherit tw-no-underline"
      :href="href(row)">
      {{ value }}
    </a>
    <span
      v-else
      class="hover:tw-text-blue-400 tw-text-inherit tw-no-underline hover:tw-cursor-pointer"
      @click.prevent="onClick">
      {{ value }}
    </span>
  </div>
</template>
<script setup>
import { defineProps, computed } from "vue";
import { isFunction, get } from "lodash";

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
  click: {
    type: Function,
    default: new Function(),
  },
  href: {
    type: Function,
    default: null,
  },
});

/**
 * Computes the display value for the link/text
 * @returns {string} The formatted value from either:
 *  - The column formatter if defined
 *  - The value at the column's field path in the row data
 *  - Empty string if no valid value found
 */
const value = computed(() => {
  if (isFunction(props.column?.formatter)) {
    return props.column?.formatter(props.row, props.column, props.columns);
  }
  return get(props.row, props.column?.field) || "";
});

const onClick = () => {
  props.click && props.click(props.row, props.column, props.columns);
};
</script>
