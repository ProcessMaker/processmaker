<template>
  <div class="data-table">
    <Recommendations v-if="showRecommendations" />
    <div
      v-show="true"
      data-cy="tasks-table"
    >
      <filter-table
        ref="filterTable"
        :headers="tableHeaders"
        :data="data"
        :unread="unreadColumnName"
        :loading="shouldShowLoader"
        :selected-row="selectedRow"
        :table-name="tableName"
        @table-row-click="handleRowClick"
        @table-row-mouseover="handleRowMouseover"
        @table-tr-mouseleave="handleTrMouseleave"
        @table-row-mouseleave="handleRowMouseleave"
        @table-column-mouseover="handleColumnMouseover"
        @table-column-mouseleave="handleColumnMouseleave"
      >
        <!-- Slot Table Header -->
        <template
          v-for="(column, index) in visibleHeaders"
          v-slot:[column.field]
        >
          <div
            :key="`tasks-table-column-${index}`"
            :id="`tasks-table-column-${column.field}`"
            class="pm-table-column-header-text"
          >
            <img
              v-if="column.field === 'is_priority'"
              src="/img/priority-header.svg"
              alt="priority-header"
              width="20"
              height="20"
            />
            <span v-else>{{ $t(column.label) }}</span>
          </div>
          <b-tooltip
            :key="index"
            :target="`tasks-table-column-${column.field}`"
            custom-class="pm-table-tooltip-header"
            placement="bottom"
            :delay="0"
            @show="checkIfTooltipIsNeeded"
          >
            {{ $t(column.label) }}
          </b-tooltip>
        </template>
        <!-- Slot Table Header filter Button -->
        <template
          v-for="(column, index) in visibleHeaders"
          v-slot:[`filter-${column.field}`]
        >
          <PMColumnFilterPopover
            v-if="column.sortable"
            :key="index"
            :id="'pm-table-column-' + index"
            type="Field"
            :value="column.field"
            :format="getFormat(column)"
            :formatRange="getFormatRange(column)"
            :operators="getOperators(column)"
            :viewConfig="getViewConfigFilter()"
            :container="''"
            :boundary="'viewport'"
            :columnSortAsc="column.sortAsc"
            :columnSortDesc="column.sortDesc"
            :filterApplied="column.filterApplied"
            :columnMouseover="columnMouseover"
            @onChangeSort="onChangeSort($event, column.field)"
            @onApply="onApply($event, column.field)"
            @onClear="onClear(column.field)"
            @onUpdate="onUpdate($event, column.field)"
          >
          </PMColumnFilterPopover>
        </template>
        <!-- Slot Table Body -->
        <template
          v-for="(row, rowIndex) in data.data"
          v-slot:[`row-${rowIndex}`]
        >
          <td
            v-for="(header, colIndex) in visibleHeaders"
            :class="{ 'pm-table-filter-applied-tbody': header.sortAsc || header.sortDesc }"
            :key="colIndex"
          >
            <!-- Slot for floating buttons -->
            <template v-if="colIndex === visibleHeaders.length-1">
              <TaskListRowButtons 
                    :ref="'taskListRowButtons-'+rowIndex"
                    :buttons="taskTooltipButtons"
                    :row="row"
                    :rowIndex="rowIndex"
                    :colIndex="colIndex"
                    :showButtons="isTooltipVisible">
                <template v-slot:body>
                  <slot name="tooltip" v-bind:previewTasks="previewTasks">
                  </slot>
                </template>
              </TaskListRowButtons>
            </template>
            <template v-if="containsHTML(getNestedPropertyValue(row, header))">
              <div
                :id="`element-${rowIndex}-${colIndex}`"
                :class="{ 'pm-table-truncate': header.truncate }"
                :style="{ maxWidth: header.width + 'px' }"
              >
                <span
                  v-html="sanitize(getNestedPropertyValue(row, header))"
                ></span>
              </div>
              <b-tooltip
                v-if="header.truncate"
                :target="`element-${rowIndex}-${colIndex}`"
                custom-class="pm-table-tooltip"
                @show="checkIfTooltipIsNeeded"
                placement="topright"
                trigger="hover"
                boundary="viewport"
                :delay="{'show':0,'hide':0}"
              >
                {{ sanitizeTooltip(getNestedPropertyValue(row, header)) }}
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
                  <span
                    :class="[
                      'badge',
                      'badge-' + row['color_badge'],
                      'due-' + row['color_badge'],
                    ]"
                  >
                    {{ formatRemainingTime(row.due_at) }}
                  </span>
                  <span>{{ getNestedPropertyValue(row, header) }}</span>
                </template>
                <template v-else-if="header.field === 'is_priority'">
                  <span>
                    <img
                      :src="
                        row[header.field]
                          ? '/img/priority.svg'
                          : '/img/no-priority.svg'
                      "
                      :alt="row[header.field] ? 'priority' : 'no-priority'"
                      width="20"
                      height="20"
                      @click.prevent="
                        togglePriority(row.id, !row[header.field])
                      "
                    />
                  </span>
                </template>
                <template v-else>
                  <div
                    :id="`element-${rowIndex}-${colIndex}`"
                    :class="{ 'pm-table-truncate': header.truncate }"
                    :style="{ maxWidth: header.width + 'px' }"
                  >
                    {{ getNestedPropertyValue(row, header) }}
                    <b-tooltip
                      v-if="header.truncate"
                      :target="`element-${rowIndex}-${colIndex}`"
                      custom-class="pm-table-tooltip"
                      @show="checkIfTooltipIsNeeded"
                      placement="topright"
                      trigger="hover"
                      boundary="viewport"
                      :delay="{'show':0,'hide':0}"
                    >
                      {{ getNestedPropertyValue(row, header) }}
                    </b-tooltip>
                  </div>
                </template>
              </template>
            </template>
          </td>
        </template>
      </filter-table>
      <data-loading
        v-show="shouldShowLoader && noResultsMessage === 'tasks'"
        :empty="$t('All clear')"
        :empty-desc="$t('No new tasks at this moment.')"
        empty-icon="noTasks"
        :data-loading-id="dataLoadingId"
      >
        <template v-slot:no-results>
          <slot name="no-results"></slot>
        </template>
      </data-loading>
      <default-tab
        v-if="shouldShowLoader && noResultsMessage === 'launchpad'"
        :alt-text="$t('No Image')"
        :title-text="$t('No items to show.')"
        :description-text="$t('You have to start a Case of this process.')"
      />
      <pagination-table
        v-show="!shouldShowLoader"
        :meta="data.meta"
        @page-change="changePage"
        @per-page-change="changePerPage"
      />
    </div>
    <tasks-preview
      v-if="!verifyURL('saved-searches')"
      ref="preview"
      @mark-selected-row="markSelectedRow"
      :tooltip-button="tooltipFromButton"
      @onSetViewed="setViewed"
      @onWatchShowPreview="onWatchShowPreview"
    >
      <template v-slot:header="{ close, screenFilteredTaskData, taskReady }">
        <slot name="preview-header" v-bind:close="close" v-bind:screenFilteredTaskData="screenFilteredTaskData" v-bind:taskReady="taskReady"></slot>
      </template>
    </tasks-preview>
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
import TaskListRowButtons from "./TaskListRowButtons.vue";
import { cloneDeep, get } from "lodash";
import Recommendations from "../../components/Recommendations.vue";
import DefaultTab from "../../processes-catalogue/components/DefaultTab.vue";

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
    TaskListRowButtons,
    Recommendations,
    DefaultTab,
  },
  mixins: [
    datatableMixin,
    dataLoadingMixin,
    uniqIdsMixin,
    ListMixin,
    PMColumnFilterPopoverCommonMixin,
    FilterTableBodyMixin,
  ],
  props: {
    selectedRowQuick: 0,
    filter: {},
    columns: [],
    pmql: {},
    disableTooltip: {
      default: false,
    },
    disableQuickFillTooltip: {
      default: false,
    },
    savedSearch: {
      default: false,
    },
    clone: {
      default: false,
    },
    additionalIncludes: {
      type: Array,
      default: () => [],
    },
    fromButton: {
      type: String,
      default: "",
    },
    disableRowClick: {
      type: Boolean,
      default: false,
    },
    disableRuleTooltip: {
      type: Boolean,
      default: false,
    },
    openQuickFillFromRow: {
      type: Boolean,
      default: false,
    },
    tableName: {
      type: String,
      default: "",
    },
    showRecommendations: {
      type: Boolean,
      default: false,
    },
    noResultsMessage: {
      type: String,
      default: "tasks",
    },
    verifyUrlToFalse: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      tooltipFromButton: "",
      selectedRow: 0,
      taskTooltipButtons: [
        {
          id: "openPreviewButton",
          ariaLabel: this.$t("Quick fill Preview"),
          click: this.previewTasks,
          icon: "fas fa-eye",
          title: this.$t("Preview"),
          show: !this.verifyURL('saved-searches') || false,
        },
        {
          id: "openCaseButton",
          title: this.$t("Open Case"),
          click: this.redirectToRequest,
          imgSrc: "/img/smartinbox-images/open-case.svg",
          show: !this.verifyURL('saved-searches') || true,
        },
        {
          id: "openTaskButton",
          title: this.$t("Open Task"),
          click: this.redirectToTask,
          icon: "fas fa-external-link-alt",
          show: !this.verifyURL('saved-searches') || true,
        },
      ],
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
      previousAdvancedFilter: "",
      tableHeaders: [],
      unreadColumnName: "user_viewed_at",
      rowPosition: {},
      tooltipRowData: {},
      isTooltipVisible: false,
      hideTimer: null,
      ellipsisShow: false,
      columnMouseover: null,
    };
  },
  computed: {
    now() {
      const tz = get(window, "ProcessMaker.user.timezone");
      if (tz) {
        return moment().tz(tz);
      }
      return moment();
    },
    endpoint() {
      if (this.savedSearch !== false) {
        return `saved-searches/${this.savedSearch}/results`;
      }

      return "tasks";
    },
    visibleHeaders() {
      return this.tableHeaders.filter((column) => !column.hidden);
    },
  },
  watch: {
    columns: {
      deep: true,
      handler() {
        this.setupColumns();
      }
    },
    data(newData) {
      if (Array.isArray(newData.data) && newData.data.length > 0) {
        for (let record of newData.data) {
          this.setDefaultProperties(record);
          //format Status
          record["case_number"] = this.formatCaseNumber(
            record.process_request,
            record
          );
          record["case_title"] = this.formatCaseTitle(
            record.process_request,
            record
          );
          record["status"] = this.formatStatus(record);
          record["assignee"] = this.formatAvatar(record["user"]);
          record["request"] = this.formatRequest(record);
          record["color_badge"] = this.formatColorBadge(record["due_at"]);
          record["process_obj"] = record["process"];
          record["process"] = this.formatProcess(record);
          record["element_name"] = this.formatActiveTask(record);
        }
      }
      this.$emit('count', newData.meta?.total);
      this.$emit("tab-count", newData.meta?.total);
    },
    shouldShowLoader(value) {
      if (this.apiNoResults) {
        this.$emit("data-loading", false);
      } else {
        this.$emit("data-loading", value);
      }
    },
  },
  mounted: function mounted() {
    this.setupColumns();
    this.getFilterConfiguration();

    const params = new URL(document.location).searchParams;
    const successRouting = params.get("successfulRouting") === "true";
    if (successRouting) {
      ProcessMaker.alert(this.$t("The request was completed."), "success");
    }
    this.$emit('onRendered', this);
  },
  methods: {
    markSelectedRow(value) {
      this.selectedRow = value;
    },
    getTask(taskId) {
      return this.data.data.find(task => task.id === taskId);
    },
    togglePriority(taskId, isPriority) {
      ProcessMaker.apiClient
        .put(`tasks/${taskId}/setPriority`, { is_priority: isPriority })
        .then((response) => {
          this.fetch();
        });
    },
    openRequest(data) {
      return `/requests/${data.id}`;
    },
    formatCaseNumber(processRequest, record) {
      return `
      <a href="${this.openTask(record, 1)}"
         class="text-nowrap">
         # ${processRequest.case_number || record.case_number}
      </a>`;
    },
    formatCaseTitle(processRequest, record) {
      let draftBadge = "";
      if (record.draft && record.status !== "CLOSED") {
        draftBadge = `<span class="badge badge-warning status-warning">
          ${this.$t("Draft")}
        </span>`;
      }
      return `
      ${draftBadge}
      <a href="${this.openTask(record, 1)}"
         class="text-nowrap">
         ${
           processRequest.case_title_formatted ||
           processRequest.case_title ||
           record.case_title ||
           ""
         }
      </a>`;
    },
    formatActiveTask(row) {
      return `
      <a href="${this.openTask(row)}"
        data-cy="active-task-data"
        class="text-nowrap">
        ${row.element_name}
      </a>`;
    },
    setupColumns() {
      this.tableHeaders = this.getColumns();
    },
    getColumns() {
      if (this.columns && this.columns.length > 0) {
        const exists = this.columns.some((column) => column.field === "options");
        if (!exists) {
          const customColumns = cloneDeep(this.columns);
          customColumns.push({
            label: "",
            field: "options",
            sortable: false,
            width: 180,
          });
          return customColumns;
        }
        return this.columns;
      }
      // from query string status=CLOSED
      const isStatusCompletedList =
        window.location.search.includes("status=CLOSED");
      const columns = [
        {
          label: "Case #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 84,
          fixed_width: 84,
          filter_subject: {
            type: "Relationship",
            value: "processRequest.case_number",
          },
          order_column: "process_requests.case_number",
        },
        {
          label: "Case title",
          field: "case_title",
          name: "__slot:case_number",
          sortable: true,
          default: true,
          width: 419,
          fixed_width: 419,
          truncate: true,
          filter_subject: {
            type: "Relationship",
            value: "processRequest.case_title",
          },
          order_column: "process_requests.case_title",
        },
        {
          label: "Priority",
          field: "is_priority",
          sortable: false,
          default: true,
          fixed_width: 20,
          resizable: false,
        },
        {
          label: "Task",
          field: "element_name",
          sortable: true,
          default: true,
          width: 135,
          fixed_width: 135,
          truncate: true,
          filter_subject: { value: "element_name" },
          order_column: "element_name",
        },
        {
          label: "Status",
          field: "status",
          sortable: true,
          default: true,
          width: 183,
          fixed_width: 183,
          filter_subject: { type: "Status" },
        },
        {
          label: "Due date",
          field: "due_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 200,
          fixed_width: 200,
        },
        {
          label: "Draft",
          field: "draft",
          sortable: false,
          default: true,
          hidden: true,
          width: 40,
        },
      ];
      if (isStatusCompletedList) {
        columns.push({
          label: "Completed",
          field: "completed_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 200,
          fixed_width: 200,
        });
      }
      columns.push({
        label: "",
        field: "options",
        sortable: false,
        width: 180,
      });
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
    previewTasks(info, size = null, fromButton = null) {
      this.tooltipFromButton = fromButton;
      this.selectedRow = info.id;
      this.$refs.preview.showSideBar(info, this.data.data, true, size);
    },
    setViewed(task) {
      const url = `tasks/${task.id}/setViewed`;
      const params = {
        id: task.id
      };
      ProcessMaker.apiClient
        .post(url, params)
        .then(
          (response) => {
            let taskToUpdate = this.data.data.findIndex(data => data.uuid === task.uuid);
            this.data.data[taskToUpdate].user_viewed_at = response.data.created_at;
          }
        )
        .catch((err) => {});
    },
    formatStatus(props) {
      let color = "success";
      let label = "In Progress";

      if (props.status === "ACTIVE") {
        if (props.is_self_service) {
          color = "danger";
          label = "Self Service";
        } else if (props.advanceStatus === "overdue") {
          color = "danger";
          label = "Overdue";
        }
      } else if (props.status === "CLOSED") {
        color = "primary";
        label = "Completed";
      }

      return `
        <span class="badge badge-${color} status-${color}">
          ${label}
        </span>`;
    },
    formatDueDate(date) {
      return date === null ? "-" : moment(date).format("MM/DD/YY HH:mm");
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
      date = moment(date);
      if (!date.isValid()) {
        return 0;
      }
      return date.diff(this.now);
    },
    formatProcess(request) {
      return request.process.name;
    },
    openTask(task) {
      return `/tasks/${task.id}/edit`;
    },
    handleRowClick(row, event) {
      const targetElement = event.target;
      const isPriorityIcon =
        targetElement.tagName.toLowerCase() === "img" &&
        (targetElement.alt === "priority" ||
          targetElement.alt === "no-priority");
      if (this.fromButton === 'previewTask') {
        return this.previewTasks(this.tooltipRowData, 93);
      }
      if (this.fromButton === 'fullTask') {
        return this.previewTasks(this.tooltipRowData, 50);
      }
      if (this.fromButton === 'inboxRules') {
        return this.previewTasks(this.tooltipRowData, 50, 'inboxRules');
      }
    },
    handleShowEllipsis() {
      this.ellipsisShow = true;
    },
    handleHideEllipsis() {
      this.ellipsisShow = false;
    },
    handleRowMouseover(row, index) {
      if (this.ellipsisShow) {
        this.isTooltipVisible = !this.disableRuleTooltip;
        this.clearHideTimer();
        return;
      }
      this.clearHideTimer();

      const tableContainer = document.getElementById("table-container");
      const rectTableContainer = tableContainer.getBoundingClientRect();
      const topAdjust = rectTableContainer.top;

      let elementHeight = 28;

      this.isTooltipVisible = !this.disableRuleTooltip;
      this.tooltipRowData = row;

      const rowElement = document.getElementById(`row-${row.id}`);
      let yPosition = 0;

      const rect = rowElement.getBoundingClientRect();
      yPosition = rect.top + window.scrollY;

      const selectedFiltersBar = document.querySelector(
        ".selected-filters-bar"
      );
      const selectedFiltersBarHeight = selectedFiltersBar
        ? selectedFiltersBar.offsetHeight
        : 0;

      elementHeight -= selectedFiltersBarHeight;

      let rightBorderX = rect.right;

      let bottomBorderY = 0;
      if(this.fromButton === "" || this.fromButton === "previewTask"){
        bottomBorderY = yPosition - topAdjust + 100 - elementHeight;
      }
      if(this.fromButton === "fullTask"){
        bottomBorderY = yPosition;
      }
      if(this.fromButton === "inboxRules"){
        bottomBorderY = rect.bottom - topAdjust + 90 - elementHeight;
      }
      //rowPosition deprecated is not used
      this.rowPosition = {
        x: rightBorderX,
        y: bottomBorderY,
      };
      this.taskListRowButtonsShow(row, index);
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
      if (this.ellipsisShow) {
        return;
      }
      this.isTooltipVisible = false;
    },
    removeBadgeSpan(html) {
      const badgeSpanRegex = /<span class="badge badge-warning status-warning">([^<]+)<\/span>/g;
      return html.replace(badgeSpanRegex, "");
    },
    sanitizeTooltip(html) {
      let cleanHtml = html.replace(/<script(.*?)>[\s\S]*?<\/script>/gi, "");
      cleanHtml = cleanHtml.replace(/<style(.*?)>[\s\S]*?<\/style>/gi, "");
      cleanHtml = this.removeBadgeSpan(cleanHtml);
      cleanHtml = cleanHtml.replace(
        /<(?!img|input|meta|time|button|select|textarea|datalist|progress|meter)[^>]*>/gi,
        ""
      );
      cleanHtml = cleanHtml.replace(/\s+/g, " ");

      return cleanHtml;
    },
    getStatus() {
      return [
        { value: "Self Service", text: this.$t("Self Service") },
        { value: "In Progress", text: this.$t("In Progress") },
        { value: "Completed", text: this.$t("Completed") },
      ];
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
      let isInUrl = currentUrl.includes(string);
      if (this.verifyUrlToFalse) {
        isInUrl = false;
      }
      return isInUrl;
    },
    /**
     * This method is used in PMColumnFilterPopoverCommonMixin.js
     */
    filterConfiguration() {
      return {
        order: {
          by: this.orderBy,
          direction: this.order_direction,
        },
        type: "taskFilter",
      };
    },
    setDefaultProperties(record) {
      if (!("process_request" in record)) {
        record.process_request = {
          id: null
        };
      }
      if (!("process" in record)) {
          record.process = {
          name: null
        };
      }
    },
    onWatchShowPreview(value) {
      this.$emit('onWatchShowPreview', value);
    },
    redirectToTask(task) {
      window.location.href = this.openTask(task);
    },
    redirectToRequest(task) {
      window.location.href = this.openRequest(task.process_request);
    },
    handleTrMouseleave(row, index) {
      this.taskListRowButtonsHide(row, index);
    },
    /**
     * TaskListRowButtons replaces the TaskTooltip component. 
     * Please ensure that any methods related to TaskTooltip are cleared.
     * @param {object} row
     * @param {int} index
     */
    taskListRowButtonsShow(row, index) {
      let container = this.$refs.filterTable.$el;
      let scrolledWidth = container.scrollWidth - container.clientWidth - container.scrollLeft;
      let widthTd = this.$refs["taskListRowButtons-" + index][0].$el.parentNode.offsetWidth - 24;
      this.$refs["taskListRowButtons-" + index][0].show();
      this.$refs["taskListRowButtons-" + index][0].setMargin(scrolledWidth - widthTd);
    },
    /**
     * TaskListRowButtons replaces the TaskTooltip component. 
     * Please ensure that any methods related to TaskTooltip are cleared.
     * @param {object} row
     * @param {int} index
     */
    taskListRowButtonsHide(row, index) {
      this.$refs["taskListRowButtons-" + index][0].close();
    },
    handleColumnMouseover(column) {
      this.columnMouseover = column;
    },
    handleColumnMouseleave() {
      this.columnMouseover = null;
    },
  },
};
</script>

<style>
.tasks-table-card {
  padding: 0;
}
.due-danger {
  background-color: rgba(237, 72, 88, 0.2);
  color: rgba(0, 0, 0, 0.75);
  font-weight: 700;
  border-radius: 5px;
  padding: 7px;
}
.due-primary {
  background: rgba(224, 229, 233, 1);
  color: rgba(0, 0, 0, 0.75);
  font-weight: 700;
  border-radius: 5px;
  padding: 7px;
}
.btn-this-data {
  background-color: #1572c2;
  width: 197px;
  height: 40px;
}
.btn-light:hover {
  background-color: #EDF1F6;
  color: #888;
}
.pm-table-column-header-text {
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
<style lang="scss" scoped>
@import url("../../../sass/_scrollbar.scss");
</style>
