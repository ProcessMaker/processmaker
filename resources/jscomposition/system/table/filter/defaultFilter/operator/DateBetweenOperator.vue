<template>
  <div class="tw-flex tw-flex-col tw-space-y-2">
    <input
      v-for="(element, index) in inputs"
      :key="index"
      v-model="inputs[index].value"
      :placeholder="placeholder"
      type="datetime-local"
      class="tw-p-2 tw-rounded-md tw-border-0
        tw-ring-1 tw-ring-inset tw-ring-gray-300 placeholder:tw-text-gray-400
        focus:tw-ring-2 focus:tw-ring-inset"
      @change="onChange()">
  </div>
</template>
<script setup>
import { ref } from "vue";

const props = defineProps({
  placeholder: {
    type: String,
    default: () => ("value"),
  },
  value: {
    type: Array,
    default: () => null,
  },
});

const emit = defineEmits(["change"]);

const inputs = ref(props.value || [
  {
    id: "from",
    value: null,
  },
  {
    id: "to",
    value: null,
  },
]);

const onChange = () => {
  emit("change", inputs.value);
};
</script>
