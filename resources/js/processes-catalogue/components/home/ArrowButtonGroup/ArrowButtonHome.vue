<template>
  <div
    class="tw-flex tw-relative tw-rounded-xl"
    :class="[{
      [`tw-bg-${color}-200 hover:tw-bg-${color}-300`]:!active,
      [`tw-outline tw-bg-${color}-100 tw-border-${color}-500 tw-outline-${color}-300 tw-bg-${color}-100 hover:tw-bg-${color}-200`]:active
    }]"
    @click="onClick">
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
        <slot name="helper">
          <div
            v-if="helper"
            class="tw-text-sm tw-truncate">
            {{ helper }}
          </div>
        </slot>
      </div>
    </div>
  </div>
</template>

<script setup>

const props = defineProps({
  header: {
    type: [String, Number],
    required: true,
  },
  body: {
    type: [String, Number],
    required: true,
  },
  helper: {
    type: [String, Number, null],
    required: false,
  },
  active: {
    type: Boolean,
    default: false,
  },
  color: {
    type: String,
    default: "orange",
  },
});

const emit = defineEmits(["click"]);

const onClick = () => {
  emit("click");
};
</script>

<style scoped>
.clip-path-triangle-right {
  clip-path: polygon(-1% 0, 90% 50%, -1% 100%);
}
</style>
