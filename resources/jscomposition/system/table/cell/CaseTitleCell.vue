<template>
  <div>
    <div
      v-if="row.case_title_formatted"
      class="tw-text-nowrap tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis"
      :style="{ width: column.width + 'px' }"
      v-html="row.case_title_formatted" 
      />
    <slot
      v-else
      :columns="columns"
      :column="column"
      :row="row"
    >
      {{ getValue() }}
    </slot>
  </div>
</template>
<script>
import { defineComponent } from "vue";

export default defineComponent({
  props: {
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
  },
  setup() {
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
