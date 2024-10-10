<template>
  <tr
    class="tw-border-b tw-relative"
    @mouseover="menu = true"
    @mouseleave="menu = false">
    <slot :name="`cell`" />
    <td
      v-if="menu"
      class="tw-sticky tw-w-0 tw-right-0 tw-top-0">
      <slot :name="`menu`" />
    </td>
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
