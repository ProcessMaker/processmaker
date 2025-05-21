<template>
  <div
    class="tw-w-auto tw-flex tw-flex-col sm:tw-flex-row tw-space-y-2 sm:tw-space-y-0 sm:tw-space-x-4 sm:tw-py-0">
    <BaseCardButton
      v-for="(item, index) in data"
      :id="item.id"
      :key="index"
      :header="item.header"
      :body="item.body"
      :active="item.active"
      :color="item.color"
      :class="`tw-w-full ${item.className}`"
      :icon="item.icon"
      :content="item.content"
      @click="onClick(item, index)" />
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
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

const dataModel = ref(props.data);

const onClick = (counter, idxCounter) => {
  const buttons = dataModel.value;

  buttons.forEach((item) => {
    if (item.id === counter.id) {
      item.active = true;
    } else {
      item.active = false;
    }
  });

  dataModel.value = buttons;
  emit("change", dataModel.value);
};

onMounted(() => {});

</script>
