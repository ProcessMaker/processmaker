<template>
  <div>
    <slot name="top-content"></slot>

    <FilterTable ref="filterTable"
                 :headers="headers"
                 :data="data"
                 @table-row-mouseover="tableRowMouseover"
                 @table-tr-mouseleave="(row, rowIndex) => $emit('onTrMouseleave', row, rowIndex)">
      <template v-for="header in headers" v-slot:[getSlotName(header.field)]="slotProps">
        <slot :name="'cell-' + header.field" v-bind="slotProps"></slot>
      </template>
    </FilterTable>

    <data-loading v-show="shouldShowLoader"
                  :for="/requests\?page|results\?page/"
                  :empty="$t('No results have been found')"
                  :empty-desc="$t('We apologize, but we were unable to find any results that match your search. Please consider trying a different search. Thank you')"
                  empty-icon="noData">
    </data-loading>

    <pagination-table :meta="data.meta"
                      @page-change="changePage">
    </pagination-table>
  </div>
</template>

<script>
  import FilterTable from "./shared/FilterTable.vue";
  import PaginationTable from "./shared/PaginationTable.vue";
  import DataLoading from "./common/DataLoading.vue";
  export default {
    components: {
      FilterTable,
      DataLoading,
      PaginationTable
    },
    props: {
      headers: null,
      data: null
    },
    data() {
      return {
        shouldShowLoader: false,
        changePage: () => {
        }
      };
    },
    methods: {
      tableRowMouseover(row, rowIndex) {
        let container = this.$refs.filterTable.$el;
        let scrolledWidth = container.scrollWidth - container.clientWidth;
        this.$emit('onRowMouseover', row, scrolledWidth, rowIndex);
      },
      getSlotName(field) {
        // need to use this method because prefixing text to the
        // dynamic slot name doesn't work in the template
        return `cell-${field}`;
      }
    }
  }
</script>

<style>
</style>
<style scoped>
  .search-bar {
    border: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: 3px;
    background: #ffffff;
    &:hover {
      background-color: #fafbfc;
      border-color: #cdddee;
    }
  }
</style>