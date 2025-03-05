<template>
  <div class="tw-flex tw-relative tw-text-nowrap tw-whitespace-nowrap tw-p-3">
    <div class="tw-overflow-hidden tw-text-ellipsis ">
      <span>
        {{ value }}
      </span>
    </div>

    <AppPopover
      v-if="optionsModel.length > 1"
      v-model="show"
      :hover="false"
      position="bottom"
      class="!tw-absolute tw-right-0 tw-top-0  tw-h-full tw-flex tw-items-center">
      <div
        class="tw-text-xs tw-py-1 tw-px-1 hover:tw-cursor-pointer
        hover:tw-bg-gray-200 tw-rounded"
        @click.prevent="onClick">
        <i class="fas fa-ellipsis-v" />
      </div>

      <template #content>
        <ul
          class="tw-bg-white tw-list-none
            tw-overflow-hidden tw-rounded-lg tw-w-50 tw-text-sm tw-border tw-border-gray-300">
          <template v-for="(option, index) in optionsModel">
            <li
              v-if="index > 0"
              :key="index"
              class="hover:tw-bg-gray-100">
              <span class="tw-flex tw-py-2 tw-px-4 transition duration-300">
                {{ getValueOption(option, index) }}
              </span>
            </li>
          </template>
        </ul>
      </template>
    </AppPopover>
  </div>
</template>
<script>
import { defineComponent, ref, computed } from "vue";
import { isFunction, get } from "lodash";
import { AppPopover } from "../../../base/index";

export default defineComponent({
  components: {
    AppPopover,
  },
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
    formatterOptions: {
      type: Function,
      default: new Function(),
    },
  },
  setup(props) {
    const show = ref(false);
    const optionsModel = ref(props.row[props.column.field]);

    // Memoize the value calculation
    /**
     * Computes the display value for the first item in the truncated list
     * @returns {string} The formatted value from either:
     *  - The column formatter if defined
     *  - The name property of first array element if field is an array
     *  - Empty string if no valid value found
     */
    const value = computed(() => {
      // If there's a formatter, use it
      if (isFunction(props.column?.formatter)) {
        return props.column.formatter(props.row, props.column, props.columns);
      }

      // Safely access nested properties
      const fieldValue = get(props.row, props.column.field, []);
      
      // Check if it's an array with at least one element
      if (Array.isArray(fieldValue) && fieldValue.length > 0) {
        return fieldValue[0]?.name || '';
      }

      return '';
    });

    const getValueOption = (option) => {
      if (isFunction(props.formatterOptions)) {
        return props.formatterOptions(option, props.row, props.column, props.columns);
      }
      return "";
    };

    const onClick = () => {
      show.value = !show.value;
    };

    const onClose = () => {
      show.value = false;
    };

    return {
      show,
      optionsModel,
      onClose,
      onClick,
      value,
      getValueOption,
    };
  },
});
</script>
