<template>
  <BaseTable
    :columns="columns"
    :data="data"
    :placeholder="placeholder"
    class="tw-overflow-y-scroll">
    <template
      v-for="(column, index) in columns"
      #[`theader-filter-${column.field}`]>
      <FilterColumn
        v-if="column.filter"
        :key="`default-${index}-${hasFilter(index,column)}`"
        :filter="column.filter"
        :value="getFilter(index, column)"
        @change="e=> onChangeFilter(column, e, index)"
        @clear="e=> onClear(column, e, index)" />
    </template>
    <template #placeholder>
      <slot name="placeholder" />
    </template>
  </BaseTable>
</template>
<script setup>
import { ref } from "vue";
import { BaseTable } from "../../base";
import { FilterColumn } from "./filter/defaultFilter/index";

const emit = defineEmits(["changeFilter"]);

const props = defineProps({
  columns: {
    type: Array,
    default: () => [],
  },
  data: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: Boolean,
    default: () => false,
  },
});

const filters = ref([]);

const onChangeFilter = (column, val, index) => {
  // All filter with sortable are reset to null
  if (val.sortable) {
    filters.value.forEach((e) => {
      e.sortable = null;
    });
  }

  // Searching the filter by fieldName
  const indexFilter = filters.value.findIndex((e) => e.field === column.field);

  // Removing the filter
  indexFilter !== -1 && (filters.value.splice(indexFilter, 1));

  // Add the filter to the array
  filters.value.push({
    ...val,
    field: column.field,
  });

  emit("changeFilter", filters.value);
};

const onClear = (column, val, index) => {
  const indexFilter = filters.value.findIndex((e) => e.field === column.field);

  indexFilter !== -1 && (filters.value.splice(indexFilter, 1));

  emit("changeFilter", filters.value);
};

const removeFilter = (index) => {
  filters.value.splice(index, 1);
};

const getFilter = (index, column) => {
  const filter = filters.value.find((e) => e.field === column.field);
  return filter || null;
};

const hasFilter = (index, column) => {
  const filter = filters.value.findIndex((e) => e.field === column.field);
  return filter || "";
};

defineExpose({
  removeFilter,
});
</script>
