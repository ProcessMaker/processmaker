<template>
  <div class="tw-flex tw-justify-center">
    <span
      :class="`tw-inline-flex tw-items-center tw-text-xs tw-border-1 tw-rounded-lg
        tw-bg-${color}-100 tw-px-2 tw-py-1 tw-text-${color}-500`">
      {{ label }}
    </span>
  </div>
</template>
<script>
import { defineComponent, computed } from "vue";

export const statuses = {
  DRAFT: {
    color: "yellow",
    label: "Draft",
  },
  CANCELED: {
    color: "red",
    label: "Canceled",
  },
  COMPLETED: {
    color: "blue",
    label: "Completed",
  },
  ERROR: {
    color: "red",
    label: "Error",
  },
  IN_PROGRESS: {
    color: "green",
    label: "In progress",
  },
};

export default defineComponent({
  props: {
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
  },
  setup(props, { emit }) {
    const color = computed(() => {
      return (
        statuses[props.row[props.column.field]].color || statuses.IN_PROGRESS.color
      );
    });

    const label = computed(() => {
      return (
        statuses[props.row[props.column.field]].label || statuses.IN_PROGRESS.label
      );
    });

    return {
      color,
      label,
    };
  },
});
</script>
