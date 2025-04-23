<template>
  <div
    class="tw-w-auto tw-flex tw-flex-col sm:tw-flex-row sm:tw-space-y-0 sm:tw-py-0">
    <PercentageCardButton
      v-for="(item, index) in data"
      :id="item.id"
      :key="index"
      :header="item.header"
      :body="item.body"
      :percentage="item.percentage"
      :style="{ width: `${item.percentage}%` }"
      :color="item.color"
      :icon="item.icon"
      :content="item.content"
      @click="onClick(item, index)" />
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import PercentageCardButton from "./PercentageCardButton.vue";

const props = defineProps({
  data: {
    type: Array({
      id: String,
      header: String,
      body: String,
      color: String,
      icon: String,
      percentage: Number,
    }),
    default: () => [],
  },
});

const emit = defineEmits(["change"]);

const onClick = (counter, idxCounter) => {
  emit("change", counter, idxCounter);
};

onMounted(() => {});

</script>
