<template>
  <input
    v-model="model"
    type="text"
    class="tw-px-2 tw-block tw-w-full tw-rounded-md tw-border-0 tw-py-2 tw-text-gray-900
      tw-shadow-sm tw-ring-1 tw-ring-inset tw-ring-gray-300 placeholder:tw-text-gray-400
      focus:tw-ring-2 focus:tw-ring-inset focus:tw-ring-indigo-600"
    @change="$emit('change', $event.target.value)">
</template>
<script setup>
import { ref } from "vue";

const props = defineProps({
  value: {
    type: String,
    default: () => "",
  },
});

const model = ref(props.value);
</script>
