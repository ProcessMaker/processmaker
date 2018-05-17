<template>
  <div>
    <filter-bar></filter-bar>
    <vuetable ref="vuetable"
      :api-mode="false"
      :fields="fields"
      :data-total="dataCount"
      :data-manager="dataManager"
      pagination-path="pagination"
      data-path='data'
      :css="css.table"
      :per-page="20"
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
          name:'user.username',
          title: 'task',
          sortField: 'task',
        },
        {
          name: 'user.name',
          title: 'name',
          sortField: 'user',
        },
        {
          name: 'title',
          sortField: 'title'
        },
        {
          name: 'completed',

        }
      ],
      tasks:[

      ],
      dataCount: 0,
      watch: {
        tasks (newVal, oldVal) {
          this.$refs.vuetable.refresh()
          console.log('refresh',refresh)
        }
      },
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
  created() {
    axios.all([this.getusers(), this.gettasks()])
      .then(axios.spread((users, tasks) => {
        this.users = users.data
        this.tasks = tasks.data
        this.tasks = this.tasks.map(task => {
          task.user = this.users.find(user => task.userId === user.id)
        })
          .then(this.set_data(this.tasks))
      }))
    },

  methods: {
    set_data(data){
      this.$refs.vuetable.setData(data)
      this.$refs.vuetable.refresh()
    },
   getusers() {
      return axios.get('https://jsonplaceholder.typicode.com/users');
    },

   gettasks() {
      return axios.get('https://jsonplaceholder.typicode.com/todos');
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
    dataManager(sortOrder, pagination) {

      let data = this.tasks;

      // account for search filter
      if (this.searchFor) {
        // the text should be case insensitive
        let txt = new RegExp(this.searchFor, "i");

        // search on name, email, and nickname
        data = _.filter(data, function(item) {
          return (
            item.name.search(txt) >= 0 ||
            item.email.search(txt) >= 0 ||
            item.nickname.search(txt) >= 0
          );
        });
      }

      // sortOrder can be empty, so we have to check for that as well
      if (sortOrder.length > 0) {

        data = _.orderBy(data, sortOrder[0].sortField, sortOrder[0].direction);
      }

      // since the filter might affect the total number of records
      // we can ask Vuetable to recalculate the pagination for us
      // by calling makePagination(). this will make VuetablePagination
      // work just like in API mode
      pagination = this.$refs.vuetable.makePagination(data.length);

      // if you don't want to use pagination component, you can just
      // return the data array
      return {
        pagination: pagination,
        data: _.slice(data, pagination.from - 1, pagination.to)
      };
    }
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
