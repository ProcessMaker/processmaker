<template>
  <div>
    <filter-bar></filter-bar>
    <vuetable ref="vuetable"
      api-url="https://vuetable.ratiw.net/api/users"
      :fields="fields"
      pagination-path=""
      :css="css.table"
      :sort-order="sortOrder"
      :multi-sort="true"
      detail-row-component="my-detail-row"
      :append-params="moreParams"
      @vuetable:cell-clicked="onCellClicked"
      @vuetable:pagination-data="onPaginationData"
    ></vuetable>
    <div class="vuetable-pagination">
      <vuetable-pagination-info ref="paginationInfo"
        info-class="pagination-info"
      ></vuetable-pagination-info>
      <vuetable-pagination ref="pagination"
        :css="css.pagination"
        @vuetable-pagination:change-page="onChangePage"
      ></vuetable-pagination>
    </div>
  </div>
</template>

<script>
import accounting from 'accounting'
import moment from 'moment'
import Vuetable from 'vuetable-2/src/components/Vuetable'
import VuetablePagination from 'vuetable-2/src/components/VuetablePagination'
import VuetablePaginationInfo from 'vuetable-2/src/components/VuetablePaginationInfo'
import Vue from 'vue'
import VueEvents from 'vue-events'
import CustomActions from './CustomActions'
import DetailRow from './DetailRow'
import FilterBar from './FilterBar'

Vue.use(VueEvents)
Vue.component('custom-actions', CustomActions)
Vue.component('my-detail-row', DetailRow)
Vue.component('filter-bar', FilterBar)

export default {

  components: {
    Vuetable,
    VuetablePagination,
    VuetablePaginationInfo,
  },
  data () {
    return {
      fields: [
        {
          name: 'name',
          sortField: 'name',
        },
        {
          name: 'nickname',
          sortField: 'nickname',
        },
        {
          name: 'email',
          sortField: 'email'
        },
        {
          name: 'birthdate',
          sortField: 'birthdate',
          callback: 'formatDate|D/MM/Y'
        }
      ],
      css: {
        table: {
          tableClass: 'table table-hover',
          ascendingIcon: 'fa fa-sort-asc',
          descendingIcon: 'fa fa-sort-desc'
        },
        pagination: {
          wrapperClass: 'pagination',
          activeClass: 'active',
          disabledClass: 'disabled',
          pageClass: 'page',
          linkClass: 'link',
          icons: {
            first: '',
            prev: '',
            next: '',
            last: '',
          },
        },
      },
      sortOrder: [
        { field: 'name'}
      ],
      moreParams: {}
    }
  },
  methods: {
    allcap (value) {
      return value.toUpperCase()
    },
    formatNumber (value) {
      return accounting.formatNumber(value, 2)
    },
    formatDate (value, fmt = 'D MMM YYYY') {
      return (value == null)
        ? ''
        : moment(value, 'YYYY-MM-DD').format(fmt)
    },
    onPaginationData (paginationData) {
      this.$refs.pagination.setPaginationData(paginationData)
      this.$refs.paginationInfo.setPaginationData(paginationData)
    },
    onChangePage (page) {
      this.$refs.vuetable.changePage(page)
    },
    onCellClicked (data, field, event) {
      console.log('cellClicked: ', field.name)
      this.$refs.vuetable.toggleDetailRow(data.id)
    },
  },
  events: {
    'filter-set' (filterText) {
      this.moreParams = {
        filter: filterText
      }
      Vue.nextTick( () => this.$refs.vuetable.refresh() )
    },
    'filter-reset' () {
      this.moreParams = {}
      Vue.nextTick( () => this.$refs.vuetable.refresh() )
    }
  }
}
</script>
<style>
.pagination {
  margin: 0;
  float: right;
}
.pagination a.page {
  border: 1px solid lightgray;
  border-radius: 3px;
  padding: 5px 10px;
  margin-right: 2px;
}
.pagination a.page.active {
  color: white;
  background-color: #337ab7;
  border: 1px solid lightgray;
  border-radius: 3px;
  padding: 5px 10px;
  margin-right: 2px;
}
.pagination a.btn-nav {
  border: 1px solid lightgray;
  border-radius: 3px;
  padding: 5px 7px;
  margin-right: 2px;
}
.pagination a.btn-nav.disabled {
  color: lightgray;
  border: 1px solid lightgray;
  border-radius: 3px;
  padding: 5px 7px;
  margin-right: 2px;
  cursor: not-allowed;
}
.pagination-info {
  float: left;
}
</style>
