<template>
  <div>
    <div
      v-show="true"
    >
      <filter-table
        :headers="tableHeaders"
        :data="data"
        :unread="unreadColumnName"
        @table-row-click="handleRowClick"
      >
        <!-- Slot Table Header -->
        <template v-for="(column, index) in tableHeaders" v-slot:[column.field]>
          <PMColumnFilterIconAsc v-if="column.sortAsc"></PMColumnFilterIconAsc>
          <PMColumnFilterIconDesc v-if="column.sortDesc"></PMColumnFilterIconDesc>
          <div :key="index" style="display: inline-block;">{{ column.label }}</div>
        </template>
        <!-- Slot Table Header filter Button -->
        <template v-for="(column, index) in tableHeaders" v-slot:[`filter-${column.field}`]>
            <PMColumnFilterPopover v-if="column.sortable" 
                                   :key="index" 
                                   :id="'pm-table-column-'+index" 
                                   :type="'Field'"
                                   :value="column.field"
                                   :format="getFormat(column)"
                                   :formatRange="getFormatRange(column)"
                                   :operators="getOperators(column)"
                                   :viewConfig="getViewConfigFilter()"
                                   :container="''"
                                   :boundary="'viewport'"
                                   @onChangeSort="onChangeSort($event, column.field)"
                                   @onApply="onApply($event, column.field)"
                                   @onClear="onClear(column.field)"
                                   @onUpdate="onUpdate($event, column.field)">
            </PMColumnFilterPopover>
        </template>
        <!-- Slot Table Body -->
        <template v-for="(row, rowIndex) in data.data" v-slot:[`row-${rowIndex}`]>
          <td
            v-for="(header, colIndex) in tableHeaders"
            :key="colIndex"
          >
            <template v-if="containsHTML(getNestedPropertyValue(row, header.field))">
              <div
                :id="`element-${rowIndex}-${colIndex}`"
                :class="{ 'pm-table-truncate': header.truncate }"
                :style="{ maxWidth: header.width + 'px' }"
              >
                <div v-html="sanitize(getNestedPropertyValue(row, header.field))"></div>
              </div>
              <b-tooltip
                v-if="header.truncate"
                :target="`element-${rowIndex}-${colIndex}`"
                custom-class="pm-table-tooltip"
              >
                {{ sanitizeTooltip(getNestedPropertyValue(row, header.field)) }}
              </b-tooltip>
            </template>
            <template v-else>
              <template v-if="isComponent(row[header.field])">
                <component
                  :is="row[header.field].component"
                  v-bind="row[header.field].props"
                >
                </component>
              </template>
              <template v-else>
                <div
                  :id="`element-${rowIndex}-${colIndex}`"
                  :class="{ 'pm-table-truncate': header.truncate }"
                  :style="{ maxWidth: header.width + 'px' }"
                >
                  {{ getNestedPropertyValue(row, header.field) }}
                  <b-tooltip
                    v-if="header.truncate"
                    :target="`element-${rowIndex}-${colIndex}`"
                    custom-class="pm-table-tooltip"
                  >
                    {{ getNestedPropertyValue(row, header.field) }}
                  </b-tooltip>
                </div>
              </template>
            </template>
          </td>
        </template>
      </filter-table>
    </div>
    <data-loading
      v-show="shouldShowLoader"
      :for="/requests\?page|results\?page/"
      :empty="$t('No results have been found')"
      :empty-desc="$t(`We apologize, but we were unable to find any results that match your search. 
Please consider trying a different search. Thank you`)"
      empty-icon="noData"
    />
    <pagination-table
        :meta="data.meta"
        @page-change="changePage"
    />
  </div>
</template>

<script>
import Vue from "vue";
import moment from "moment";
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import AvatarImage from "../../components/AvatarImage";
import isPMQL from "../../modules/isPMQL";
import ListMixin from "./ListMixin";
import { FilterTable } from "../../components/shared";
import PMColumnFilterPopover from "../../components/PMColumnFilterPopover/PMColumnFilterPopover.vue";
import PMColumnFilterPopoverCommonMixin from "../../common/PMColumnFilterPopoverCommonMixin.js";
import paginationTable from "../../components/shared/PaginationTable.vue";
import PMColumnFilterIconAsc from "../../components/PMColumnFilterPopover/PMColumnFilterIconAsc.vue";
import PMColumnFilterIconDesc from "../../components/PMColumnFilterPopover/PMColumnFilterIconDesc.vue";
import FilterTableBodyMixin from "../../components/shared/FilterTableBodyMixin";

const uniqIdsMixin = createUniqIdsMixin();

Vue.component("AvatarImage", AvatarImage);

export default {
  components: {
    PMColumnFilterPopover,
    paginationTable,
    PMColumnFilterIconAsc,
    PMColumnFilterIconDesc
  },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ListMixin, PMColumnFilterPopoverCommonMixin, FilterTableBodyMixin],
  props: {
    filter: {},
    columns: {},
    pmql: {},
    savedSearch: {
      default: false,
    },
  },
  data() {
    return {
      orderBy: "id",
      orderDirection: "DESC",
      additionalParams: "",
      sortOrder: [
        {
          field: "id",
          sortField: "id",
          direction: "desc",
        },
      ],
      fields: [],
      previousFilter: "",
      previousPmql: "",
      tableHeaders: [],
      unreadColumnName: "user_viewed_at",
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
    this.getParticipants("");
    this.setupColumns();
    this.getFilterConfiguration("requestFilter");
  },
  methods: {
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
          case "case_title":
            field.name = "__slot:case_title";
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
    getColumns() {
      if (this.$props.columns) {
        return this.$props.columns;
      }
      return [
        {
          label: this.$t("Case #"),
          field: "case_number",
          sortable: true,
          default: true,
          width: 80,
        },
        {
          label: this.$t("Case title"),
          field: "case_title",
          sortable: true,
          default: true,
          truncate: true,
          width: 220,
        },
        {
          label: this.$t("Process"),
          field: "name",
          sortable: true,
          default: true,
          width: 220,
          truncate: true,
        },
        {
          label: this.$t("Task"),
          field: "active_tasks",
          sortable: false,
          default: true,
          width: 140,
          truncate: true,
        },
        {
          label: this.$t("Participants"),
          field: "participants",
          sortable: true,
          default: true,
          width: 160,
          truncate: true,
        },
        {
          label: this.$t("Status"),
          field: "status",
          sortable: true,
          default: true,
          width: 100,
        },
        {
          label: this.$t("Started"),
          field: "initiated_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 160,
        },
        {
          label: this.$t("Completed"),
          field: "completed_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 160,
        },
      ];
    },
    openRequest(data, index) {
      return `/requests/${data.id}`;
    },
    openTask(task) {
      return `/tasks/${task.id}/edit`;
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
        ' status-' +
        color +
        '">' +
        this.$t(label) +
        "</span>"
      );
    },
    formatActiveTasks(value) {
      let htmlString = '';
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
    formatCaseNumber(value) {
      return `
      <a href="${this.openRequest(value, 1)}"
         class="text-nowrap">
         # ${value.case_number}
      </a>`;
    },
    formatCaseTitle(value) {
      return `
      <a href="${this.openRequest(value, 1)}"
         class="text-nowrap">
         ${value.case_title_formatted || value.case_title || ""}
      </a>`;
    },
    formatParticipants(participants) {
      return {
        component: "AvatarImage",
        props: {
          size: "25",
          "input-data": participants,
          "hide-name": false,
          vertical: true,
        },
      };
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
        record["case_title"] = this.formatCaseTitle(record);
        if (record["active_tasks"]) {
          record["active_tasks"] = this.formatActiveTasks(record["active_tasks"]);
        }
        record["status"] = this.formatStatus(record["status"]);
        record["participants"] = this.formatParticipants(record["participants"]);
      }
      return data;
    },
    fetch() {
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

        if (filter && filter.length) {
          if (filter.isPMQL()) {
            pmql = `(${pmql}) and (${filter})`;
            filter = '';
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

        // Load from our api client
        ProcessMaker.apiClient
          .get(
            `${this.endpoint}?page=` +
            this.page +
            "&per_page=" +
            this.perPage +
            "&include=process,participants,activeTasks,data" +
            "&pmql=" +
            encodeURIComponent(pmql) +
            "&filter=" +
            filter +
            "&order_by=" +
            (this.orderBy === "__slot:ids" ? "id" : this.orderBy) +
            "&order_direction=" +
            this.orderDirection +
            this.additionalParams + 
            this.getAdvancedFilter(),
            {
              cancelToken: new CancelToken((c) => {
                this.cancelToken = c;
              }),
            },
          )
          .then((response) => {
            this.data = this.transform(response.data);
          }).catch((error) => {
            this.data = [];
            if (error.code === "ERR_CANCELED") {
              return;
            }
            if (_.has(error, 'response.data.message')) {
              ProcessMaker.alert(error.response.data.message, 'danger');
            } else if (_.has(error, 'response.data.error')) {
              return;
            } else {
              throw error;
            }
          });
      });
    },
    handleRowClick(row) {
      window.location.href = this.openRequest(row, 1);
    },
    /**
     * This method is used in PMColumnFilterPopoverCommonMixin.js
     * @returns {Array}
     */
    getStatus() {
      return ["In Progress", "Completed", "Error", "Canceled"];
    },
    /**
     * This method is used in PMColumnFilterPopoverCommonMixin.js
     * @param {string} by
     * @param {string} direction
     */
    setOrderByProps(by, direction) {
      by = this.getAliasColumnForOrderBy(by);
      this.orderBy = by;
      this.orderDirection = direction;
      this.sortOrder[0].sortField = by;
      this.sortOrder[0].direction = direction;
    },
    sanitizeTooltip(html) {
      let cleanHtml = html.replace(/<script(.*?)>[\s\S]*?<\/script>/gi, "");
      cleanHtml = cleanHtml.replace(/<style(.*?)>[\s\S]*?<\/style>/gi, "");
      cleanHtml = cleanHtml.replace(/<(?!img|input|meta|time|button|select|textarea|datalist|progress|meter)[^>]*>/gi, "");
      cleanHtml = cleanHtml.replace(/\s+/g, " ");

      return cleanHtml;
    },
    /**
     * This method is used in PMColumnFilterPopoverCommonMixin.js
     */
    storeFilterConfiguration() {
      let url = "users/store_filter_configuration/requestFilter";
      if (this.$props.columns) {
        url = "saved-searches/" + this.savedSearch + "/advanced-filters";
      }
      let config = {
        filter: this.advancedFilter,
        order: {
          by: this.orderBy,
          direction: this.orderDirection
        },
      };
      ProcessMaker.apiClient.put(url, config);
      window.Processmaker.filter_user = config;
    },
    getTypeColumnFilter(value) {
      let type = "Field";
      if (value === "case_number" || value === "case_title") {
        type = "Request";
      }
      if (value === "process") {
        type = "Process";
      }
      if (value === "active_tasks") {
        type = "Task";
      }
      if (value === "participants") {
        type = "Participants";
      }
      if (value === "status") {
        type = "Status";
      }
      return type;
    },
    getAliasColumnForFilter(value) {
      if (value === "active_tasks") {
        value = "id";
      }
      return value;
    },
    getAliasColumnForOrderBy(value) {
      if (value === "process") {
        value = "process.name";
      }
      if (value === "active_tasks") {
        value = "id";
      }
      if (value === "participants") {
        value = "id";
      }
      return value;
    }
  }
};
</script>
<style>
  .pm-table-ellipsis-column{
    text-transform: uppercase;
  }
</style>
<style lang="scss" scoped>
  @import url("../../../sass/_scrollbar.scss");
</style>
