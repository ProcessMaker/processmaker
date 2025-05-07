<template>
  <div :class="`tw-flex tw-relative ${color}`">
    <div class=" tw-flex tw-flex-col tw-w-full">
      <div class="tw-flex tw-flex-col tw-justify-start tw-px-6 tw-py-4 tw-gap-0">
        <slot name="header">
          <div class="tw-text-lg tw-font-bold tw-truncate">
            {{ body }}
          </div>
        </slot>
        <slot name="body">
          <div class="tw-text-sm tw-truncate">
            {{ header }}
          </div>
        </slot>
        <slot name="float">
          <div class="tw-absolute tw-text-xs tw-right-1 tw-top-2 tw-rounded-full tw-bg-white tw-px-2 tw-py-1">
            {{ float }}
          </div>
        </slot>
      </div>
    </div>
    <div
      :class="`tw-h-full tw-w-[20px] tw-absolute
          tw-right-[-20px] tw-z-10`">
      <div :class="`tw-h-full tw-w-[20px] clip-path-triangle-right ${color}`">
        &nbsp;
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
  float: {
    type: String,
    required: false,
  },
  color: {
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
