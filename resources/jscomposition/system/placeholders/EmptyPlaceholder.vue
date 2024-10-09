<template>
  <div class="tw-flex tw-flex-col tw-justify-center tw-items-center tw-grow">
    <div>
      <img
        src="/img/all-cleared.svg"
        class="no-results-img"
        alt="no-results">
    </div>
    <span class="tw-pt-4"> {{ title }}</span>

    <span class="tw-pt-2 tw-text-xl"> {{ subtitle }}</span>
  </div>
</template>

<script setup>

const props = defineProps({
  title: {
    type: String,
    default: () => "",
  },
  subtitle: {
    type: String,
    default: () => "",
  },
});
</script>
