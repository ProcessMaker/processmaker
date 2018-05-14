<template>
  <div>
    <filter-bar></filter-bar>
    <vuetable ref="vuetable"
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
  mounted() {
    // Maybe fetch is a method you define that does the ajax calls.  By calling it
    // in the mounted handler, it'll do the initial load
    // this.fetch({
    //   axios.get('https://jsonplaceholder.typicode.com/users')
    //     .then(function (response) {
    //       console.log(response);
    //     })
    //     .catch(function (error) {
    //       console.log(error);
    //     });
    // });
    // Fetch our users and then populate the data
    // Here, you'd want to potentially do your various axious calls, merge the
    // data and then make sure you build up the object that vuetable expects.
    // See: https://ratiw.github.io/vuetable-2/#/Data-Format-JSON
    this.$refs.vuetable.setData({
  "links": {
    "pagination": {
      "total": 5,
      "per_page": 2,
      "current_page": 1,
      "last_page": 2,
      "next_page_url": "...",
      "prev_page_url": "...",
      "from": 1,
      "to": 2,
    }
  },
  " ": [
    {
      "id": 1,
      "name": "xxxxxxxxx",
      "nickname": "xxxxxxx",
      "email": "xxx@xxx.xxx",
      "birthdate": "xxxx-xx-xx",
      "gender": "X",
      "group_id": 1,
    },
    {
      "id": 50,
      "name": "xxxxxxxxx",
      "nickname": "xxxxxxx",
      "email": "xxx@xxx.xxx",
      "birthdate": "xxxx-xx-xx",
      "gender": "X",
      "group_id": 3,
    }
  ]
});


},
  methods: {
    getUsers (){
      return axios.get('https://jsonplaceholder.typicode.com/users');
      /*
      .then(function (response) {
        console.log(response,"response");
      })
      .catch(function (error) {
        console.log(error);
      });
      */
    },
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
