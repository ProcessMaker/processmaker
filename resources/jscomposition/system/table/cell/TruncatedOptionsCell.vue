<template>
  <div class="tw-flex tw-relative tw-text-nowrap tw-whitespace-nowrap tw-p-3">
    <div
      v-if="optionsModel.length"
      class="tw-overflow-hidden tw-text-ellipsis">
      <a
        v-if="href !== null"
        class="hover:tw-text-blue-400 tw-text-gray-500"
        :href="href(optionsModel[0])">
        {{ getValueOption(optionsModel[0], 0) }}
      </a>
      <span
        v-else
        class="hover:tw-text-blue-400 tw-text-gray-500 hover:tw-cursor-pointer"
        href="#"
        @click.prevent.stop="onClickOption(optionsModel[0], 0)">
        {{ getValueOption(optionsModel[0], 0) }}
      </span>
    </div>
    <AppPopover
      v-if="optionsModel.length > 1"
      v-model="show"
      :hover="false"
      position="bottom"
      class="!tw-absolute tw-right-0 tw-top-0  tw-h-full tw-flex tw-items-center">
      <div
        class="tw-self-center tw-px-2 tw-rounded-md hover:tw-cursor-pointer hover:tw-bg-gray-200 tw-bg-white "
        @click.prevent="onClick">
        <i class="fas fa-ellipsis-v" />
      </div>
      <template #content>
        <ul
          class="tw-bg-white tw-list-none tw-text-gray-600
            tw-overflow-hidden tw-rounded-lg tw-w-50 tw-text-sm tw-border tw-border-gray-300">
          <template v-for="(option, index) in optionsModel">
            <li
              v-if="index > 0"
              :key="index"
              class="hover:tw-bg-gray-100">
              <a
                v-if="href !== null"
                class="tw-flex tw-py-2 tw-px-4 transition duration-300 tw-text-gray-500 hover:tw-bg-gray-200 hover:tw-text-blue-400"
                :href="href(option)">
                {{ getValueOption(option, index) }}
              </a>
              <span
                v-else
                class="tw-flex tw-py-2 tw-px-4 transition duration-300 hover:tw-bg-gray-200 hover:tw-cursor-pointer"
                @click.prevent.stop="onClickOption(option, index)">
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
import { defineComponent, ref, onMounted } from "vue";
import { isFunction } from "lodash";
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
    filterData: {
      type: Function,
      default: null,
    },
  },
  setup(props) {
    const show = ref(false);
    const optionsModel = ref(props.row[props.column.field]);

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

    onMounted(() => {
      // Filter the data before render
      if (props.filterData) {
        optionsModel.value = props.filterData(props.row, props.column, props.columns);
      }
    });

    return {
      show,
      optionsModel,
      onClose,
      onClickOption,
      onClick,
      getValueOption,
    };
  },
});
</script>
