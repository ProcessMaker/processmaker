<template>
  <div
    :class="`tw-flex tw-relative ${color}`"
    @click="click">
    <div class=" tw-flex tw-flex-col tw-w-full">
      <div class="tw-flex tw-flex-col tw-justify-start tw-px-6 tw-py-2 tw-gap-0">
        <slot name="header">
          <div class="tw-text-lg tw-font-bold tw-truncate">
            {{ header }}
          </div>
        </slot>
        <slot name="body">
          <div class="tw-text-sm tw-truncate tw-font-semibold">
            {{ body }}
          </div>
        </slot>
        <slot name="helper">
          <div class="tw-text-sm tw-truncate">
            {{ helper }}
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
  helper: {
    type: [String, Number],
    required: false,
  },
  color: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(["click"]);

const click = () => {
  emit("click");
};
</script>

<style scoped>
.clip-path-triangle-right {
  clip-path: polygon(-1% 0, 90% 50%, -1% 100%);
  position: relative;
}
</style>
