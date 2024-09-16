<template>
  <td class="tw-p-3">
    <div
      v-if="!column.cellRenderer"
      :style="{ maxWidth: `${column.width}px` }">
      <slot
        :columns="columns"
        :column="column"
        :row="row">
        {{ getValue() }}
      </slot>
    </div>
    <component
      v-else
      :is="column.cellRenderer()"
      :columns="columns"
      :column="column"
      :row="row" />
  </td>
</template>

<script>
import { defineComponent } from "vue";
import { isFunction, get } from "lodash";

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
    row: {
      type: Object,
      default: () => {},
    },
  },
  setup(props, { emit }) {
    const getValue = () => {
      if (isFunction(props.column?.formatter)) {
        return props.column?.formatter(props.row, props.column, props.columns);
      }
      return get(props.row, props.column?.field) || "";
    };
    return {
      getValue,
    };
  },
});
</script>
