<template>
  <span
    v-if="initials"
    class="tw-inline-flex tw-items-center tw-justify-center tw-h-6 tw-w-6
      tw-rounded-full tw-bg-gray-500 tw-ring-1 tw-ring-white"
    @click.prevent="$emit('click')">
    <span class="tw-text-xs tw-font-medium tw-leading-none tw-text-white">{{ initials }}</span>
  </span>

  <img
    v-else
    class="tw-relative tw-z-30 tw-inline-block tw-h-6 tw-w-6 tw-rounded-full tw-ring-1 tw-ring-white"
    src="https://images.unsplash.com/photo-1491528323818-fdd1faba62cc?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
    alt=""
    @click.prevent="$emit('click')">
</template>
<script>
import { defineComponent } from "vue";

export default defineComponent({
  props: {
    src: {
      type: String,
      default: () => null,
    },
    initials: {
      type: String,
      default: () => "NA",
    },
  },
  setup(props, { emit }) {
    return {};
  },
});
</script>
