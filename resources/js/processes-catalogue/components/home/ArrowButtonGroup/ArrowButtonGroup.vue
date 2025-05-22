<template>
  <div
    class="tw-flex tw-flex-row">
    <ArrowButton
      v-for="(item, index) in dataModel"
      :id="item.id"
      :key="`${index}-${item.active}`"
      :header="item.header"
      :body="item.body"
      :float="item.float"
      :color="`${[`tw-bg-${color || item.color}-${(index+1) * 100}`]}
      ${item.active ? `tw-border-b-4 ${'tw-border-' + activeColor}-500` : ''}`"
      :style="{ width: `${100 / data.length}%` }"
      :class="`first:tw-rounded-l-xl
        last:tw-rounded-r-xl
        hover:tw-cursor-pointer
        last:tw-overflow-hidden`"
      @click="click(dataModel[index], index)" />
  </div>
</template>

<script setup>
import { ref } from "vue";
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
  activeColor: {
    type: String,
    default: "orange",
  },
});

const emit = defineEmits(["change"]);

const dataModel = ref(props.data);

const click = (counter, idxCounter) => {
  const model = dataModel.value;

  model.forEach((item) => {
    if (item.id === counter.id) {
      item.active = true;
    } else {
      item.active = false;
    }
  });

  dataModel.value = model;
  emit("change", dataModel.value);
};
</script>

<style>
.tw-bg-tangerine-100 {
  background-color: rgb(255, 230, 213);
}

.tw-bg-tangerine-200 {
  background-color: rgb(252, 213, 186);
}

.tw-bg-tangerine-300 {
  background-color: rgb(255, 202, 165);
}

.tw-bg-tangerine-400 {
  background-color: rgb(255, 191, 145);
}

.tw-bg-tangerine-500 {
  background-color: rgb(255, 179, 124);
}

.tw-bg-tangerine-600 {
  background-color: rgb(255, 170, 109);
}

.tw-bg-tangerine-700 {
  background-color: rgb(255, 160, 92);
}

.tw-bg-tangerine-800 {
  background-color: rgb(254, 149, 74);
}

.tw-bg-tangerine-900 {
  background-color: rgb(253, 138, 56);
}
</style>
