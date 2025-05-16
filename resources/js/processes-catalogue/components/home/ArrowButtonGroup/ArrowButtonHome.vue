<template>
  <div :class="`tw-flex tw-relative tw-rounded-xl`">
    <div class=" tw-flex tw-flex-col tw-w-full">
      <div class="tw-flex tw-flex-col tw-justify-start tw-px-6 tw-py-4 tw-gap-0">
        <slot name="header">
          <div class="tw-text-lg tw-font-bold tw-truncate">
            {{ header }}
          </div>
        </slot>
        <slot name="body">
          <div class="tw-text-sm tw-truncate">
            {{ body }}
          </div>
        </slot>
      </div>
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
});

</script>

<style scoped>
.clip-path-triangle-right {
  clip-path: polygon(-1% 0, 90% 50%, -1% 100%);
}
</style>
