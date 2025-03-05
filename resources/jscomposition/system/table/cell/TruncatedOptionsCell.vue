<template>
  <div class="tw-flex tw-relative tw-text-nowrap tw-whitespace-nowrap tw-p-3">
    <!-- First Option -->
    <div
      v-if="optionsModel.length"
      class="tw-overflow-hidden tw-text-ellipsis"
      >
      <a
        v-if="href !== null"
        class="hover:tw-text-blue-400 tw-text-gray-500"
        :href="firstOptionHref"
      >
        {{ firstOptionValue }}
      </a>
      <span
        v-else
        class="hover:tw-text-blue-400 tw-text-gray-500 hover:tw-cursor-pointer"
        @click.prevent.stop="onClickOption(optionsModel[0], 0)"
      >
        {{ firstOptionValue }}
      </span>
    </div>

    <!-- Additional Options Popover -->
    <AppPopover
      v-if="hasAdditionalOptions"
      v-model="show"
      :hover="false"
      position="bottom"
      class="!tw-absolute tw-right-0 tw-top-0 tw-h-full tw-flex tw-items-center"
    >
      <div
        class="tw-self-center tw-px-2 tw-rounded-md hover:tw-cursor-pointer hover:tw-bg-gray-200 tw-bg-white"
        @click.prevent="onClick"
      >
        <i class="fas fa-ellipsis-v" />
      </div>
      <template #content>
        <ul class="tw-bg-white tw-list-none tw-text-gray-600 tw-overflow-hidden tw-rounded-lg tw-w-50 tw-text-sm tw-border tw-border-gray-300">
          <li
            v-for="(option, index) in additionalOptions"
            :key="index"
            class="hover:tw-bg-gray-100"
          >
            <a
              v-if="href !== null"
              class="tw-flex tw-py-2 tw-px-4 transition duration-300 tw-text-gray-500 hover:tw-bg-gray-200 hover:tw-text-blue-400"
              :href="getOptionHref(option)"
            >
              {{ optionValues[index + 1] }}
            </a>
            <span
              v-else
              class="tw-flex tw-py-2 tw-px-4 transition duration-300 hover:tw-bg-gray-200 hover:tw-cursor-pointer"
              @click.prevent.stop="onClickOption(option, index + 1)"
            >
              {{ optionValues[index + 1] }}
            </span>
          </li>
        </ul>
      </template>
    </AppPopover>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { isFunction } from "lodash";
import { AppPopover } from "../../../base/index";
/**
 * Props interface for TruncatedOptionsCell component
 * @typedef {Object} Props
 * @property {Array} columns - Array of table columns
 * @property {Object} column - Current column configuration
 * @property {Object} row - Current row data
 * @property {Function} click - Click handler for options
 * @property {Function} formatterOptions - Function to format option display values
 * @property {Function|null} href - Function to generate href for options, or null for non-link options
 * @property {Function|null} filterData - Optional function to filter/transform row data
 */
const props = defineProps({
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
    default: () => {},
  },
  formatterOptions: {
    type: Function,
    default: () => {},
  },
  href: {
    type: Function,
    default: null,
  },
  filterData: {
    type: Function,
    default: null,
  },
});

/** Controls visibility of options popover */
const show = ref(false);

/**
 * Computed array of all options, filtered if filterData prop is provided
 * @returns {Array} Array of option objects
 */
const optionsModel = computed(() => {
  if (props.filterData) {
    return props.filterData(props.row, props.column, props.columns);
  }
  return props.row[props.column.field] || [];
});

/**
 * Computed array of additional options (all except first)
 * @returns {Array} Array of option objects excluding first option
 */
const additionalOptions = computed(() => 
  optionsModel.value.slice(1)
);

/**
 * Checks if there are additional options beyond the first one
 * @returns {boolean} True if more than one option exists
 */
const hasAdditionalOptions = computed(() => 
  optionsModel.value.length > 1
);

/**
 * Pre-computes formatted display values for all options
 * @returns {Object} Map of formatted option values
 */
const optionValues = computed(() => {
  if (!isFunction(props.formatterOptions)) return {};
  
  return optionsModel.value.map(option => 
    props.formatterOptions(option, props.row, props.column, props.columns)
  );
});

/**
 * Gets the formatted value for the first option
 * @returns {string} Formatted value or empty string
 */
const firstOptionValue = computed(() => 
  optionValues.value[0] || ""
);

/**
 * Generates href for first option if href prop is provided
 * @returns {string|null} URL for first option or null
 */
const firstOptionHref = computed(() => 
  props.href ? props.href(optionsModel.value[0]) : null
);

/**
 * Generates href for a given option
 * @param {Object} option - The option object
 * @returns {string|null} URL for the option or null
 */
const getOptionHref = (option) => 
  props.href ? props.href(option) : null;

/**
 * Toggles visibility of options popover
 */
const onClick = () => {
  show.value = !show.value;
};

/**
 * Handles click on an individual option
 * @param {Object} option - The clicked option object
 * @param {number} index - Index of the clicked option
 */
const onClickOption = (option, index) => {
  props.click?.(option, props.row, props.column, props.columns);
};

/**
 * Closes the options popover
 */
const onClose = () => {
  show.value = false;
};
</script>
