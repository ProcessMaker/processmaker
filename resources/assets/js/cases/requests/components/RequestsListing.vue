<template>
  <div>
    <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data" pagination-path="meta">
        <template slot="actions" slot-scope="props"> 
          <div class="actions">
            <i class="fas fa-ellipsis-h"></i>
            <div class="popout">
              <b-btn variant="action" @click="onAction('edit-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Edit"><i class="fas fa-edit"></i></b-btn>
              <b-btn variant="action" @click="onAction('remove-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Remove"><i class="fas fa-trash-alt"></i></b-btn>
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

export default {
  mixins: [datatableMixin],
  props: ["filter"],
  data() {
    return {
      orderBy: "instance_id",
      sortOrder: [
        {
          field: "instance_id",
          sortField: "instance_id",
          direction: "asc"
        }
      ],
      fields: [
        {
          title: "Id",
          name: "instance_id",
          sortField: "instance_id"
        },
        {
          title: "Process",
          name: "process_name",
          sortField: "process_name"
        },
        {
          title: "Assigned to",
          name: "full_name",
          sortField: "full_name"
        },
        {
          title: "Due date",
          name: "due_date_delay",
          sortField: "due_date_delay",
          callback: this.formatDueDate
        },
        {
          title: "Created on",
          name: "instance_create_date",
          sortField: "instance_create_date",
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
    formatDueDate(value, format) {
      let parts = value.split('|');
      let dueDate = value;
      let delayType = 'on_time';

      if (parts.length === 2) {
        dueDate = parts[0];
        delayType = parts[1];
      }

      let color = 'green';

      switch (delayType) {
        case 'on_time':
            color = 'green';
            break;
        case 'at_risk':
            color = 'yellow';
            break;
        case 'overdue':
            color = 'red';
            break;
      }

      let response = '<i class="fas fa-circle" style="color:'+color+'"></i> ';
      return response + this.formatDate(dueDate);
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

