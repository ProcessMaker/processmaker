<template>
  <div class="tw-flex tw-relative tw-text-nowrap tw-whitespace-nowrap tw-p-3">
    <div class="tw-overflow-hidden tw-text-ellipsis ">
      {{ row[column.field][0].name }}
    </div>

    <AppPopover
      v-model="show"
      :hover="false"
      position="bottom"
      class="!tw-absolute tw-right-0 tw-top-0  tw-h-full tw-flex tw-items-center">
      <div
        class="tw-self-center tw-px-2 tw-rounded-md hover:tw-cursor-pointer hover:tw-bg-gray-200 tw-bg-white "
        @click.prevent.stop="onClick">
        <i class="fas fa-ellipsis-v" />
      </div>

      <template #content>
        <ul
          class="tw-bg-white tw-list-none tw-text-gray-600
            tw-overflow-hidden tw-rounded-lg tw-w-50 tw-text-sm tw-border tw-border-gray-300">
          <li
            v-for="(option, index) in optionsModel"
            :key="index"
            class="hover:tw-bg-gray-100"
            @click.prevent.stop="onClickOption(option, index)">
            <span class="tw-flex tw-py-2 tw-px-4 transition duration-300 hover:tw-bg-gray-200 hover:tw-cursor-pointer">
              {{ option.name || option.id }}
            </span>
          </li>
        </ul>
      </template>
    </AppPopover>
  </div>
</template>
<script>
import { defineComponent, ref } from "vue";
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
  },
  setup(props) {
    const show = ref(false);
    const optionsModel = ref(props.row[props.column.field]);

    const onClick = () => {
      show.value = !show.value;
    };

    const onClickOption = (option, index) => {
      props.click && props.click(option, props.row, props.column, props.columns);
    };

    const onClose = () => {
      show.value = false;
    };

    return {
      show,
      optionsModel,
      onClose,
      onClickOption,
      onClick,
    };
  },
});
</script>
