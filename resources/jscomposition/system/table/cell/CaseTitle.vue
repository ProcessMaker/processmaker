<template>
  <div class="tw-overflow-hidden tw-text-ellipsis">
    <span
      v-if="title"
      v-html="title"
    />
    <span v-else>{{ defaultValue }}</span>
  </div>
</template>
<script>
import { defineComponent } from "vue";

export default defineComponent({
  props: {
    title: {
      type: String,
      default: "",
    },
    defaultValue: {
      type: String,
      default: "",
    },
  },
});
</script>
