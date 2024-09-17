<template>
  <th>
    <div class="tw-flex tw-justify-between">
      <div
        class="tw-w-full tw-relative tw-py-4 tw-px-3 tw-text-left
         tw-overflow-hidden tw-whitespace-nowrap tw-text-ellipsis"
        :style="{ width: width + 'px' }">
        <slot>
          {{ getValue() }}
        </slot>
      </div>
      <div
        :class="{ '!tw-cursor-col-resize': column.resizable }"
        class="tw-w-[10px] tw-cursor-default tw-border-r tw-border-gray-400"
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

    const width = computed(() => {
      return props.column.width || 200;
    });

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
