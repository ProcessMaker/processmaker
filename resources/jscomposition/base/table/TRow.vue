<template>
  <tr
    class="tw-border-b tw-relative"
    @mouseover="menu = true"
    @mouseleave="menu = false"
  >
    <slot :name="`cell`" />
    <template v-if="menu">
      <slot :name="`menu`" />
    </template>
  </tr>
</template>

<script>
import { defineComponent, ref } from "vue";

export default defineComponent({
  setup() {
    const menu = ref(false);
    return {
      menu,
    };
  },
});
</script>
