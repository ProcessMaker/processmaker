<template>
  <div
    class="tw-flex tw-justify-between tw-items-center
            tw-p-4 tw-transition tw-duration-150 tw-ease-out
            hover:tw-ease-in tw-border tw-cursor-pointer tw-rounded-lg tw-text-gray-600"
    :class="[{
      [`tw-bg-${color}-100/50 hover:tw-bg-${color}-100`]:!active,
      [`tw-outline tw-bg-${color}-100 tw-border-${color}-500 tw-outline-${color}-300 tw-bg-${color}-100 hover:tw-bg-${color}-200`]:active
    }]"
    @click="handleClick"
  >
    <div>
      <slot name="header">
        <div class="tw-text-xs">
          {{ header }}
        </div>
      </slot>
      <slot name="body">
        <div class="tw-text-2xl">
          {{ body }}
        </div>
      </slot>
    </div>

    <slot name="icon">
      <i
        :class="[`tw-text-${color}-500 tw-text-3xl`, icon]"
      />
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
