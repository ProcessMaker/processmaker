<template>
  <div
    class="
      tw-flex-1 tw-overflow-auto tw-rounded-xl tw-border tw-border-gray-200 tw-shadow-zinc-200
    "
  >
    <table class="tw-w-full tw-text-left tw-text-sm">
      <thead class="tw-bg-gray-50 tw-text-zinc-400">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            class="tw-px-6 tw-py-4 tw-font-medium tw-whitespace-nowrap"
          >
            {{ column.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(item, idx) in data"
          :key="idx"
          class="tw-border-t tw-border-zinc-200"
        >
          <td
            v-for="column in columns"
            :key="column.key"
            class="tw-px-6 tw-py-4 tw-text-gray-600 tw-border-b tw-border-gray-200 tw-whitespace-nowrap"
          >
            <slot
              :name="`cell-${column.key}`"
              :value="getRawValue(item, column)"
              :item="item"
              :column="column"
            >
              {{ getItemValue(item, column) }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  props: {
    columns: { type: Array, required: true },
    data: { type: Array, required: true },
  },
  methods: {
    getRawValue(item, column) {
      // Get raw value - handle dot-separated keys for nested properties
      return column.key.includes(".")
        ? column.key.split(".").reduce((val, part) => (val == null ? undefined : val[part]), item)
        : item[column.key];
    },
    getItemValue(item, column) {
      const value = this.getRawValue(item, column);

      // Apply format function if provided
      if (typeof column.format === "function") {
        return column.format(value);
      }

      return value;
    },
  },
};
</script>
