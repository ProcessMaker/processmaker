<template>
  <div
    class="tw-flex tw-justify-between tw-items-center
            tw-px-4 tw-py-3 tw-transition tw-duration-150 tw-ease-out
            hover:tw-ease-in tw-cursor-pointer tw-rounded-lg tw-text-gray-600"
    :class="[{
      [`tw-bg-${color}-100 hover:tw-bg-${color}-100`]:!active,
      [`tw-bg-${color}-100 tw-bg-${color}-100 hover:tw-bg-${color}-200`]:active
    }]"
    @click="handleClick">
    <div>
      <slot name="icon">
        <i
          :class="[`tw-text-lg`, icon]" />
      </slot>
      <slot name="header">
        <div class="tw-text-base">
          {{ header }}
        </div>
      </slot>
      <slot name="body">
        <div class="tw-text-xs">
          {{ body }}
        </div>
      </slot>
    </div>

    <slot name="content">
      <div class="tw-text-2xl tw-font-bold">
        {{ content }}
      </div>
    </slot>
  </div>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  props: {
    header: {
      type: String,
      default: () => "header",
    },
    body: {
      type: String,
      default: () => "body",
    },
    icon: {
      type: String,
      default: () => "",
    },
    active: {
      type: Boolean,
      default: () => false,
    },
    color: {
      type: String,
      default: () => "gray",
    },
    content: {
      type: String,
      default: () => "",
    },
  },
  setup(props, { emit }) {
    const handleClick = () => {
      emit("click");
    };
    return {
      handleClick,
    };
  },
});
</script>
