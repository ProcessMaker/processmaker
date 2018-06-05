<template>
  <div>
    <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data" pagination-path="meta">
        <template slot="actions" slot-scope="props"> 
          <div class="actions">
            <i class="fas fa-ellipsis-h"></i>
            <div class="popout">
              <b-btn variant="action" @click="onAction('edit-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Edit"><i class="fas fa-edit"></i></b-btn>
              <b-btn variant="action" @click="onAction('remove-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Remove"><i class="fas fa-trash-alt"></i></b-btn>
              <b-btn variant="action" @click="onAction('users-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Users"><i class="fas fa-users"></i></b-btn>
              <b-btn variant="action" @click="onAction('permissions-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Permissions"><i class="fas fa-user-lock"></i></b-btn>
            </div>
          </div>
    </template>  
    </vuetable> 
    <pagination single="Role" plural="Roles" :perPageSelectEnabled="true" @changePerPage="changePerPage" @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
   </div>
</template>

<script>
import Vuetable from "vuetable-2/src/components/Vuetable";
import Pagination from "../../../components/common/Pagination";
import moment from "moment";

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
      orderBy: "name",
      orderDirection: "asc",
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
        },
        {
          name: "__slot:actions",
          title: ""
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
      return moment(value).format("l LTS");
    },
    formatActiveUsers(value) {
      return '<div class="text-center">' + value + "</div>";
    },
    formatStatus(value) {
      value = value.toLowerCase();
      let response = '<i class="fas fa-circle ' + value + '"></i> ';
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
      if (this.cancelToken) {
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
            cancelToken: new CancelToken(c => {
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

/deep/ tr:hover td {
  background-color: #d8d8d8;
}

/deep/ i.fa-circle {
  &.active {
    color: green;
  }
  &.inactive {
    color: red;
  }
}

/deep/ .vuetable-slot {
  position: relative;
}

/deep/ .actions {
  cursor: pointer;

  .popout {
    display: none;
    align-items: center;
    position: absolute;
    background-color: #d8d8d8;
    right: 0px;
    top: 0px;
    font-size: 17px;
    text-align: right;
    height: 42px;

    button.btn-action {
      color: #212529;
      height: 32px;
      width: 32px;
      margin-left: 4px;
      margin-right: 4px;
      background-color: #d8d8d8;
      border-color: #d8d8d8;
      text-align: center;
      padding: 0px;

      &:hover {
        background-color: white;
        border-radius: 2px;
      }
  }
    
  }

  &:hover {
    .popout {
      display: flex;
    }
  }
}
</style>

