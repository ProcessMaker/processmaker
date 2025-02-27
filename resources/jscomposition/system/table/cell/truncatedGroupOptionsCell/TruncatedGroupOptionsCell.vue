<template>
  <div class="tw-flex tw-relative tw-text-nowrap tw-whitespace-nowrap tw-p-3">
    <div
      v-if="optionsModel.length && optionsModel[0]?.options"
      class="tw-overflow-hidden tw-text-ellipsis">
      <a
        v-if="href !== null"
        class="hover:tw-text-blue-400 tw-text-gray-500"
        :href="href(optionsModel[0].options[0])">
        {{ getValueOption(optionsModel[0].options[0]) }}
      </a>
      <span
        v-else
        class="hover:tw-text-blue-400 hover:tw-cursor-pointer"
        href="#"
        @click.prevent.stop="onClickOption(optionsModel[0].options[0])">
        {{ getValueOption(optionsModel[0].options[0]) }}
      </span>
    </div>
    <AppPopover
      v-if="optionsModel.length"
      v-model="show"
      :hover="false"
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
              :option="optionTitle"
              :columns="columns"
              :row="row"
              :column="column" />

            <ul
              class="tw-list-disc tw-list-inside">
              <BaseOption
                v-for="(option, index) in optionTitle.options"
                :key="index"
                :formatter-options="formatterOptions"
                :option="option"
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
import { defineComponent, ref, onMounted } from "vue";
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
      if (props.formatData) {
        optionsModel.value = props.formatData(props.row, props.column, props.columns);
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
