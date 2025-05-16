<template>
  <div
    class="tw-flex tw-flex-row">
    <ArrowButton
      v-for="(item, index) in data"
      :id="item.id"
      :key="index"
      :header="item.header"
      :body="item.body"
      :float="item.float"
      :color="`${[`tw-bg-${color || item.color}-${(index+1) * 100}`]}`"
      :style="{ width: `${100 / data.length}%` }"
      :class="`first:tw-rounded-l-xl
        last:tw-rounded-r-xl
        last:tw-overflow-hidden`"
      @click="onClick(item, index)" />
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import ArrowButton from "./ArrowButton.vue";

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
    props: {
      id: String,
      header: String,
      body: String,
      color: String,
      float: String,
    },
  },
  color: {
    type: String,
    default: "orange",
  },
});

const emit = defineEmits(["change"]);

const onClick = (counter, idxCounter) => {
  emit("change", counter, idxCounter);
};
</script>
