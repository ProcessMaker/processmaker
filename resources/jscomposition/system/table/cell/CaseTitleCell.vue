<template>
  <div class="tw-text-nowrap tw-whitespace-nowrap tw-p-3">
    <a
      v-if="href !== null"
      class="hover:tw-text-blue-400 tw-text-inherit tw-no-underline"
      :href="href(row)">
      <case-title
        :title="row.case_title_formatted"
        :default-value="getValue()" />
    </a>
    <span
      v-else
      class="hover:tw-text-blue-400 text-inherit no-underline hover:tw-cursor-pointer"
      @click.prevent="onClick">
      <case-title
        :title="row.case_title_formatted"
        :default-value="getValue()" />
    </span>
  </div>
</template>
<script>
import { defineComponent } from "vue";
import { isFunction, get } from "lodash";
import CaseTitle from "./CaseTitle.vue";

export default defineComponent({
  components: { CaseTitle },
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
    href: {
      type: Function,
      default: null,
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
