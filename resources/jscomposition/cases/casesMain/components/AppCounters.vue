<template>
  <div
    class="tw-w-auto tw-flex tw-flex-col sm:tw-flex-row tw-space-y-2 sm:tw-space-y-0 sm:tw-space-x-4 sm:tw-py-0">
    <ThreeSectionCard
      v-for="(counter, index) in data"
      :key="index"
      :header="counter.header"
      :body="counter.body"
      :active="index === active"
      class="tw-w-full"
      :color="counter.color"
      :icon="counter.icon"
      :id="counter.id"
      @click="onClick(counter, index)" />
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import { ThreeSectionCard } from "../../../base/index";

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
  active: {
    type: Number,
    default: () => 0,
  },
});

const emit = defineEmits(["change"]);

const onClick = (counter, idxCounter) => {
  emit("change", counter, idxCounter);
};

onMounted(() => {});

</script>
