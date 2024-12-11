<template>
  <div class="tw-flex tw-justify-left">
    <span
      :class="`tw-inline-flex tw-items-center tw-text-xs tw-border-1 tw-rounded-lg
        tw-bg-${color}-100 tw-px-2 tw-py-1 tw-text-${color}-500`">
      {{ label }}
    </span>
  </div>
</template>
<script>
import { defineComponent, computed } from "vue";
import { t } from "i18next";

export const statuses = {
  DRAFT: {
    color: "red",
    label: `${t("Draft")}`,
  },
  CANCELED: {
    color: "red",
    label: `${t("Canceled")}`,
  },
  COMPLETED: {
    color: "blue",
    label: `${t("Completed")}`,
  },
  ERROR: {
    color: "red",
    label: `${t("Error")}`,
  },
  IN_PROGRESS: {
    color: "green",
    label: `${t("In Progress")}`,
  },
  ACTIVE: {
    color: "green",
    label: `${t("In Progress")}`,
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
