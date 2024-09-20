<template>
  <BaseTable
    :columns="columns"
    :data="data"
    class="tw-grow tw-overflow-y-scroll">
    <template
      v-for="(column, index) in columns"
      v-slot:[`theader-filter-${column.field}`]>
      <FilterColumn
        v-if="column.filter"
        :key="index"
        :filter="column.filter"/>
    </template>
  </BaseTable>
</template>
<script>
import { defineComponent } from "vue";
import { BaseTable } from "../../base";
import FilterColumn from "./filter/FilterColumn.vue";

export default defineComponent({
  components: {
    BaseTable,
    FilterColumn,
  },
  props: {
    columns: {
      type: Array,
      default: () => [],
    },
    data: {
      type: Array,
      default: () => [],
    },
  },
  setup() {
    return {};
  },
});
</script>
