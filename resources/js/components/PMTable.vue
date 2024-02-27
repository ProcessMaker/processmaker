<template>
  <div>
    <FilterTable ref="filterTable"
                 :headers="headers"
                 :data="data"
                 @table-row-mouseover="tableRowMouseover"
                 @table-tr-mouseleave="$emit('onTrMouseleave', $event)">
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
      };
    },
    methods: {
      tableRowMouseover(row) {
        let container = this.$refs.filterTable.$el;
        let scrolledWidth = container.scrollWidth - container.clientWidth;
        this.$emit('onRowMouseover', row, scrolledWidth);
      },
      changePage(page) {
        this.$emit('page-change', page);
      }
    }
  }
</script>

<style>
</style>
<style scoped>
</style>