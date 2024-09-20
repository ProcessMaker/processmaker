<template>
  <th>
    <div class="tw-flex tw-relative tw-text-nowrap tw-whitespace-nowrap tw-justify-between">
      <div
        class="tw-py-4 tw-px-3 tw-text-left tw-text-nowrap tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis"
        :style="{ width: width + 'px' }">
        <slot>
          {{ getValue() }}
        </slot>
      </div>

      <div class="tw-h-auto tw-flex tw-items-center">
        <slot name="filter" />
      </div>

      <div
        :class="{ '!tw-cursor-col-resize': column.resizable }"
        class="tw-w-[5px] tw-cursor-default tw-border-r tw-border-gray-400"
        @mousedown="column.resizable ? columnResize.startResize($event) : null">
        &nbsp;
      </div>
    </div>
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
