<template>
  <div
    class="tw-text-nowrap tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis tw-p-3">
    <a
      v-if="href !== null"
      class="hover:tw-text-blue-400 tw-text-gray-500"
      :href="href(row)">
      {{ getValue() }}
    </a>
    <span
      v-else
      class="hover:tw-text-blue-400 tw-text-gray-500 hover:tw-cursor-pointer"
      @click.prevent="onClick">
      {{ getValue() }}
    </span>
  </div>
</template>
<script setup>
import { defineProps } from "vue";
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

const getValue = () => {
  if (isFunction(props.column?.formatter)) {
    return props.column?.formatter(props.row, props.column, props.columns);
  }
  return get(props.row, props.column?.field) || "";
};

const onClick = () => {
  props.click && props.click(props.row, props.column, props.columns);
};
</script>
