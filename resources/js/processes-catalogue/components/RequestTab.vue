<template>
  <div class="mt-3">
        <div class="bg-white" v-if="!showTabRequests">
          <filter-table
            :headers="tableHeadersRequests"
            :data="dataRequests"
            @table-row-click="handleRowClick"
          />
          <pagination-table
            :meta="dataRequests.meta"
            @page-change="changePageRequests"
          />
        </div>
        <div v-else>
          <default-tab
            :alt-text="$t('No Image')"
            :title-text="$t('You have made no requests of this process.')"
            :description-text="$t('All your requests will be shown here')"
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
import isPMQL from "../../modules/isPMQL";
import ListMixin from "../../requests/components/ListMixin";
import { FilterTable } from "../../components/shared";
import moment from "moment";
import { createUniqIdsMixin } from "vue-uniq-ids";
import { methodsTabMixin } from "./TabMixing.js";
const uniqIdsMixin = createUniqIdsMixin();

Vue.component("AvatarImage", AvatarImage);

export default {
  components: {
    PMColumnFilterPopover,
    paginationTable,
    DefaultTab,
  },
  mixins: [uniqIdsMixin, ListMixin, methodsTabMixin ],
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
          label: "STATUS",
          field: "status",
          sortable: true,
          default: true,
          width: 150,
        },
        {
          label: "STARTED",
          field: "initiated_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 160,
        },
        {
          label: "COMPLETED",
          field: "completed_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 160,
        },
      ],
      dataRequests: {},
      savedSearch: false,
      queryRequest: "",
      perPage: 10,
    };
  },
  mounted() {
    this.queryBuilder();
  },
  methods: {
    // jsonRows(rows) {
    //   if (rows.length === 0 || !_.has(_.head(rows), "_json")) {
    //     return rows;
    //   }
    //   return rows.map((row) => JSON.parse(row._json));
    // },
    changePageRequests(page) {
      this.page = page;
      this.queryBuilder();
    },
    openRequest(data, index) {
      return `/requests/${data.id}`;
    },
    handleRowClick(row) {
      window.location.href = this.openRequest(row, 1);
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
        record["status"] = this.formatStatus(record["status"]);
        record["participants"] = this.formatParticipants(record["participants"]);
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
      this.tabRequests();
    },
    tabRequests() {
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
      this.getData(this.queryRequest, "requests");
    },
    getData(query, type) {
    // Load from api client
      ProcessMaker.apiClient
        .get(query)
        .then((response) => {
          const dataResponse = response.data
          this.dataRequests = this.transform(response.data)
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
