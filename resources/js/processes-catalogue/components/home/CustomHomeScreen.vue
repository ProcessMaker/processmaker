<template>
  <div class="tw-flex tw-flex-col tw-space-y-4 tw-h-full tw-w-full">
    <component
      :is="view"
      :process="process"
      @goBackCategory="emit('goBackCategory')" />
  </div>
</template>

<script setup>
import { computed } from "vue";
import TceDistributionStudent from "./TceDistributionStudent.vue";
import TceDistributionCollege from "./TceDistributionCollege.vue";
import TceDistributionGrants from "./TceDistributionGrants.vue";

const props = defineProps({
  process: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["goBackCategory"]);

const view = computed(() => {
  let screenId = 0;
  const unparseProperties = props.process?.launchpad?.properties || null;
  if (unparseProperties !== null) {
    screenId = JSON.parse(unparseProperties)?.screen_id || 0;
  }

  switch (screenId) {
    case "tce-student":
      return TceDistributionStudent;
    case "tce-college":
      return TceDistributionCollege;
    case "tce-grants":
      return TceDistributionGrants;
    default:
      return TceDistributionStudent;
  }
});
</script>
