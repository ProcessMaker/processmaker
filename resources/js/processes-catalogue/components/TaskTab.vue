<template>
  <div>
    <div
      class="bg-white class-container"
      v-if="!showTabTasks"
    >
      <filter-table
        :headers="tableHeadersTasks"
        :data="dataTasks"
        table-name="task-tab"
      />
      <pagination-table
        class="custom-pagination"
        :meta="dataTasks.meta"
        @page-change="changePageTasks"
        @per-page-change="changePerPage"
      />
    </div>
    <div v-else>
      <default-tab
        :alt-text="$t('No Image')"
        :title-text="$t('No items to show.')"
        :description-text="$t('You have to start a Case of this process.')"
      />
    </div>
  </div>
</template>

<script>
import Vue from "vue";
import AvatarImage from "../../components/AvatarImage";
import PMColumnFilterPopover from "../../components/PMColumnFilterPopover/PMColumnFilterPopover.vue";
import paginationTable from "../../components/shared/PaginationTable.vue";
import DefaultTab from "./DefaultTab.vue";
import SearchTab from "./utils/SearchTab.vue";
import ListMixin from "../../tasks/components/ListMixin";
import { FilterTable } from "../../components/shared";
import { createUniqIdsMixin } from "vue-uniq-ids";
import { methodsTabMixin } from "./TabMixing.js";
const uniqIdsMixin = createUniqIdsMixin();

Vue.component("AvatarImage", AvatarImage);

export default {
  components: {
    PMColumnFilterPopover,
    paginationTable,
    DefaultTab,
    FilterTable,
    SearchTab,
  },
  mixins: [uniqIdsMixin, ListMixin, methodsTabMixin],
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
      pmqlTask: `(user_id = ${ProcessMaker.user.id})`,
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
      showTabTasks: false,
      tableHeaders: [],
      tableHeadersTasks: [
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
          width: 140,
          truncate: true,
        },
        {
          label: "TASK NAME",
          field: "element_name",
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
      dataTasks: {},
      savedSearch: false,
      perPage: 15,
    };
  },
  mounted() {
    this.queryBuilder();
  },
  methods: {
    changePageTasks(page) {
      this.page = page;
      this.queryBuilder();
    },
    changePerPage(value, page) {
      this.page = page;
      this.perPage = value;
      this.queryBuilder();
    },
    openTask(task) {
      return `/tasks/${task.id}/edit`;
    },
    transform(data) {
      // Clean up fields for meta pagination so vue table pagination can understand
      data.meta.last_page = data.meta.total_pages;
      data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
      data.meta.to = data.meta.from + data.meta.count;
      data.data = this.jsonRows(data.data);
      for (let record of data.data) {
        //format Status
        record["case_number"] = this.formatCaseNumber(record.process_request);
        record["case_title"] = this.formatCaseTitle(record.process_request);
        record["name"] = record.process.name;
        record["status"] = this.formatStatus(record);
        record["participants"] = this.formatParticipants(
          record["participants"]
        );
      }
      return data;
    },
    openRequest(data) {
      return `/requests/${data.id}`;
    },
    formatCaseTitle(processTask) {
      return `
      <a href="${this.openRequest(processTask, 1)}"
         class="text-nowrap">
         ${processTask.case_title_formatted || ""}
      </a>`;
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
    formatStatus(props) {
      let color;
      let label;

      if (props.status === "ACTIVE" && props.isSelfService) {
        color = "danger";
        label = "Self Service";
      } 
      if (props.status === "ACTIVE") {
        color = "success";
        label = "In Progress";
      } 
      if (props.status === "CLOSED") {
        color = "primary";
        label = "Completed";
      }
      return `
        <span class="badge badge-${color} status-${color}">
          ${label}
        </span>`;
    },

    formatCaseNumber(value) {
      return `
      <a href="${this.openRequest(value, 1)}"
         class="text-nowrap">
        # ${value.case_number}
      </a>`;
    },
    /**
     * Build the search PMQL
     */
    onFilter(value, showEmpty = false) {
      this.filter = `fulltext LIKE "%${value}%"`;
      this.queryBuilder();
    },
    queryBuilder() {
      let pmql = "";
      if (this.pmqlTask !== undefined) {
        pmql = `(${this.pmqlTask}) AND (process_id = ${this.process.id})`
      }
      let filter = this.filter;
      if (filter?.length) {
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
      this.tabTasks(pmql);
    },
    tabTasks(pmql) {
      this.queryTask =
        "tasks?page=" +
        this.page +
        "&include=process,processRequest,processRequest.user,user,data" +
        "&pmql=" +
        `${encodeURIComponent(pmql)}` +
        "&per_page="+
        `${this.perPage}`+
        "&order_by=ID&order_direction=DESC&non_system=true";
      this.getData(this.queryTask);
    },
    getData(query) {
      // Load from api client
      ProcessMaker.apiClient
        .get(query)
        .then((response) => {
          const dataResponse = response.data;
          this.dataTasks = this.transform(response.data);
          this.showTabTasks = false;
          if (
            dataResponse &&
            Array.isArray(dataResponse.data) &&
            dataResponse.data.length === 0
          ) {
            this.showTabTasks = true;
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
<style scoped>
.text-style {
  margin-bottom: 10px;
  color: #556271;
}
.custom-pagination{
  display: flex;
  justify-content: left;
  align-items: center;
  margin-top: 10px;
  font-weight: 400;
  font-size: 15px;
  color: #5C5C63;
  padding-bottom: 10px;
}
.class-container{
  padding-left: 8px;
}
</style>
