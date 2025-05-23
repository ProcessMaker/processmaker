<template>
  <div v-if="percentage > 0 && title !== ''">
    <span class="tw-text-[#3C3C3C] tw-font-sans tw-text-sm tw-font-normal tw-leading-normal tw-tracking-[-0.014em]">
      {{ title }}
    </span>
    <div :class="`tw-w-full tw-bg-${color}-200 tw-rounded-full`">
      <div
        :class="`tw-bg-${color}-500 tw-text-white tw-rounded-full tw-text-center tw-h-2`"
        role="progressbar"
        :style="{ width: percentage + '%' }"
        :aria-valuenow="percentage"
        aria-valuemin="0"
        aria-valuemax="100"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  percentage: {
    type: Number,
    required: true,
  },
  title: {
    type: String,
    required: true,
    default: "",
  },
});

const color = computed(() => {
  if (props.percentage < 100) {
    return "green";
  }
  return "blue";
});
</script>
