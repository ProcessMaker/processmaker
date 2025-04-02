<template>
  <td
    class="tw-relative tw-p-0"
    :style="{ width: width }">
    <template v-if="!column.cellRenderer">
      <slot
        :columns="columns"
        :column="column"
        :row="row"
        :index-row="indexRow">
        <div
          :style="{ width: width }"
          class="tw-p-3 tw-text-ellipsis tw-text-nowrap tw-overflow-hidden">
          {{ getValue() }}
        </div>
      </slot>
    </template>
    <component
      :is="getComponent()"
      v-else
      :style="{ width: width }"
      v-bind="getParams()"
      :columns="columns"
      :column="column"
      :row="row"
      :index-row="indexRow"
      @collapseContainer="collapseContainer" />
  </td>
</template>

<script>
import { defineComponent, computed } from "vue";
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
    indexRow: {
      type: Number,
      default: 0,
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

    const index = computed(() => props.columns.findIndex((column) => column.field === props.column.field));

    const width = computed(() => {
      if (index.value === props.columns.length - 1) {
        return "auto";
      }
      return `${props.column.width || 200}px`;
    });

    return {
      getComponent,
      getParams,
      getValue,
      collapseContainer,
      width,
    };
  },
});
</script>
