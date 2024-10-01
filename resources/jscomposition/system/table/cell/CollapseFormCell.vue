<template>
  <div class="tw-flex tw-justify-center">
    <button
      type="button"
      class="tw-rounded-lg tw-bg-white tw-px-2.5 tw-py-1 tw-text-sm tw-font-semibold tw-text-gray-900
        tw-shadow-sm tw-ring-1 tw-ring-inset tw-ring-gray-300 hover:tw-bg-gray-50"
      @click="showScreen"
    >
      <i
        class="fas"
        :class="viewScreen ? 'fa-angle-down' : 'fa-angle-right'"
      />
    </button>
  </div>
</template>

<script>
import { defineComponent, ref } from "vue";

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
  },
  setup(props, { emit }) {
    const viewScreen = ref(false);

    const showScreen = () => {
      viewScreen.value = !viewScreen.value;
      emit("collapseContainer", viewScreen.value);
    };

    return {
      viewScreen,
      showScreen,
      color: "red",
      label: "fabio",
    };
  },
});
</script>
