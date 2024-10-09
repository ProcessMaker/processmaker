<template>
  <tr
    v-if="showRow"
    class="tw-border-b"
  >
    <td :colspan="columns.length">
      <slot />
    </td>
  </tr>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  props: ["showRow", "columns"],
  setup(props) {
    return {
      view: props.showRow,
    };
  },
});
</script>
