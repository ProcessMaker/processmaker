<template>
  <BaseTable
    :key="indexFilter"
    :columns="columns"
    :data="data"
    class="tw-grow tw-overflow-y-scroll">
    <template
      v-for="(column, index) in columns"
      #[`theader-filter-${column.field}`]>
      <SortableFilter
        v-if="column.filter"
        :key="`sortable-${index}`"
        :state="indexFilter == index? 'asc': ''"

        @change="e=> onChangeFilter(column, e, index)" />
    </template>
  </BaseTable>
</template>
<script setup>
import { ref } from "vue";
import { BaseTable } from "../../base";
import { SortableFilter } from "./filter/sortableFilter/index";

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
});

const indexFilter = ref(-1);

const onChangeFilter = (columm, val, index) => {
  indexFilter.value = index;
  
  emit("changeFilter", {
    field: columm.field,
    filter: val,
  });
};
</script>
