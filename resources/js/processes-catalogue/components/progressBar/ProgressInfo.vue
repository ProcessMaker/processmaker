<template>
  <div class="tw-flex tw-flex-row tw-gap-1 tw-full tw-py-2">
    <div class="tw-flex tw-justify-center">
      <div :class="`tw-flex tw-text-sm tw-bg-${color}-200 tw-text-${color}-700 tw-rounded-lg tw-font-bold tw-p-2`">
        {{ value }}
      </div>
    </div>

    <div class="tw-flex tw-w-full tw-flex-col">
      <span class="tw-text-sm tw-text-gray-500 tw-line-clamp-1">
        {{ title }}
      </span>
      <progress-bar
        :percentage="percentage"
        :color="color"
      />
    </div>
  </div>
</template>

<script setup>
import ProgressBar from "./ProgressBar.vue";

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: Number,
    required: true,
  },
  percentage: {
    type: Number,
    required: true,
  },
  color: {
    type: String,
    required: true,
    default: "green",
  },
});
</script>
