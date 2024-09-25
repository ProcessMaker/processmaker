<template>
  <div
    v-if="row.case_title_formatted"
    class="tw-text-nowrap tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis"
    :style="{ width: column.width + 'px' } ">
    <a
      class="hover:tw-text-blue-500"
      href="#"
      @click.prevent="onClick">
      {{ getValue() }}
    </a>
  </div>
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
  },
  setup(props) {
    const getValue = () => {
      if (isFunction(props.column?.formatter)) {
        return props.column?.formatter(props.row, props.column, props.columns);
      }
      return get(props.row, props.column?.field) || "";
    };

    const onClick = () => {
      props.click && props.click(props.row, props.column, props.columns);
    };

    return {
      onClick,
      getValue,
    };
  },
});
</script>
