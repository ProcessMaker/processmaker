<template>
  <li class="tw-py-0 tw-px-5 tw-text-sm">
    <a
      v-if="href !== null"
      class="hover:tw-text-blue-400 tw-text-gray-500 tw-text-ellipsis tw-text-nowrap tw-overflow-hidden"
      :href="href(option)">
      {{ getValueOption(option) }}
    </a>
    <span
      v-else
      class="hover:tw-text-blue-400 tw-text-gray-500 hover:tw-cursor-pointer
        tw-text-ellipsis tw-text-nowrap tw-overflow-hidden"
      @click.prevent.stop="onClickOption(option)">
      {{ getValueOption(option) }}
    </span>
  </li>
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
    href: {
      type: Function,
      default: null,
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
