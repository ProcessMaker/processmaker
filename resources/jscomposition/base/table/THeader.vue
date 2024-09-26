<template>
  <th
    class="tw-relative"
    :style="{ width: width + 'px' }">
    <div
      class="tw-py-4 tw-px-3 tw-text-left tw-text-nowrap tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis">
      <slot>
        {{ getValue() }}
      </slot>
    </div>

    <div class="tw-absolute tw-right-0 tw-top-0 tw-h-full tw-w-5 tw-flex tw-items-center">
      <slot name="filter" />
    </div>

    <div
      class="tw-absolute tw-right-0 tw-top-0 tw-w-1
        tw-h-full tw-cursor-col-resize tw-select-none tw-border-r tw-border-gray-400"
      @mousedown="column.resizable ? columnResize.startResize($event) : null" />
  </th>
</template>

<script>
import { isFunction } from "lodash";
import { defineComponent, computed } from "vue";
import { columnResizeComposable } from "./composables/columnComposable";

export default defineComponent({
  props: {
    columns: {
      type: Array,
      default: () => [],
    },
    column: {
      type: Object,
      default: () => {},
    },
  },
  setup(props, { emit }) {
    const columnResize = columnResizeComposable(props.column);

    const width = computed(() => props.column.width || 200);

    const getValue = () => {
      if (isFunction(props.column?.headerFormatter)) {
        return props.column?.headerFormatter(props.columns);
      }

      return props.column.header || props.column.field || "";
    };
    return {
      getValue,
      width,
      columnResize,
    };
  },
});
</script>
