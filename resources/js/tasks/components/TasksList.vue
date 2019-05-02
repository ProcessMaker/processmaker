<template>
  <div class="data-table">
    <div v-show="loading" class="overlay">
      <i class="fas fa-circle-notch fa-spin fa-2x text-success"></i>
    </div>
    <div class="card card-body table-card">
      <vuetable
        :dataManager="dataManager"
        :sortOrder="sortOrder"
        :css="css"
        :api-mode="false"
        @vuetable:pagination-data="onPaginationData"
        :fields="fields"
        :data="data"
        data-path="data"
        :noDataTemplate="showMessage()"
        pagination-path="meta"
      >
        <template slot="name" slot-scope="props">
          <b-link
            @click="onAction('edit', props.rowData, props.rowIndex)"
          >{{props.rowData.element_name}}</b-link>
        </template>

        <template slot="requestName" slot-scope="props">
          <b-link
            @click="onAction('showRequestSummary', props.rowData, props.rowIndex)"
          >{{props.rowData.process.name}}</b-link>
        </template>

        <template slot="assignee" slot-scope="props">
          <avatar-image size="25" :input-data="props.rowData.user" hide-name="true"></avatar-image>
        </template>

        <template slot="dueDate" slot-scope="props">
          <span :class="props.rowData.status === 'CLOSED' ? 'text-dark' : classDueDate(props.rowData.due_at)">{{formatDate(props.rowData.due_at)}}</span>
        </template>

        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="onAction('edit', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="$t('Open Task')"
              >
                <i class="fas fa-caret-square-right fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onAction('showRequestSummary', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="$t('Open Request')"
              >
                <i class="fas fa-clipboard fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        :single="$t('Task')"
        :plural="$t('Tasks')"
        :perPageSelectEnabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      ></pagination>
    </div>
  </div>
</template>

<script>
import datatableMixin from "../../components/common/mixins/datatable";
import AvatarImage from "../../components/AvatarImage";
import moment from "moment";

Vue.component("avatar-image", AvatarImage);

export default {
  mixins: [datatableMixin],
  props: ["filter"],
  data() {
    return {
      orderBy: "due_at",
      status: "",

      sortOrder: [
        {
          field: "due_at",
          sortField: "due_at",
          direction: "asc"
        }
      ],
      fields: [
        {
          title: () => this.$t("Name"),
          name: "__slot:name",
          field: "element_name",
          sortField: "element_name"
        },
        {
          title: () => this.$t("Status"),
          name: "status",
          sortField: "status",
          callback: this.formatStatus
        },
        {
          title: () => this.$t("Request"),
          name: "__slot:requestName",
          field: "request",
          sortField: "request.name"
        },
        {
          title: () => this.$t("Assignee"),
          name: "__slot:assignee",
          field: "user"
        },
        {
          title: this.status === 'CLOSED' ? () => this.$t("Completed") : () => this.$t("Due"),
          name: "__slot:dueDate",
          field: "request",
          sortField: "due_at"
        },
        {
          name: "__slot:actions",
          title: ""
        }
      ]
    };
  },
  beforeCreate() {
    let params = (new URL(document.location)).searchParams;
    this.status = params.get('status');

    switch (this.status) {
      case "CLOSED":
        this.$parent.status.push({
          name: 'Completed',
          value: 'CLOSED'
        });
        break;
    }
    
    this.$parent.buildPmql();
  },
  mounted: function mounted() {
    let params = new URL(document.location).searchParams;
    let successRouting = params.get("successfulRouting") === "true";
    if (successRouting) {
      ProcessMaker.alert(this.$t("The request was completed."), "success");
    }
  },
  methods: {
    onAction(action, rowData, index) {
      if (action === "edit") {
        let link = "/tasks/" + rowData.id + "/edit";
        window.location = link;
      }

      if (action === "showRequestSummary") {
        let link = "/requests/" + rowData.process_request.id;
        window.location = link;
      }
    },
    showMessage() {
      if(this.loading) {
        return "    "
      }else {
        return "No Data Available"
      }
    },
    formatStatus(status) {
      let statusNames = {
        "ACTIVE" : this.$t('In Progress'),
        "CLOSED" : this.$t('Completed')
      }
      let bubbleColor = {
        "ACTIVE": "text-success",
        "CLOSED": "text-danger",
      };
      return (
        '<i class="fas fa-circle ' +
        bubbleColor[status] + 
        ' small"></i> ' + statusNames[status]
      );
    },
    classDueDate(value) {
      let dueDate = moment(value);
      let now = moment();
      let diff = dueDate.diff(now, "hours");
      return diff < 0 ? "text-danger" : (diff <= 1 ? "text-warning" : "text-dark");
    },
    getTaskStatus() {
      let path = new URL(location.href);
      let status = path.searchParams.get("status");
      return status === null ? "ACTIVE" : status;
    },

    getSortParam: function() {
      if (this.sortOrder instanceof Array && this.sortOrder.length > 0) {
        return (
          "&order_by=" +
          this.sortOrder[0].sortField +
          "&order_direction=" +
          this.sortOrder[0].direction
        );
      } else {
        return "";
      }
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
          "tasks?page=" +
            this.page +
            "&include=process,processRequest,processRequest.user,user" +
            "&pmql=" +
            this.$parent.pmql +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            this.getSortParam(),
          {
            cancelToken: new CancelToken(c => {
              this.cancelToken = c;
            })
          }
        )
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
          if (response.data.meta.in_overdue > 0) {
            this.$emit("in-overdue", response.data.meta.in_overdue);
          }
        })
        .catch(error => {
          window.ProcessMaker.alert(error.response.data.message, "danger");
          this.data = [];
        });
    }
  }
};
</script>

<style>
	.overlay { 
		position: relative; 
		z-index: 10; 
    width: 100%;
    text-align: center;
    top: 89px
	}
</style>

