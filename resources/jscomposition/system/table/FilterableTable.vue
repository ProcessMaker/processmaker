<template>
  <BaseTable
    :columns="columns"
    :data="data"
    class="tw-grow tw-overflow-y-scroll">
    <template
      v-for="(column, index) in columns"
      #[`theader-filter-${column.field}`]>
      <FilterColumn
        v-if="column.filter"
        :key="`default-${index}`"
        :filter="column.filter" />
    </template>
  </BaseTable>
</template>
<script setup>
import { BaseTable } from "../../base";
import { FilterColumn } from "./filter/defaultFilter/index";

defineProps({
  columns: {
    type: Array,
    default: () => [],
  },
  data: {
    type: Array,
    default: () => [],
  },
});
</script>
