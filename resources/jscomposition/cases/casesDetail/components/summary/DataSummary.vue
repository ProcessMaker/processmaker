<template>
  <div class="tw-w-full tw-h-fit tw-border tw-border-gray-300 tw-rounded-xl tw-overflow-hidden">
    <table
      class="tw-divide-y tw-w-full tw-text-sm ">
      <thead class="tw-bg-gray-100 tw-text-left  tw-font-semibold tw-border-b">
        <tr>
          <th
            scope="col"
            class="tw-py-3.5 tw-pl-4 tw-pr-3">
            <div>{{ $t('Key') }}</div>
          </th>
          <th
            scope="col"
            class="tw-px-3 tw-py-3">
            <div>{{ $t('Value') }}</div>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="row in formattedData"
          :key="row.key"
          class="tw-border-b tw-text-gray-500">
          <td
            class="tw-whitespace-nowrap tw-py-4 tw-pl-4"
            aria-colindex="1"
            role="cell">
            {{ row.key }}
          </td>
          <td
            class="tw-whitespace-nowrap tw-px-3 tw-py-4"
            aria-colindex="2"
            role="cell">
            {{ row.value }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

const props = defineProps({
  summary: {
    type: [Object, Array],
    required: true,
  },
});

const formattedData = ref([]);

const isObject = (item) => item && typeof item === "object";

const formatData = (prefix, items) => {
  Object.entries(items).forEach(([key, item]) => {
    if (isObject(item)) {
      formatData(`${prefix + key}.`, item);
    } else {
      formattedData.value.push({
        key: prefix + key,
        value: item,
      });
    }
  });
};

onMounted(() => {
  formatData("", props.summary);
});
</script>
