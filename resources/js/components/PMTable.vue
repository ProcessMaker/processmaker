<template>
  <div>
    <div class="search-bar flex-grow w-100">
      <div class="d-flex align-items-center">
        <i class="fa fa-search ml-3 pmql-icons">
        </i>
        <textarea  ref="search_input"
                   type="text"
                   :aria-label="''"
                   :placeholder="$t('Search here')"
                   rows="1"
                   @keydown.enter.prevent
                   class="pmql-input">
        </textarea>
        <i class="fa fa-times pl-1 pr-3 pmql-icons"
           role="button">
        </i>
        <b-button id="idPopoverInboxRules"
                  class="ml-md-1 task-inbox-rules"
                  variant="primary">
          {{ $t('Create Rule') }}
        </b-button>
      </div>  
    </div>

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
        changePage: () => {
        }
      };
    },
    methods: {
      tableRowMouseover(row) {
        let container = this.$refs.filterTable.$el;
        let scrolledWidth = container.scrollWidth - container.clientWidth;
        this.$emit('onRowMouseover', row, scrolledWidth);
      }
    }
  }
</script>

<style>
</style>
<style scoped>
  .pmql-icons{

  }
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