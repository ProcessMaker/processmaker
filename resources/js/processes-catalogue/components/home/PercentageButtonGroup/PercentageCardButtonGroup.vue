<template>
  <div
    class="tw-w-auto tw-flex tw-flex-row">
    <div
      v-for="(item, index) in data"
      :key="index"
      :style="{ width: `${item.percentage}%` }"
      :class="`first:tw-rounded-l-lg
        last:tw-rounded-r-lg
        first:tw-overflow-hidden
        last:tw-overflow-hidden`"
      class="tw-w-auto tw-flex tw-flex-col sm:tw-space-y-0 sm:tw-py-0">
      <PercentageCardButton
        :id="item.id"
        :header="item.header"
        :body="item.body"
        :percentage="item.percentage"
        :color="item.color"
        :icon="item.icon"
        :content="item.content"
        @click="onClick(item, index)" />
      <div
        v-if="item.subpercentage"
        class="tw-flex">
        <div
          :style="{ width: `${item.subpercentage}%` }"
          class="tw-h-2 tw-bg-blue-400" />
        <div
          class="tw-h-2 tw-w-full tw-bg-orange-400" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import PercentageCardButton from "./PercentageCardButton.vue";

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
    props: {
      id: String,
      header: String,
      body: String,
      color: String,
      icon: String,
      percentage: Number,
    },
  },
});

const emit = defineEmits(["change"]);

const onClick = (counter, idxCounter) => {
  emit("change", counter, idxCounter);
};

onMounted(() => {});

</script>
