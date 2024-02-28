<template>
  <div>
    <div v-if="showSearch" class="search-bar flex-grow w-100">
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

    <FilterTable v-show="!shouldShowLoader"
                 ref="filterTable"
                 :headers="headers"
                 :data="data"
                 @table-row-mouseover="tableRowMouseover"
                 @table-tr-mouseleave="(row, rowIndex) => $emit('onTrMouseleave', row, rowIndex)">
      <template v-for="header in headers" v-slot:[getSlotName(header.field)]="slotProps">
        <slot :name="'cell-' + header.field" v-bind="slotProps" />
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
    <pagination-table :meta="data.meta"
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
        dataLoading: false,
        noResults: false,
        showsSearch: true,
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
        return `cell-${field}`
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