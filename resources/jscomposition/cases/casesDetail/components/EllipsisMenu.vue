<template>
  <td class="tw-sticky tw-w-0 tw-right-0 tw-top-0">
    <div class="tw-float-right tw-relative tw-right-4">
      <button
        v-for="item in menuOptions"
        :key="item.menu"
        type="button"
        class="tw-rounded-lg tw-bg-white tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-gray-600 tw-shadow-sm
        tw-ring-1 tw-ring-gray-300 hover:tw-bg-gray-50 tw-gap-2"
        @click="executeCallback(item)"
      >
        <i
          class="tw-text-xl tw-text-gray-600"
          :class="item.icon"
        />
      </button>
    </div>
  </td>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  props: {
    data: {
      type: Object,
      required: true,
    },
  },
  setup(props) {
    const onPrint = () => {
      const { data } = props;
      window.open(`/requests/${data.case_number}/task/${data.id}/screen/${data.screen_id}`);
    };

    const menuOptions = [
      {
        icon: "fa fa-print",
        callback: onPrint,
        name: "print",
      },
    ];

    const executeCallback = (item) => item.callback();

    return {
      menuOptions,
      executeCallback,
      onPrint,
    };
  },
});
</script>
