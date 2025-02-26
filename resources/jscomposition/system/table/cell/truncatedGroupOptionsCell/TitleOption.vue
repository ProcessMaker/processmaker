<template>
  <div
    class="tw-flex tw-px-4 tw-text-gray-400
      tw-text-xs tw-italic tw-space-x-2 tw-items-center">
    <i class="fp-play-outline tw-text-base" />
    <span class="tw-text-ellipsis tw-text-nowrap tw-overflow-hidden">{{ getValueOption(option) }}</span>
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
    option: {
      type: Object,
      default: () => ({}),
    },
    formatterOptions: {
      type: Function,
      default: new Function(),
    },
  },
  setup(props) {
    const getValueOption = (option) => {
      if (typeof props.formatterOptions === "function") {
        return props.formatterOptions(option, props.row, props.column, props.columns);
      }
      return "";
    };

    return {
      getValueOption,
    };
  },
});
</script>
