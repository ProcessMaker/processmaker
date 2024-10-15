<template>
  <div class="tw-float-right tw-absolute tw-right-4 tw-top-0" />
</template>

<script setup>
const props = defineProps({
  columns: {
    type: Array,
    default: () => [],
  },
  row: {
    type: Object,
    default: () => {},
  },
});
</script>
