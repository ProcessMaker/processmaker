<template>
  <div class="tw-flex tw-flex-col tw-gap-4">
    <ProgressInfo
      v-for="stage in stagesSummary"
      :key="`stage-${stage.id}`"
      :title="stage.name"
      :value="Number(stage.count)"
      :color="getColor(stage)"
      :percentage="Number(stage.percentage)"
    />
  </div>
</template>

<script setup>
import { defineProps } from "vue";
import ProgressInfo from "./ProgressInfo.vue";

// Define props
const props = defineProps({
  stagesSummary: {
    type: Array,
    required: true,
  },
});

// Determine color based on stage
const getColor = (stage) => (stage.count === 0 ? "blue" : "green");
</script>
