<template>
  <div class="tw-relative tw-rounded-lg tw-shadow-sm tw-text-gray-500">
    <div class="tw-pointer-events-none tw-absolute tw-inset-y-0 tw-left-0 tw-flex tw-items-center tw-pl-3">
      <slot name="icon">
        <i :class="icon" />
      </slot>
    </div>
    <input
      v-model="data"
      class="tw-block tw-w-full tw-rounded-lg tw-border-0 tw-py-2 tw-pl-10
                tw-text-gray-900 tw-ring-1 tw-ring-inset tw-ring-gray-300
                placeholder:tw-text-gray-400 focus:tw-ring-2 focus:tw-ring-inset
                focus-visible:tw-outline-none
                sm:tw-text-sm sm:tw-leading-6"
      :placeholder="placeholder"
      @change="onChange"
      @keypress="onKeypress"
    >
  </div>
</template>
<script>
import { defineComponent, computed } from "vue";

export default defineComponent({
  props: {
    placeholder: {
      type: String,
      default: () => "",
    },
    icon: {
      type: String,
      default: () => ("fas fa-search"),
    },
    value: {
      type: String,
      default: () => null,
    },
  },
  emits: ["input", "keypress"],
  setup(props, { emit }) {
    const data = computed({
      get() {
        return props.value;
      },
      set(value) {
        emit("input", value);
      },
    });

    const onChange = (val) => {
      emit("change", val);
    };

    const onKeypress = (val) => {
      emit("keypress", val);
    };

    return {
      data,
      onChange,
      onKeypress,
    };
  },
});
</script>
