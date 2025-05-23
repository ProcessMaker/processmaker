<template>
  <div
    class="tw-text-nowrap tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis tw-p-3">
    <span @click="onClick">
      <i
        :class="{
          'far fa-flag': active,
          'fas fa-flag': !active,
        }" />
    </span>
  </div>
</template>
<script setup>
import { defineProps, computed } from "vue";

const props = defineProps({
  columns: {
    type: Array,
    default: () => [],
  },
  column: {
    type: Object,
    default: () => ({}),
  },
  row: {
    type: Object,
    default: () => ({}),
  },
  active: {
    type: Function,
    default: new Function(),
  },
  click: {
    type: Function,
    default: new Function(),
  },
});

const active = computed(() => props.active(props.row, props.column, props.columns));

const onClick = () => {
  props.click && props.click(!active.value, props.row, props.column, props.columns);
};
</script>
