<template>
  <th
    class="tw-relative thead-resizable tw-p-0"
    :style="{ width: width }">
    <div
      :style="{ width: width }"
      class="tw-py-3 tw-px-3 tw-text-left tw-text-nowrap tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis">
      <slot>
        {{ $t(getValue()) }}
      </slot>
    </div>

    <div class="tw-absolute tw-right-0 tw-top-0 tw-h-full tw-w-5 tw-flex tw-items-center tw-mr-1">
      <slot name="filter" />
    </div>

    <div
      class="tw-absolute tw-right-0 tw-top-0 tw-w-1.5 tw-border-r hover:tw-border-blue-400 hover:tw-border-r-2
        tw-h-full tw-cursor-col-resize tw-select-none tw-border-gray-300"
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
    const columnResize = columnResizeComposable({
      column: props.column,
      stopResize: () => {
        emit("stopResize", props.column);
      },
    });

    const index = computed(() => props.columns.findIndex((column) => column.field === props.column.field));

    const width = computed(() => {
      if (index.value === props.columns.length - 1) {
        return "auto";
      }
      return `${props.column.width || 200}px`;
    });

    const getValue = () => {
      if (isFunction(props.column?.headerFormatter)) {
        return props.column?.headerFormatter(props.columns);
      }

      return props.column.header || props.column.field || "";
    };
    return {
      getValue,
      index,
      width,
      columnResize,
    };
  },
});
</script>
<style scoped>
.thead-resizable:nth-last-child(-n+2) > div:nth-last-child(1) {
  border-right-width: 0px;
}
</style>
