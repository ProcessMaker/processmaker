<template>
  <div
    class="tw-flex tw-transition-all tw-duration-500 tw-pl-2"
    :class="{'!tw-w-0': collapse}"
  >
    <slot name="left">
      <div
        class="tw-relative tw-group tw-w-4 tw-border-0 tw-border-l tw-border-solid tw-border-l-[#dee0e1]
        hover:tw-border-l hover:tw-border-l-[#a6c7fd]"
      >
        <div class="tw-transition tw-duration-250 tw-w-[2px] tw-h-full">
          <div
            :class="{
              'tw-visible': collapse,
              'tw-invisible': !collapse,
            }"
            class="hover:tw-cursor-pointer tw-z-10 tw-h-[60px]
            tw-transition-all tw-duration-250 group-hover:tw-ease-in
            tw-flex tw-justify-center tw-items-center
            tw-border hover:tw-bg-gray-200 tw-bg-white group-hover:tw-visible
            tw-absolute tw-left-[-12px] tw-top-[41px] tw-w-[20px] tw-rounded-full
            tw-text-base tw-border-solid tw-border-[#dee0e1]"
            @click.prevent="onClick"
          >
            <i
              class="fa"
              :class="{'fa-caret-right': !collapse, 'fa-caret-left': collapse}"
            />
          </div>
        </div>
      </div>
    </slot>
    <div
      class="tw-flex tw-w-full tw-transition-all tw-duration-500"
      :class="{
        'tw-opacity-0': collapse,
        'tw-opacity-100': !collapse,
      }"
    >
      <slot name="default" />
    </div>
    <slot name="right" />
  </div>
</template>

<script setup>
import { computed } from "vue";

const emit = defineEmits(["change", "input"]);

const props = defineProps({
  value: {
    type: Boolean,
    default: true,
  },
});

const collapse = computed({
  get() {
    return props.value;
  },
  set(val) {
    emit("input", val);
  },
});

const onClick = () => {
  const val = !collapse.value;
  collapse.value = val;
  emit("change", val);
};
</script>
