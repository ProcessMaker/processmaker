<template>
  <div>
    <slot name="top-content"></slot>

    <FilterTable v-show="!shouldShowLoader"
                 ref="filterTable"
                 :headers="headers"
                 :data="data?data:defaultData"
                 @table-row-mouseover="tableRowMouseover"
                 @table-tr-mouseleave="(row, rowIndex) => $emit('onTrMouseleave', row, rowIndex)">
      <template v-for="header in headers" v-slot:[getSlotName(header.field)]="slotProps">
        <slot :name="'cell-' + header.field" v-bind="slotProps"></slot>
      </template>
    </FilterTable>

    <data-loading v-show="shouldShowLoader"
                  :for="/rule-execution-log/"
                  :empty="empty"
                  :empty-desc="emptyDesc"
                  :empty-icon="emptyIcon"
                  ref="dataLoading"
                  >
    </data-loading>

    <pagination-table :meta="data?.meta"
                      @page-change="changePage">
    </pagination-table>
  </div>
</template>

<script>
  import FilterTable from "./shared/FilterTable.vue";
  import PaginationTable from "./shared/PaginationTable.vue";
  import DataLoading from "./common/DataLoading.vue";
  import dataLoadingMixin from "../components/common/mixins/apiDataLoading";
  export default {
    components: {
      FilterTable,
      DataLoading,
      PaginationTable
    },
    mixins: [dataLoadingMixin],
    props: {
      headers: null,
      data: null,
      showSearch: false,
      empty: null,
      emptyDesc: null,
      emptyIcon: null,
    },
    data() {
      return {
        defaultData: {data: [], meta: []}
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
      },
      changePage(page) {
        this.$emit('page-change', page);
      }
    }
  }
</script>

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