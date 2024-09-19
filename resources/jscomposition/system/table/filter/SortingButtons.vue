<template>
  <div class="tw-flex tw-justify-center tw-text-gray-500 tw-text-xs">
    <div
      class="tw-space-x-2 tw-inline-flex tw-items-center tw-rounded-l-md tw-px-3 tw-py-2 tw-ring-1 tw-ring-inset tw-ring-gray-300 hover:tw-bg-gray-100 focus:tw-z-10"
      @click="$emit('asc')">
      <i class="fas fa-sort-amount-up-alt"></i>

      <span>{{ "Sort Ascending" }}</span>
    </div>

    <div
      class="tw-space-x-2 -tw-ml-px tw-inline-flex tw-items-center tw-rounded-r-md tw-px-3 tw-py-2 tw-ring-1 tw-ring-inset tw-ring-gray-300 hover:tw-bg-gray-100 focus:tw-z-10"
      @click="$emit('desc')">
      <i class="fas fa-sort-amount-down"></i>

      <span>{{ "Sort Descending" }}</span>
    </div>
  </div>
</template>
<script>
import { defineComponent} from "vue";

export default defineComponent({
  setup(props, { emit }) {
    return {};
  },
});
</script>
