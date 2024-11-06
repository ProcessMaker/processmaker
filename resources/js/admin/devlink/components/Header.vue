<script setup>
import { useRouter, defineProps } from 'vue-router/composables';
const router = useRouter();
const props = defineProps({
  back: {
    type: String,
    required: true,
  }
});
</script>

<template>
  <div class="d-flex align-items-center mb-2 mt-2">
      <a
      class="float-left pr-3"
      style="font-size: 1.5em;"
      href="#"
      @click.prevent="router.push({ name: props.back })"
    >
      <i
        class="fa fa-chevron-left"
        aria-hidden="true"
      ></i>
    </a>
      <h3>
        <slot></slot>
      </h3>
  </div>
</template>

<style lang="scss" scoped>
h3 {
  font-size: 1.4em;
  margin-bottom: 0;
}
</style>