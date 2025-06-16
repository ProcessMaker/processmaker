<template>
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
</template>

<script setup>
const props = defineProps({
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
