<template>
  <div :class="`tw-flex tw-relative tw-w-full`">
    <div class=" tw-flex tw-flex-col tw-w-full">
      <div class="tw-flex tw-flex-col tw-justify-start tw-px-6 tw-py-3">
        <slot name="header">
          <div class="tw-text-xl tw-font-bold">
            {{ header }}
          </div>
        </slot>
        <slot name="body">
          <div class="tw-text-base">
            {{ body }}
          </div>
        </slot>
      </div>
    </div>
    <div
      :class="`tw-h-full tw-w-[20px] tw-absolute tw-bg-inherit
          tw-right-[-20px] clip-path-triangle-right tw-z-10`">
        &nbsp;
    </div>
  </div>
</template>

<script setup>

const props = defineProps({
  header: {
    type: String,
    required: true,
  },
  body: {
    type: String,
    required: true,
  },
  color: {
    type: String,
    required: true,
  },
});

</script>

<style scoped>
.clip-path-triangle-right {
  clip-path: polygon(-1% 0, 50% 50%, -1% 100%);
}
</style>
