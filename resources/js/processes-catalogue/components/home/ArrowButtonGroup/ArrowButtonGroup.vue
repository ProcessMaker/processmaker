<template>
  <div
    class="tw-w-auto tw-flex tw-flex-row"
  >
    <ArrowButton
      v-for="(item, index) in data"
      :id="item.id"
      :key="index"
      :header="item.header"
      :body="item.body"
      :color="item.color"
      :style="{'opacity': item.opacity}"
      :class="{
        [`tw-bg-${item.color}-${(index+1) * 100}`]: true,
        'first:tw-rounded-l-xl last:tw-rounded-r-xl last:tw-overflow-hidden': true
      }"
      @click="onClick(item, index)"
    />
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import ArrowButton from "./ArrowButton.vue";

const props = defineProps({
  data: {
    type: Array({
      id: String,
      header: String,
      body: String,
      color: String,
    }),
    default: () => [],
  },
  color: {
    type: String,
    default: "amber",
  },
});

const emit = defineEmits(["change"]);

const onClick = (counter, idxCounter) => {
  emit("change", counter, idxCounter);
};

onMounted(() => {
  if (props.color) {
    props.data.forEach((item) => {
      item.color = props.color;
    });
  }
});
</script>
