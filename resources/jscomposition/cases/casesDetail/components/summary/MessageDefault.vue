<template>
  <div
    class="tw-flex tw-border-l-4 tw-border-gray-400 tw-bg-gray-50
      tw-p-4 tw-h-fit tw-w-full">
    <div class="tw-ml-3">
      <p class="tw-text tw-font-bold">
        {{ title }}
      </p>

      <p class="tw-text-sm">
        {{ message }}
      </p>
    </div>
  </div>
</template>
<script setup>
const props = defineProps({
  title: {
    type: String,
    required: false,
  },
  message: {
    type: String,
    required: false,
  },
});
</script>
