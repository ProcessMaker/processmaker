<template>
  <div class="tw-flex tw-relative tw-text-nowrap tw-whitespace-nowrap tw-p-3">
    <div
      v-if="firstOptionValue"
      :data-test="`truncated-group-${indexRowComputed}`"
      class="tw-overflow-hidden tw-text-ellipsis">
      <a
        v-if="href !== null"
        class="hover:tw-text-blue-400 tw-text-gray-500"
        :href="firstOptionHref">
        {{ firstOptionFormatted }}
      </a>
      <span
        v-else
        class="hover:tw-text-blue-400 tw-text-gray-500 hover:tw-cursor-pointer"
        @click.prevent.stop="onClickOption(firstOptionValue, 0)">
        {{ firstOptionFormatted }}
      </span>
    </div>

    <AppPopover
      v-if="hasAdditionalOptions"
      v-model="show"
      :hover="false"
      :data-test="`truncated-group-popover-${indexRow}`"
      position="bottom"
      class="!tw-absolute tw-right-0 tw-top-0 tw-h-full tw-flex tw-items-center">
      <div
        class="tw-flex tw-justify-center tw-items-center tw-rounded-full tw-h-4 tw-w-4 tw-border
          hover:tw-cursor-pointer hover:tw-bg-gray-100 hover:tw-border-gray-300 tw-bg-white tw-text-gray-400"
        @click.prevent="onClick">
        <i class="fas fa-ellipsis-h tw-text-[0.5rem]" />
      </div>
      <template #content>
        <ul
          class="tw-bg-white tw-list-none tw-text-gray-600 tw-py-2 tw-space-y-2 tw-flex tw-flex-col
            tw-max-w-80 tw-min-w-50
            tw-overflow-hidden tw-rounded-lg tw-text-sm tw-border tw-border-gray-300">
          <li
            v-for="(optionTitle, indexTitle) in optionsModel"
            :key="indexTitle">
            <TitleOption
              :formatter-options="formatterOptions"
              :option="optionsModel[indexTitle]"
              :columns="columns"
              :row="row"
              :column="column"
              :data-test="`truncated-group-popover-title-${indexTitle}`" />

            <ul
              class="tw-list-disc tw-list-inside">
              <BaseOption
                v-for="(option, index) in optionsModel[indexTitle].options"
                :key="index"
                :formatter-options="formatterOptions"
                :option="optionsModel[indexTitle].options[index]"
                :columns="columns"
                :row="row"
                :href="href"
                :column="column" />
            </ul>
          </li>
        </ul>
      </template>
    </AppPopover>
  </div>
</template>
<script>
import {
  defineComponent, ref, onMounted, computed,
} from "vue";
import { isFunction } from "lodash";
import { AppPopover } from "../../../../base/index";
import TitleOption from "./TitleOption.vue";
import BaseOption from "./BaseOption.vue";

export default defineComponent({
  components: {
    AppPopover,
    TitleOption,
    BaseOption,
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
    click: {
      type: Function,
      default: new Function(),
    },
    formatterOptions: {
      type: Function,
      default: new Function(),
    },
    href: {
      type: Function,
      default: null,
    },
    // Filter Data, method to filter the input data
    formatData: {
      type: Function,
      default: null,
    },
    indexRow: {
      type: Number,
      default: 0,
    },
  },
  setup(props) {
    const show = ref(false);

    /**
     * Computed array of all options, filtered if filterData prop is provided
     * @returns {Array} Array of option objects
     */
    const optionsModel = computed(() => {
      if (props.formatData) {
        return props.formatData(props.row, props.column, props.columns);
      }
      return props.row[props.column.field] || [];
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

    const onClickOption = (option, index) => {
      props.click && props.click(option, props.row, props.column, props.columns);
    };

    const onClose = () => {
      show.value = false;
    };

    /**
     * Pre-computes formatted display values for all options
     * @returns {Object} Map of formatted option values
     */
    const optionValues = computed(() => {
      if (!isFunction(props.formatterOptions)) return {};

      return optionsModel.value.map((option) => props.formatterOptions(option, props.row, props.column, props.columns));
    });

    /**
     * Gets the formatted value for the first option
     * @returns {string} Formatted value or empty string
     */
    const firstOptionValue = computed(() => {
      if (optionsModel.value[0]?.options?.[0]) {
        return optionsModel.value[0]?.options?.[0];
      }
      return null;
    });

    /**
     * Gets the formatted value for the first option
     * @returns {string} Formatted value or empty string
     */
    const firstOptionFormatted = computed(() => {
      if (optionsModel.value[0]?.options?.[0]) {
        return props.formatterOptions(optionsModel.value[0]?.options?.[0]);
      }
      return null;
    });

    /**
     * Generates href for first option if href prop is provided
     * @returns {string|null} URL for first option or null
     */
    const firstOptionHref = computed(() => {
      if (props.href && firstOptionValue.value) {
        return props.href(firstOptionValue.value);
      }
      return null;
    });

    /**
     * Checks if there are additional options to display
     * @returns {boolean} True if there are additional options, false otherwise
     */
    const hasAdditionalOptions = computed(() => optionsModel.value.length > 1 || (optionsModel.value[0] && optionsModel.value[0].options.length > 1));

    const indexRowComputed = computed(() => props.indexRow);

    return {
      show,
      optionsModel,
      onClose,
      onClickOption,
      onClick,
      getValueOption,
      firstOptionValue,
      optionValues,
      firstOptionHref,
      firstOptionFormatted,
      hasAdditionalOptions,
      indexRowComputed,
    };
  },
});
</script>
