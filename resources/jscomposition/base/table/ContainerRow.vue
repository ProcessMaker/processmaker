<template>
  <tr
    v-if="showRow"
    class="tw-border-b">
    <td :colspan="columns.length">
      <slot />
    </td>
  </tr>
</template>

<script setup>
const props = defineProps({
  showRow: {
    type: Boolean,
    default: () => false,
  },
  columns: {
    type: Array,
    default: () => [],
  },
});
</script>
