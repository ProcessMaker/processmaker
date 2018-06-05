<template>
  <div>
    <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data" pagination-path="meta"></vuetable> 
    <pagination single="Role" plural="Roles" :perPageSelectEnabled="true" @changePerPage="changePerPage" @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
   </div>
</template>

<script>
import Vuetable from "vuetable-2/src/components/Vuetable";
import Pagination from "../../../components/common/Pagination";
import moment from 'moment'

export default {
  components: {
    Vuetable,
    Pagination
  },
  props: ["filter"],
  data() {
    return {
      // Our listing of roles
      data: [],
      page: 1,
      perPage: 10,
      orderBy: 'name',
      orderDirection: 'asc',
      loading: false,
      cancelToken: null,
      css: {
        tableClass: "ui blue selectable celled stackable attached table",
        loadingClass: "loading",
        ascendingIcon: "blue chevron up icon",
        descendingIcon: "blue chevron down icon",
        detailRowClass: "vuetable-detail-row",
        handleIcon: "grey sidebar icon",
        sortableIcon: "fas fa-sort",
        ascendingIcon: "fas fa-sort-up",
        descendingIcon: "fas fa-sort-down",
        ascendingClass: "ascending",
        descendingClass: "descending",
        renderIcon: function(classes, options) {
          return `<i class="${classes.join(" ")}"></i>`;
        }
      },
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc"
        }
      ],
      fields: [
        {
          title: "Code",
          name: "code",
          sortField: "code"
        },
        {
          title: "Name",
          name: "name",
          sortField: "name"
        },
        {
          title: "Description",
          name: "description",
          sortField: "description"
        },
        {
          title: "Status",
          name: "status",
          sortField: "status",
          callback: this.formatStatus
        },
       {
          title: "Active Users",
          name: "total_users",
          sortField: "total_users",
          callback: this.formatActiveUsers
        },
        {
          title: "Created At",
          name: "created_at",
          sortField: "created_at",
          callback: this.formatDate
        },
        {
          title: "Updated At",
          name: "updated_at",
          sortField: "updated_at",
          callback: this.formatDate
        }
      ]
    };
  },
  created() {
    // Use our api to fetch our role listing
    this.fetch();
  },
  watch: {
    filter: _.debounce(function() {
      if (!this.loading) {
        this.fetch();
      }
    }, 250)
  },
  methods: {
    formatDate(value) {
      return moment(value).format('l LTS')
    },
    formatActiveUsers(value) {
      return '<div class="text-center">' + value + '</div>';
    },
    formatStatus(value) {
      value = value.toLowerCase();
      let response = '<i class="fas fa-circle ' + value +'"></i> ';
      value = value.charAt(0).toUpperCase() + value.slice(1);
      return response + value;
    },
    dataManager(sortOrder, pagination) {
      this.orderBy = sortOrder[0].field;
      this.orderDirection = sortOrder[0].direction;
      this.fetch();
    },
    changePerPage(value) {
      this.perPage = value;
      this.fetch();
    },
    fetch() {
      this.loading = true;
      if(this.cancelToken) {
        this.cancelToken();
        this.cancelToken = null;
      }
      const CancelToken = ProcessMaker.apiClient.CancelToken;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "roles?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy + 
            "&order_direction=" +
            this.orderDirection,
          {
            cancelToken: new CancelToken((c) => {
              this.cancelToken = c;
            })
          }
        )
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
        })
        .catch(error => {
          // Undefined behavior currently, show modal?
        });
    },
    transform(data) {
      // Clean up fields for meta pagination so vue table pagination can understand
      data.meta.last_page = data.meta.total_pages;
      data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
      data.meta.to = data.meta.from + data.meta.count;
      return data;
    },
    onPaginationData(data) {
      this.$refs.pagination.setPaginationData(data);
    },
    onPageChange(page) {
      if (page == "next") {
        this.page = this.page + 1;
      } else if (page == "prev") {
        this.page = this.page - 1;
      } else {
        this.page = page;
      }
      if (this.page <= 0) {
        this.page = 1;
      }
      if (this.page > this.data.meta.last_page) {
        this.page = this.data.meta.last_page;
      }
      this.fetch();
    }
  }
};
</script>

<style lang="scss" scoped>

/deep/ th#_total_users {
  width: 150px;
  text-align: center;
}

/deep/ th#_description {
  width: 250px;
}

/deep/ i.fa-circle {
  &.active {
    color: green;
  }
  &.inactive {
    color: red;
  }
}


</style>

