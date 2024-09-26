<template>
  <div
    class="tw-text-nowrap tw-whitespace-nowrap tw-p-3">
    <a
      href="#"
      class="hover:tw-text-blue-500"
      @click.prevent="onClick">
      <div
        v-if="row.case_title_formatted"
        class="tw-overflow-hidden tw-text-ellipsis"
        v-html="row.case_title_formatted" />
      <span
        v-else
        class="tw-overflow-hidden tw-text-ellipsis">{{ getValue() }}</span>
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
