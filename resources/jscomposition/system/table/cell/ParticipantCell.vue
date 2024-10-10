<template>
  <div class="tw-overflow-hidden tw-flex -tw-space-x-1 tw-items-center tw-justify-center">
    <AppPopover
      position="bottom"
      :hover="true">
      <AppAvatar
        :class="`tw-bg-gray-500`"
        :initials="initials(row, column, columns) || ''"
        class="tw-cursor-pointer"
        @click="onClick" />
      <template #content>
        <div class="tw-p-2 tw-rounded-lg tw-border tw-border-gray-300 tw-bg-white">
          {{ formatter(row, column, columns) || "" }}
        </div>
      </template>
    </AppPopover>

    <span
      class="tw-px-2">
      {{ formatter(row, column, columns) }}
    </span>
  </div>
</template>
<script setup>
import { AppAvatar, AppPopover } from "../../../base";

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
  click: {
    type: Function,
    default: new Function(),
  },
  formatter: {
    type: Function,
    default: new Function(),
  },
  initials: {
    type: Function,
    default: new Function(),
  },
});

const onClick = () => {
  props.click && props.click(props.row, props.column, props.columns);
};

</script>
