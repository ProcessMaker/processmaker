<template>
  <div
    class="tw-w-auto tw-flex tw-flex-col sm:tw-flex-row tw-space-y-2 sm:tw-space-y-0 sm:tw-space-x-4 sm:tw-py-0">
    <BaseCardButton
      v-for="(item, index) in data"
      :id="item.id"
      :key="index"
      :header="item.header"
      :body="item.body"
      :active="index === active"
      :class="`tw-w-full ${item.className}`"
      :icon="item.icon"
      :content="item.content"
      @click="onClick(item, index)" />
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import BaseCardButton from "./BaseCardButton.vue";

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
    props: {
      id: String,
      header: String,
      body: String,
      icon: String,
    },
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
