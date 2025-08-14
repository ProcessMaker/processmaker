<template>
  <div v-if="percentage > 0 && title !== ''">
    <span class="tw-text-[#3C3C3C] tw-font-sans tw-text-sm tw-font-normal tw-leading-normal tw-tracking-[-0.014em]">
      {{ title }}
    </span>
    <ProgressBar
      :percentage="percentage"
      :color="color"
    />
  </div>
</template>

<script setup>
import { computed } from "vue";
import { ProgressBar } from "../../../system";

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
