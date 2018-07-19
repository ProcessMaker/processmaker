<template>
  <div>
    <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data" pagination-path="meta">
        <template slot="actions" slot-scope="props"> 
          <div class="actions">
            <i class="fas fa-ellipsis-h"></i>
            <div class="popout">
              <b-btn variant="action" @click="openRequest(props.rowData, props.rowIndex)" v-b-tooltip.hover title="Open">
                  <i class="fas fa-folder-open"></i>
              </b-btn>
            </div>
          </div>
      </template>  
    </vuetable>
    <pagination single="Request" plural="Requests" :perPageSelectEnabled="true" @changePerPage="changePerPage" @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
   </div>
</template>

<script>
import Vuetable from "vuetable-2/src/components/Vuetable";
import datatableMixin from "../../../components/common/mixins/datatable";
import Pagination from "../../../components/common/Pagination";
import moment from "moment"

export default {
  mixins: [datatableMixin],
  props: ["filter"],
  data() {
    return {
      orderBy: "id",
      sortOrder: [
        {
          field: "id",
          sortField: "id",
          direction: "asc"
        }
      ],
      fields: [
        {
          title: "Id",
          name: "uid",
          sortField: "uid",
          callback: this.formatUid
        },
        {
          title: "Process",
          name: "process.name",
          sortField: "process.name"
        },
        {
          title: "Assigned to",
          name: "delegations",
          callback: this.assignedTo
        },
        {
          title: "Due date",
          name: "delegations",
          callback: this.formatDueDate
        },
        {
          title: "Created on",
          name: "APP_CREATE_DATE",
          sortField: "APP_CREATE_DATE",
          callback: this.formatDate
        },
        {
          name: "__slot:actions",
          title: ""
        }
     ]
    };
  },
  methods: {
    openRequest(data, index) {
      window.open('/request/' + data.uid + '/status');
    },
    formatUid(uid) {
        return uid.split('-').pop();
    },
    assignedTo(delegations) {
      let assignedTo = '';
      if (!delegations) return assignedTo;
      delegations.forEach(function (delegation) {
        let user = delegation.user;
        let avatar = user.avatar ? '<img class="avatar" src="' + user.avatar + '">'
                : '<i class="fas fa-user"></i>';
        assignedTo +=  avatar + ' ' + user.fullname + '<br>';
      });
      return assignedTo;
    },
    formatDateWithDot(value) {
      if (!value) {
          return '';
      }
      let duedate = moment(value);
      let now = moment();
      let diff = duedate.diff(now, 'hours');
      let color = diff < 0 ? 'text-danger' : (diff <= 48 ? 'text-warning' : 'text-primary');
      return '<i class="fas fa-circle '+color+'"></i> ' + duedate.format('YYYY-MM-DD hh:mm');
    },
    formatDate(value) {
      let date = moment(value);
      return date.format('YYYY-MM-DD hh:mm');
    },
    formatDueDate(delegations) {
      let dueDate = '';
      let self = this;
      if (!delegations) return dueDate;
      delegations.forEach(function (delegation) {
        dueDate += self.formatDateWithDot(delegation.task_due_date) + '<br>';
      });
      return dueDate;
    },
    transform(data) {
      // Clean up fields for meta pagination so vue table pagination can understand
      data.meta.last_page = data.meta.total_pages;
      data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
      data.meta.to = data.meta.from + data.meta.count;

      for(let record of data.data) {
        record['full_name'] = [record['firstname'], record['lastname']].join(' ');
      }
      return data;
    },
    fetch() {
      this.loading = true;

      //get any additional query string parameters
      let urlParts = window.location.href.split('?');
      let additionalParams = '';
      if (urlParts.length === 2) {
        additionalParams = '&' + urlParts[1];
      }

      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "requests?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&include=process,delegations,delegations.user" +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection +
            additionalParams
        )
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
        })
        .catch(error => {
          // Undefined behavior currently, show modal?
        });
    }
  }
};
</script>

<style lang="scss" scoped>
/deep/ i.fa-circle {
  &.active {
    color: green;
  }
  &.inactive {
    color: red;
  }
}
</style>
