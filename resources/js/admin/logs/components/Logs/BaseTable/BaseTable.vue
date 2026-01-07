<template>
  <div
    class="
      tw-flex-1 tw-overflow-auto tw-rounded-xl tw-border tw-border-gray-200
      tw-shadow-md tw-shadow-zinc-200
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
            {{ getItemValue(item, column) }}
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
    getItemValue(item, column) {
      // if key is a dot-separated string, get the nested value from the item
      if (column.key.includes('.')) {
        return column.key.split('.').reduce((val, part) => (val != null ? val[part] : undefined), item);
      }

      if (typeof column.format === 'function') {
        return column.format(item[column.key]);
      }

      return item[column.key];
    },
  },
};
</script>

