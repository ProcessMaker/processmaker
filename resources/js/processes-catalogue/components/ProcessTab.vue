<template>
  <div class="mt-3">
    <b-tabs content-class="mt-3 text-style">
      <b-tab
        title="My Requests"
        active
      >
        <div v-if="showTabRequests">
          <filter-table
            :headers="tableHeadersRequests"
            :data="dataRequests"
            @table-row-click="handleRowClick"
          />
          <pagination-table
            :meta="dataRequests.meta"
            @page-change="changePage"
          />
        </div>
        <div v-else>
          <default-tab
            :altText="$t('No Image')"
            :titleText="$t('You have made no requests of this process.')"
            :descriptionText="$t('All your requests will be shown here')"
          />
        </div>
      </b-tab>
      <b-tab title="My Tasks">
        <div v-if="showTabTasks">
          <filter-table
            :headers="tableHeadersTasks"
            :data="dataTasks"
            @table-row-click="handleRowClick"
          />
          <pagination-table
            :meta="dataTasks.meta"
            @page-change="changePage"
          />
        </div>
        <div v-else>
          <default-tab
            :altText="$t('No Image')"
            :titleText="$t('You have no tasks from this process')"
            :descriptionText="
              $t('All your tasks related to this process will be shown here.')
            "
          />
        </div>
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
import Vue from "vue";
import moment from "moment";
import { createUniqIdsMixin } from "vue-uniq-ids";
import AvatarImage from "../../components/AvatarImage";
import isPMQL from "../../modules/isPMQL";
import ListMixin from "../../requests/components/ListMixin";
import { FilterTable } from "../../components/shared";
import PMColumnFilterPopover from "../../components/PMColumnFilterPopover/PMColumnFilterPopover.vue";
import paginationTable from "../../components/shared/PaginationTable.vue";
import TasksList from "../../tasks/components/TasksList.vue";
import DefaultTab from "./DefaultTab.vue";

const uniqIdsMixin = createUniqIdsMixin();

Vue.component("AvatarImage", AvatarImage);

export default {
  components: {
    PMColumnFilterPopover,
    paginationTable,
    TasksList,
    DefaultTab,
  },
  mixins: [uniqIdsMixin, ListMixin],
  props: {
    currentUser: {
      type: Object,
    },
    process: {
      type: Object,
    },
  },
  data() {
    return {
      pmqlRequest: `(status = "In Progress") AND (requester = "${this.currentUser.username}")`,
      pmqlTask: `(status = "In Progress")`,
      filter: "",
      previousFilter: "",
      previousPmql: "",
      orderBy: "id",
      orderDirection: "DESC",
      additionalParams: "",
      advanced_filter: "",
      sortOrder: [
        {
          field: "id",
          sortField: "id",
          direction: "desc",
        },
      ],
      fields: [],
      showTabRequests: false,
      showTabTasks: false,
      tableHeaders: [],
      tableHeadersRequests: [
        {
          label: "REQUEST",
          field: "name",
          sortable: true,
          default: true,
          width: 110,
          truncate: true,
        },
        {
          label: "STATUS",
          field: "status",
          sortable: true,
          default: true,
          width: 150,
        },
        {
          label: "DUE DATE",
          field: "due_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 120,
        },
      ],
      tableHeadersTasks: [
        {
          label: "NAME",
          field: "element_name",
          sortable: true,
          default: true,
          width: 140,
          truncate: true,
        },
        {
          label: "REQUEST",
          field: "request",
          sortable: true,
          default: true,
          width: 140,
          truncate: true,
        },
        {
          label: "STATUS",
          field: "status",
          sortable: true,
          default: true,
          width: 100,
        },
        {
          label: "DUE DATE",
          field: "due_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 140,
        },
      ],
      dataRequests: {},
      dataTasks: {},
      savedSearch: false,
      queryTask: "",
      queryRequest: "",
      perPage: 1,
    };
  },
  computed: {
    endpoint() {
      if (this.savedSearch !== false) {
        return `saved-searches/${this.savedSearch}/results`;
      }

      return "requests";
    },
  },
  mounted() {
    this.setupColumns();
    this.queryBuilder();
  },
  methods: {
    getColumns() {
      return [
        {
          label: "CASE #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 55,
        },
        {
          label: "CASE TITLE",
          field: "case_title",
          sortable: true,
          default: true,
          width: 220,
        },
        {
          label: "PROCESS NAME",
          field: "name",
          sortable: true,
          default: true,
          width: 220,
          truncate: true,
        },
      ];
    },
    setupColumns() {
      const columns = this.getColumns();
      this.tableHeaders = this.getColumns();
      columns.forEach((column) => {
        const field = {
          title: () => this.$t(column.label),
        };

        switch (column.field) {
          case "id":
            field.name = "__slot:ids";
            field.title = "#";
            break;
          case "participants":
            field.name = "__slot:participants";
            break;
          case "name":
            field.name = "__slot:name";
            break;
          default:
            field.name = column.name || column.field;
        }

        if (!field.field) {
          field.field = column.field;
        }

        if (column.format === "datetime") {
          field.callback = "formatDateUser|datetime";
        }

        if (column.format === "date") {
          field.callback = "formatDateUser|date";
        }

        if (column.sortable === true && !field.sortField) {
          field.sortField = column.field;
        }

        this.fields.push(field);
      });

      this.fields.push({
        name: "__slot:actions",
        title: "",
      });
    },
    jsonRows(rows) {
      if (rows.length === 0 || !_.has(_.head(rows), "_json")) {
        return rows;
      }
      return rows.map((row) => JSON.parse(row._json));
    },
    changePage(page) {
      this.page = page;
      this.fetch();
    },
    handleRowClick(row) {
      window.location.href = this.openRequest(row, 1);
    },
    openRequest(data, index) {
      return `/requests/${data.id}`;
    },
    transform(data) {
      // Clean up fields for meta pagination so vue table pagination can understand
      data.meta.last_page = data.meta.total_pages;
      data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
      data.meta.to = data.meta.from + data.meta.count;
      data.data = this.jsonRows(data.data);
      for (let record of data.data) {
        //format Status
        record["case_number"] = this.formatCaseNumber(record);
        record["active_tasks"] = record["active_tasks"];
        record["status"] = this.formatStatus(record["status"]);
        record["participants"] = this.formatParticipants(
          record["participants"]
        );
      }
      return data;
    },
    formatParticipants(participants) {
      return {
        component: "AvatarImage",
        props: {
          size: "25",
          "input-data": participants,
          "hide-name": false,
        },
      };
    },
    formatStatus(status) {
      let color = "success",
        label = "In Progress";
      switch (status) {
        case "DRAFT":
          color = "danger";
          label = "Draft";
          break;
        case "CANCELED":
          color = "danger";
          label = "Canceled";
          break;
        case "COMPLETED":
          color = "primary";
          label = "Completed";
          break;
        case "ERROR":
          color = "danger";
          label = "Error";
          break;
      }
      return (
        '<span class="badge badge-' +
        color +
        " status-" +
        color +
        '">' +
        this.$t(label) +
        "</span>"
      );
    },
    formatActiveTasks(value) {
      let htmlString = "";
      for (const task of value) {
        htmlString += `
          <div>
            <a class="text-nowrap" href="${this.openTask(task)}">
              ${task.element_name}
            </a>
          </div>
        `;
      }
      return htmlString;
    },
    openTask(task) {
      return `/tasks/${task.id}/edit`;
    },
    formatCaseNumber(value) {
      return `
      <a href="${this.openRequest(value, 1)}"
         class="text-nowrap">
         # ${value.case_number}
      </a>`;
    },
    queryBuilder() {
      let pmql = "";
      if (this.pmqlRequest !== undefined) {
        pmql = this.pmqlRequest;
      }

      let filter = this.filter;
      if (filter && filter.length) {
        if (filter.isPMQL()) {
          pmql = `(${pmql}) and (${filter})`;
          filter = "";
        }
      }

      if (this.previousFilter !== filter) {
        this.page = 1;
      }

      this.previousFilter = filter;

      if (this.previousPmql !== pmql) {
        this.page = 1;
      }

      this.previousPmql = pmql;

      this.queryRequest =
        "requests?page=" +
        this.page +
        "&per_page=" +
        this.perPage +
        "&include=process,participants,activeTasks,data" +
        "&pmql=" +
        `${this.pmqlRequest}` +
        " AND process_id=" +
        `${this.process.id}` +
        "&filter&order_by=id&order_direction=DESC";

      this.queryTask =
        "tasks?page=" +
        this.page +
        "&include=process,processRequest,processRequest.user,user,data" +
        "&pmql=" +
        `${this.pmqlTask}` +
        " AND process_id=" +
        `${this.process.id}` +
        "&per_page=10&order_by=ID&order_direction=DESC&non_system=true";

      this.getData(this.queryRequest, "requests");
      this.getData(this.queryTask, "tasks");
    },
    getData(query, type) {
      // Load from api client
      ProcessMaker.apiClient
        .get(query)
        .then((response) => {
          const dataResponse = this.transform(response.data);
          type === "requests"
            ? (this.dataRequests = this.transform(response.data))
            : type === "tasks" &&
              (this.dataTasks = this.transform(response.data));

          if (
            dataResponse &&
            Array.isArray(dataResponse.data) &&
            dataResponse.data.length === 0
          ) {
            type === "requests"
              ? (this.showTabRequests = false)
              : (this.showTabTasks = false);
          } else {
            type === "tasks"
              ? (this.showTabRequests = true)
              : (this.showTabTasks = true);
          }
        })
        .catch((error) => {
          if (error.code === "ERR_CANCELED") {
            return;
          }
          if (_.has(error, "response.data.message")) {
            ProcessMaker.alert(error.response.data.message, "danger");
          } else if (_.has(error, "response.data.error")) {
            return;
          } else {
            throw error;
          }
        });
    },
  },
};
</script>
<style>
.text-style {
  margin-bottom: 10px;
  color: #556271;
}
</style>
