<template>
  <div
    class="tw-flex tw-transition-all tw-duration-500"
    :class="{ 'tw-w-0' : collapse}">
    <slot name="left">
      <div class="tw-relative tw-group tw-w-3">
        <div class="tw-transition tw-duration-250 tw-w-[2px] tw-h-full">
          <div
            :class="{
              'tw-visible' : collapse,
              'tw-invisible' : !collapse,
            }"
            class="hover:tw-cursor-pointer tw-z-10
            tw-transition-all tw-duration-250 group-hover:tw-ease-in
            tw-flex tw-justify-center tw-items-center tw-text-[8px]
            tw-border tw-border-gray-300 hover:tw-bg-gray-200 tw-bg-white group-hover:tw-visible
            tw-absolute tw-left-[-12px] tw-top-[41px] tw-w-6 tw-h-6 tw-rounded-full"
            @click.prevent="onClick">
            <i
              class="fa"
              :class="{ 'fa-chevron-right' : !collapse, 'fa-chevron-left' : collapse }" />
          </div>
        </div>
      </div>
    </slot>

    <div
      class="tw-flex tw-w-full tw-transition-all tw-duration-500"
      :class="{
        'tw-opacity-0' : collapse,
        'tw-opacity-100' : !collapse,
      }">
      <slot
        name="default" />
    </div>

    <slot name="right" />
  </div>
</template>

<script setup>
import { ref } from "vue";

const collapse = ref(false);

const onClick = () => {
  collapse.value = !collapse.value;
};
</script>
