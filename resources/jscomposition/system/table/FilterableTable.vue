<template>
  <BaseTable
    ref="baseTable"
    :columns="columns"
    :data="data"
    :placeholder="placeholder"
    :config="config"
    class="tw-grow"
    @stopResize="onStopResize">
    <template
      v-for="(column, index) in columns"
      #[`theader-filter-${column.field}`]>
      <FilterColumn
        v-if="column.filter"
        :id="column.field"
        :key="`default-${index}-${hasFilter(index,column)}`"
        :filter="column.filter"
        :value="getFilter(index, column)"
        @change="e=> onChangeFilter(column, e, index)"
        @clear="e=> onClear(column, e, index)"
        @resetTable="e=> onResetTable()" />
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

const emit = defineEmits(["changeFilter", "stopResize"]);

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
  config: {
    type: Object,
    default: () => {},
  },
});

const filters = ref([]);

const baseTable = ref(null);

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

const onResetTable = () => {
  filters.value = [];

  emit("resetFilters", filters.value);
};

const removeFilter = (index) => {
  filters.value.splice(index, 1);
};

const removeAllFilters = () => {
  filters.value = [];
};

const addFilters = (filtersValue) => {
  filters.value = filtersValue;
};

const getFilter = (index, column) => {
  const filter = filters.value.find((e) => e.field === column.field);
  return filter || null;
};

const hasFilter = (index, column) => {
  const filter = filters.value.findIndex((e) => e.field === column.field);
  return filter || "";
};

const getHeightTBody = () => baseTable.value.$el.clientHeight - baseTable.value.$refs.thead.clientHeight;

const getHeightThead = () => baseTable.value.$refs.thead.clientHeight;

const onStopResize = (column) => {
  emit("stopResize", column);
};

defineExpose({
  removeFilter,
  removeAllFilters,
  addFilters,
  getHeightTBody,
  getHeightThead,
});
</script>
