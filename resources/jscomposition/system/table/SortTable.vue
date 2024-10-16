<template>
  <BaseTable
    :key="indexFilter"
    :columns="columns"
    :data="data"
    :config="config"
    :placeholder="placeholder"
    class="tw-grow tw-overflow-y-scroll">
    <template
      v-for="(column, index) in columns"
      #[`theader-filter-${column.field}`]>
      <SortableFilter
        v-if="column.filter"
        :key="`sortable-${index}`"
        :state="indexFilter == index ? 'asc': ''"
        @change="e=> onChangeFilter(column, e, index)" />
    </template>

    <template
      v-for="(item, indexRow) in data"
      #[`container-row-${indexRow}`]>
      <slot
        :name="`container-row-${indexRow}`" />
    </template>

    <template
      v-for="(item, index) in data"
      #[`ellipsis-menu-${index}`]="{row, columns}">
      <slot
        :name="`ellipsis-menu-${index}`"
        :row="row"
        :columns="columns" />
    </template>

    <template #placeholder>
      <slot name="placeholder" />
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
  placeholder: {
    type: Boolean,
    default: () => false,
  },
  config: {
    type: Object,
    default: () => ({}),
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
