<template>
  <td
    class="tw-relative"
    :style="{ width: `${column.width}px` }">
    <div v-if="!column.cellRenderer">
      <slot
        :columns="columns"
        :column="column"
        :row="row">
        <div class="tw-p-3">
          {{ getValue() }}
        </div>
      </slot>
    </div>
    <component
      :is="getComponent()"
      v-else
      v-bind="getParams()"
      :columns="columns"
      :column="column"
      :row="row"
      @collapseContainer="collapseContainer" />
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

    const getComponent = () => props.column.cellRenderer().component || props.column.cellRenderer();

    const getParams = () => props.column.cellRenderer().params || {};

    const collapseContainer = (value) => emit("toogleContainer", value);

    return {
      getComponent,
      getParams,
      getValue,
      collapseContainer,
    };
  },
});
</script>
