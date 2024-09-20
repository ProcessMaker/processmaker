<template>
  <button
    type="button"
    :class="`tw-flex tw-rounded-lg tw-border tw-border-${color}-300 tw-text-${color}-500
      tw-space-x-2 tw-px-3 tw-py-3 hover:tw-bg-${color}-200 tw-justify-center tw-items-center`"
    @click.prevent.stop="onClick">
    <slot />
  </button>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  props: {
    color: {
      type: String,
      default: () => "gray",
    },
  },
  emits: ["click"],
  setup(props, { emit }) {
    const onClick = () => {
      emit("click");
    };
    return {
      onClick,
    };
  },
});
</script>
