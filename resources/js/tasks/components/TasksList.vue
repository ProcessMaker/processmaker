<template>
  <div class="data-table">
    <div
      v-show="true"
      data-cy="tasks-table"
    >
      <filter-table
        :headers="tableHeaders"
        :data="data"
        :unread="unreadColumnName"
        @table-row-click="handleRowClick"
        @table-row-mouseover="handleRowMouseover"
        @table-row-mouseleave="handleRowMouseleave"
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
                <template v-if="header.field === 'due_at'">
                  <span :class="['badge', 'badge-'+row['color_badge'], 'due-'+row['color_badge']]">
                    {{ formatRemainingTime(getNestedPropertyValue(row, header.field)) }}
                  </span>
                  <span>{{ row["due_date"] }}</span>
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
            </template>
          </td>
        </template>
      </filter-table>
      <task-tooltip
        :position="rowPosition"
        v-show="isTooltipVisible"
      >
        <template v-slot:task-tooltip-body>
          <div
            @mouseover="clearHideTimer"
            @mouseleave="hideTooltip"
          >
          <span>
            <i
              v-if="!verifyURL('saved-searches')"
              class="fa fa-eye py-2"
              @click="previewTasks(tooltipRowData)"
            />
          </span>
          <ellipsis-menu
            :actions="actions"
            :data="tooltipRowData"
            :divider="false"
          />
          </div>
        </template>
      </task-tooltip>
      <data-loading
        v-show="shouldShowLoader"
        :for="/tasks\?page|results\?page/"
        :empty="$t('Congratulations')"
        :empty-desc="$t('You don\'t currently have any tasks assigned to you')"
        empty-icon="beach"
      />
      <pagination-table
        :meta="data.meta"
        @page-change="changePage"
      />
    </div>
    <tasks-preview
      v-if="!verifyURL('saved-searches')"
      ref="preview"
    />
  </div>
</template>

<script>
import Vue from "vue";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import AvatarImage from "../../components/AvatarImage";
import isPMQL from "../../modules/isPMQL";
import moment from "moment";
import { createUniqIdsMixin } from "vue-uniq-ids";
import { FilterTable } from "../../components/shared";
import TasksPreview from "./TasksPreview.vue";
import ListMixin from "./ListMixin";
import PMColumnFilterPopover from "../../components/PMColumnFilterPopover/PMColumnFilterPopover.vue";
import PMColumnFilterPopoverCommonMixin from "../../common/PMColumnFilterPopoverCommonMixin.js";
import paginationTable from "../../components/shared/PaginationTable.vue";
import TaskTooltip from "./TaskTooltip.vue";
import PMColumnFilterIconAsc from "../../components/PMColumnFilterPopover/PMColumnFilterIconAsc.vue";
import PMColumnFilterIconDesc from "../../components/PMColumnFilterPopover/PMColumnFilterIconDesc.vue";
import FilterTableBodyMixin from "../../components/shared/FilterTableBodyMixin";

const uniqIdsMixin = createUniqIdsMixin();

Vue.component("AvatarImage", AvatarImage);
Vue.component("TasksPreview", TasksPreview);

export default {
  components: {
    EllipsisMenu,
    PMColumnFilterPopover,
    paginationTable,
    TaskTooltip,
    PMColumnFilterIconAsc,
    PMColumnFilterIconDesc,
  },
  mixins: [datatableMixin,
    dataLoadingMixin,
    uniqIdsMixin,
    ListMixin,
    PMColumnFilterPopoverCommonMixin,
    FilterTableBodyMixin],
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
      actions: [
        {
          value: "edit",
          content: "Open Task",
          icon: "fas fa-caret-square-right",
          link: true,
          href: "/tasks/{{id}}/edit",
        },
        {
          value: "showRequestSummary",
          content: "Open Request",
          icon: "fas fa-clipboard",
          link: true,
          href: "/requests/{{process_request.id}}",
        },
      ],
      orderBy: "ID",
      order_direction: "DESC",
      status: "",
      sortOrder: [
        {
          field: "ID",
          sortField: "ID",
          direction: "DESC",
        },
      ],
      fields: [],
      previousFilter: "",
      previousPmql: "",
      tableHeaders: [],
      unreadColumnName: "user_viewed_at",
      rowPosition: {},
      tooltipRowData: {},
      isTooltipVisible: false,
      hideTimer: null,
    };
  },
  computed: {
    endpoint() {
      if (this.savedSearch !== false) {
        return `saved-searches/${this.savedSearch}/results`;
      }

      return "tasks";
    },
  },
  watch: {
    data(newData) {
      if (Array.isArray(newData.data) && newData.data.length > 0) {
        for (let record of newData.data) {
          //format Status
          record["case_number"] = this.formatCaseNumber(record.process_request);
          record["case_title"] = this.formatCaseTitle(record.process_request);
          record["status"] = this.formatStatus(record);
          record["assignee"] = this.formatAvatar(record["user"]);
          record["request"] = this.formatRequest(record);
          record["due_date"] = this.formatDueDate(record["due_at"]);
          record["color_badge"] = this.formatColorBadge(record["due_at"]);
          record["process"] = this.formatProcess(record);
          record["task_name"] = this.formatActiveTask(record);
        }
      }
    },
  },
  mounted: function mounted() {
    this.getAssignee("");
    this.getProcess();
    this.setupColumns();
    this.getFilterConfiguration("taskFilter");
    const params = new URL(document.location).searchParams;
    const successRouting = params.get("successfulRouting") === "true";
    if (successRouting) {
      ProcessMaker.alert(this.$t("The request was completed."), "success");
    }
  },
  methods: {
    openRequest(data) {
      return `/requests/${data.id}`;
    },
    formatCaseNumber(processRequest) {
      return `
      <a href="${this.openRequest(processRequest, 1)}"
         class="text-nowrap">
         # ${processRequest.case_number}
      </a>`;
    },
    formatCaseTitle(processRequest) {
      return `
      <a href="${this.openRequest(processRequest, 1)}"
         class="text-nowrap">
         ${processRequest.case_title_formatted || ""}
      </a>`;
    },
    formatActiveTask(row) {
      return `
      <a href="${this.openTask(row)}"
        class="text-nowrap">
        ${row.element_name}
      </a>`;
    },
    setupColumns() {
      const columns = this.getColumns();
      this.tableHeaders = this.getColumns();
    },
    getColumns() {
      if (this.$props.columns) {
        return this.$props.columns;
      }
      // from query string status=CLOSED
      const isStatusCompletedList = window.location.search.includes("status=CLOSED");
      const columns = [
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
          name: "__slot:case_number",
          sortable: true,
          default: true,
          width: 220,
          truncate: true,
        },
        {
          label: this.$t("Process"),
          field: "process",
          sortable: true,
          default: true,
          width: 140,
          truncate: true,
        },
        {
          label: this.$t("Task"),
          field: "task_name",
          sortable: true,
          default: true,
          width: 140,
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
          label: this.$t("Due date"),
          field: "due_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 140,
        }
      ];
      if (isStatusCompletedList) {
        columns.push({
          label: this.$t("Completed"),
          field: "completed_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 140,
        });
      }
      return columns;
    },
    onAction(action, rowData, index) {
      let link = "";
      if (action === "edit") {
        link = `/tasks/${rowData.id}/edit`;
      }

      if (action === "showRequestSummary") {
        link = `/requests/${rowData.process_request.id}`;
      }
      return link;
    },
    previewTasks(info) {
      this.$refs.preview.showSideBar(info, this.data.data, true);
    },
    formatStatus(props) {
      let color;
      let label;

      if (props.status === "ACTIVE" && props.isSelfService) {
        color = "danger";
        label = "Self Service";
      } if (props.status === "ACTIVE") {
        color = "success";
        label = "In Progress";
      } if (props.status === "CLOSED") {
        color = "primary";
        label = "Completed";
      }
      return `
        <span class="badge badge-${color} status-${color}">
          ${label}
        </span>`;
    },
    formatAsignee(participants) {
      return {
        component: "AvatarImage",
        props: {
          size: "25",
          "input-data": participants,
          "hide-name": false,
        },
      };
    },
    formatDueDate(date) {
      return moment(date).format("MM/DD/YY HH:mm");
    },
    formatColorBadge(date) {
      const days = this.remainingTime(date);
      return days >= 0 ? "primary" : "danger";
    },
    formatRequest(request) {
      return `#${request.process_request.id} ${request.process.name}`;
    },
    formatRemainingTime(date) {
      const millisecondsPerDay = 1000 * 60 * 60 * 24;
      const remaining = this.remainingTime(date);
      const daysRemaining = Math.ceil(remaining / millisecondsPerDay);
      if (daysRemaining <= 1 && daysRemaining >= -1) {
        const hoursRemaining = Math.ceil(remaining / (1000 * 60 * 60));
        return `${hoursRemaining}H`;
      }

      return `${daysRemaining}D`;
    },
    remainingTime(date) {
      const currentDate = new Date();
      const formatDate = moment(date).format("YYYY-MM-DD");
      const endDate = new Date(formatDate);
      return endDate - currentDate;
    },
    formatProcess(request) {
      return request.process.name;
    },
    openTask(task) {
      return `/tasks/${task.id}/edit`;
    },
    handleRowClick(row) {
      window.location.href = this.openTask(row);
    },
    handleRowMouseover(row) {
      this.clearHideTimer();

      const tableContainer = document.getElementById("table-container");
      const rectTableContainer = tableContainer.getBoundingClientRect();
      const topAdjust = rectTableContainer.top;

      const tasksAlert = document.querySelector('[data-cy="tasks-alert"]');
      let elementHeight = tasksAlert ? tasksAlert.clientHeight - 14 : 0;

      const savedSearch = this.verifyURL("saved-searches");
      if (savedSearch) {
        elementHeight += 36;
      }

      this.isTooltipVisible = true;
      this.tooltipRowData = row;

      const rowElement = document.getElementById(`row-${row.id}`);
      const rect = rowElement.getBoundingClientRect();

      const selectedFiltersBar = document.querySelector('.selected-filters-bar');
      const selectedFiltersBarHeight = selectedFiltersBar ? selectedFiltersBar.offsetHeight : 0;

      elementHeight -= selectedFiltersBarHeight;

      const rightBorderX = rect.right;
      const bottomBorderY = rect.bottom - topAdjust + 48 - elementHeight;

      this.rowPosition = {
        x: rightBorderX,
        y: bottomBorderY,
      };
    },
    handleRowMouseleave(visible) {
      this.startHideTimer();
    },
    startHideTimer() {
      this.hideTimer = setTimeout(() => {
        this.hideTooltip();
      }, 700);
    },
    clearHideTimer() {
      clearTimeout(this.hideTimer);
    },
    hideTooltip() {
      this.isTooltipVisible = false;
    },
    sanitizeTooltip(html) {
      let cleanHtml = html.replace(/<script(.*?)>[\s\S]*?<\/script>/gi, "");
      cleanHtml = cleanHtml.replace(/<style(.*?)>[\s\S]*?<\/style>/gi, "");
      cleanHtml = cleanHtml.replace(/<(?!img|input|meta|time|button|select|textarea|datalist|progress|meter)[^>]*>/gi, "");
      cleanHtml = cleanHtml.replace(/\s+/g, " ");

      return cleanHtml;
    },
    getStatus() {
      return ["Self Service", "In Progress", "Completed"];
    },
    /**
     * This method is used in PMColumnFilterPopoverCommonMixin.js
     * @param {string} by
     * @param {string} direction
     */
    setOrderByProps(by, direction) {
      by = this.getAliasColumnForOrderBy(by);
      this.orderBy = by;
      this.order_direction = direction;
      this.sortOrder[0].sortField = by;
      this.sortOrder[0].direction = direction;
    },
    verifyURL(string) {
      const currentUrl = window.location.href;
      const isInUrl = currentUrl.includes(string);
      return isInUrl;
    },
    /**
     * This method is used in PMColumnFilterPopoverCommonMixin.js
     */
    storeFilterConfiguration() {
      let url = "users/store_filter_configuration/taskFilter";
      if (this.$props.columns) {
        url = "saved-searches/" + this.savedSearch + "/advanced-filters";
      }
      let config = {
        filter: this.advancedFilter,
        order: {
          by: this.orderBy,
          direction: this.order_direction
        }
      };
      ProcessMaker.apiClient.put(url, config);
      window.Processmaker.filter_user = config;
    },
    getTypeColumnFilter(value) {
      let type = "Field";
      if (value === "case_number" || value === "case_title") {
        type = "Relationship";
      }
      if (value === "process") {
        type = "Process";
      }
      if (value === "status") {
        type = "Status";
      }
      return type;
    },
    getAliasColumnForFilter(value) {
      if (value === "case_number") {
        value = "processRequest.case_number";
      }
      if (value === "case_title") {
        value = "processRequest.case_title";
      }
      if (value === "task_name") {
        value = "element_name";
      }
      if (value === "assignee") {
        value = "user_id";
      }
      return value;
    },
    getAliasColumnForOrderBy(value) {
      if (value === "case_number") {
        value = "process_requests.case_number";
      }
      if (value === "case_title") {
        value = "process_requests.case_title_formatted";
      }
      if (value === "process") {
        value = "process_requests.name";
      }
      if (value === "task_name") {
        value = "element_name";
      }
      if (value === "assignee") {
        value = "user.fullname";
      }
      return value;
    }
  }
};
</script>

<style>
.tasks-table-card {
  padding: 0;
}
.due-danger {
  background-color:rgba(237, 72, 88, 0.2);
  color: rgba(237, 72, 88, 1);
  font-weight: 600;
  border-radius: 5px;
}
.due-primary {
  background: rgba(205, 221, 238, 1);
  color: rgba(86, 104, 119, 1);
  font-weight: 600;
  border-radius: 5px;
}
</style>
<style lang="scss" scoped>
  @import url("../../../sass/_scrollbar.scss");
</style>