<template>
  <div class="data-table">
    <data-loading
      :for=/tasks\?page/
      v-show="shouldShowLoader"
      :empty="$t('Congratulations')"
      :empty-desc="$t('You don\'t currently have any tasks assigned to you')"
      empty-icon="beach"
    />
    <div v-show="!shouldShowLoader" class="card card-body table-card">
      <vuetable
        :dataManager="dataManager"
        :sortOrder="sortOrder"
        :css="css"
        :api-mode="false"
        @vuetable:pagination-data="onPaginationData"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
        ref="vuetable"
      >
        <template slot="ids" slot-scope="props">
          <b-link :href="onAction('edit', props.rowData, props.rowIndex)">#{{props.rowData.id}}</b-link>
        </template>
        <template slot="name" slot-scope="props">
          <b-link
            :href="onAction('edit', props.rowData, props.rowIndex)"
          >{{props.rowData.element_name}}</b-link>
        </template>

        <template slot="requestName" slot-scope="props">
          <b-link
            :href="onAction('showRequestSummary', props.rowData, props.rowIndex)"
          >#{{props.rowData.process_request.id}} {{props.rowData.process.name}}</b-link>
        </template>

        <template slot="status" slot-scope="props">
          <i class="fas fa-circle small" :class="statusColor(props.rowData)"></i>
          {{ (statusLabel(props.rowData)) }}
        </template>

        <template slot="assignee" slot-scope="props">
          <avatar-image size="25" :input-data="props.rowData.user" hide-name="true" v-if="props.rowData.user"></avatar-image>
        </template>

        <template slot="dueDate" slot-scope="props">
          <span
            :class="classDueDate(props.rowData.due_at)"
          >{{formatDate(props.rowData.due_at)}}</span>
        </template>

        <template slot="completedDate" slot-scope="props">
          <span
            class="text-dark"
          >{{formatDate(props.rowData.completed_at)}}</span>
        </template>

        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                :href="onAction('edit', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="$t('Open Task')"
              >
                <i class="fas fa-caret-square-right fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                :href="onAction('showRequestSummary', props.rowData, props.rowIndex)"
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
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import AvatarImage from "../../components/AvatarImage";
import isPMQL from "../../modules/isPMQL";
import moment from "moment";

Vue.component("avatar-image", AvatarImage);

export default {
  mixins: [datatableMixin, dataLoadingMixin],
  props: ["filter", "columns", "pmql"],
  data() {
    return {
      orderBy: "ID",
      order_direction: "DESC",
      status: "",
      sortOrder: [
        {
          field: "ID",
          sortField: "ID",
          direction: "DESC"
        }
      ],
      fields: []
    };
  },
  mounted: function mounted() {
    this.setupColumns();
    let params = new URL(document.location).searchParams;
    let successRouting = params.get("successfulRouting") === "true";
    if (successRouting) {
      ProcessMaker.alert(this.$t("The request was completed."), "success");
    }
  },
  methods: {
    setupColumns() {
      let columns = this.getColumns();
      
      columns.forEach(column => {    
        let field = {
          title: this.$t(column.label)
        };
        
        switch (column.field) {
          case 'id':
            field.name = '__slot:ids';
            field.title = '#';
            break;
          case 'task':
            field.name = '__slot:name';
            field.field = 'element_name';
            field.sortField = 'element_name';
            break;
          case 'status':
            field.name = '__slot:status';
            field.sortField = 'status';
            break;
          case 'request':
            field.name = '__slot:requestName';
            field.sortField = 'process_requests.id,process_requests.name';
            break;
          case 'assignee':
            field.name = '__slot:assignee';
            field.field = "user";
            break;
          case 'due_at':
            field.name = '__slot:dueDate';
            break;
          case 'completed_at':
            field.name = '__slot:completedDate';
            break;
          default:
            field.name = column.field;
        }
        
        if (! field.field) {
          field.field = column.field;
        }
              
        if (column.sortable && ! field.sortField) {
          field.sortField = column.field;
        }

        this.fields.push(field);    
      });
      
      this.fields.push({
        name: "__slot:actions",
        title: ""
      });

      // this is needed because fields in vuetable2 are not reactive
      this.$nextTick(()=>{
        this.$refs.vuetable.normalizeFields();
      });      
    },
    getColumns() {
      if (this.$props.columns) {
        return this.$props.columns;
      } else {
        let columns = [
          {
            "label": "#",
            "field": "id",
            "sortable": true,
            "default": true
          },
          {
            "label": "Task",
            "field": "task",
            "sortable": true,
            "default": true
          },
          {
            "label": "Status",
            "field": "status",
            "sortable": true,
            "default": true
          },
          {
            "label": "Request",
            "field": "request",
            "sortable": true,
            "default": true
          },
          {
            "label": "Assignee",
            "field": "assignee",
            "sortable": false,
            "default": true
          }
        ];
        
        if (this.status === "CLOSED") {
          columns.push({
            "label": "Completed",
            "field": "completed_at",
            "sortable": true,
            "default": true
          });
        } else {
          columns.push({
            "label": "Due",
            "field": "due_at",
            "sortable": true,
            "default": true
          });
        }
        
        return columns;
      }
    },
    onAction(action, rowData, index) {
      if (action === "edit") {
        let link = "/tasks/" + rowData.id + "/edit";
        return link;
      }

      if (action === "showRequestSummary") {
        let link = "/requests/" + rowData.process_request.id;
        return link;
      }
    },
    statusColor(props) {
      let status = props.status, isSelfService = props.is_self_service;
      if (status == 'ACTIVE' && isSelfService) {
        return 'text-warning';
      } else if (status == 'ACTIVE') {
        return 'text-success';
      } else if (status == 'CLOSED') {
        return 'text-primary';
      } else {
        return 'text-secondary';
      }
    },
    statusLabel(props) {
      let status = props.status, isSelfService = props.is_self_service;
      if (status == 'ACTIVE' && isSelfService) {
        return 'Self Service';
      } else if (status == 'ACTIVE') {
        return 'In Progress';
      } else if (status == 'CLOSED') {
        return 'Completed';
      } else {
        return status;
      }
    },
    classDueDate(value) {
      let dueDate = moment(value);
      let now = moment();
      let diff = dueDate.diff(now, "hours");
      return diff < 0
        ? "text-danger"
        : diff <= 1
        ? "text-warning"
        : "text-dark";
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

    fetch(query) {
        Vue.nextTick(() => {
            if (this.cancelToken) {
              this.cancelToken();
              this.cancelToken = null;
            }
            const CancelToken = ProcessMaker.apiClient.CancelToken;
            
            let pmql = '';
            
            if (this.pmql !== undefined) {
                pmql = this.pmql;
            }

            let filter = this.filter;
            
            if (query && query.length) {
              if (query.isPMQL()) {
                pmql = `(${pmql}) and (${query})`;
              } else {
                filter = query;
              }
            }
            
            // Load from our api client
            ProcessMaker.apiClient
              .get(
                "tasks?page=" +
                  this.page +
                  "&include=process,processRequest,processRequest.user,user,data" +
                  "&pmql=" +
                  encodeURIComponent(pmql) +
                  "&per_page=" +
                  this.perPage +
                  "&user_id=" +
                  window.ProcessMaker.user.id +
                  "&filter=" +
                  filter +
                  "&statusfilter=ACTIVE,CLOSED" +
                  this.getSortParam(),
                {
                  cancelToken: new CancelToken(c => {
                    this.cancelToken = c;
                  })
                }
              )
              .then(response => {
                this.data = this.transform(response.data);
                if (response.data.meta.in_overdue > 0) {
                  this.$emit("in-overdue", response.data.meta.in_overdue);
                }
              })
              .catch(error => {
                window.ProcessMaker.alert(error.response.data.message, "danger");
                this.data = [];
              });
        });
    }
  }
};
</script>

<style>
</style>
