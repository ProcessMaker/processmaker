<template>
  <div>
    <div
      v-show="true"
    >
      <filter-table
        :headers="tableHeaders"
        :data="data"
        :unread="unreadColumnName"
        :loading="shouldShowLoader"
        @table-row-click="handleRowClick"
        @table-column-mouseover="handleColumnMouseover"
        @table-column-mouseleave="handleColumnMouseleave"
      >
        <!-- Slot Table Header -->
        <template v-for="(column, index) in tableHeaders" v-slot:[column.field]>
          <div
            :key="`requests-table-column-${index}`"
            :id="`requests-table-column-${column.field}`"
            class="pm-table-column-header-text"
          >
            {{ $t(column.label) }}
          </div>
          <b-tooltip
            :key="index"
            :target="`requests-table-column-${column.field}`"
            custom-class="pm-table-tooltip-header"
            placement="bottom"
            :delay="0"
            @show="checkIfTooltipIsNeeded"
          >
            {{ $t(column.label) }}
          </b-tooltip>
        </template>
        <!-- Slot Table Header filter Button -->
        <template v-for="(column, index) in tableHeaders" v-slot:[`filter-${column.field}`]>
            <PMColumnFilterPopover v-if="column.sortable"
                                   :key="index"
                                   :id="'pm-table-column-'+index"
                                   :type="getTypeColumnFilter(column.field)"
                                   :value="column.field"
                                   :format="getFormat(column)"
                                   :formatRange="getFormatRange(column)"
                                   :operators="getOperators(column)"
                                   :viewConfig="getViewConfigFilter()"
                                   :container="''"
                                   :boundary="'viewport'"
                                   :hideSortingButtons="column.hideSortingButtons"
                                   :columnSortAsc="column.sortAsc"
                                   :columnSortDesc="column.sortDesc"
                                   :filterApplied="column.filterApplied"
                                   :columnMouseover="columnMouseover"
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
            :class="{ 'pm-table-filter-applied-tbody': header.sortAsc || header.sortDesc }"
            :key="colIndex"
          >
            <template v-if="containsHTML(getNestedPropertyValue(row, header))">
              <div
                :id="`element-${rowIndex}-${colIndex}`"
                :class="{ 'pm-table-truncate': header.truncate }"
                :style="{ maxWidth: header.width + 'px' }"
              >
                <span v-html="sanitize(getNestedPropertyValue(row, header))"></span>
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
          </td>
        </template>
      </filter-table>
    </div>
    <data-loading
      v-show="shouldShowLoader && noResultsMessage === 'cases'"
      :for="/requests\?page|results\?page/"
      :empty="$t('No results have been found')"
      :empty-desc="$t(`We apologize, but we were unable to find any results that match your search.
Please consider trying a different search. Thank you`)"
      empty-icon="noData"
    />
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
import DefaultTab from "../../processes-catalogue/components/DefaultTab.vue";

const uniqIdsMixin = createUniqIdsMixin();

Vue.component("AvatarImage", AvatarImage);

export default {
  components: {
    PMColumnFilterPopover,
    paginationTable,
    PMColumnFilterIconAsc,
    PMColumnFilterIconDesc,
    DefaultTab
  },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ListMixin, PMColumnFilterPopoverCommonMixin, FilterTableBodyMixin],
  props: {
    filter: {},
    columns: {},
    pmql: {},
    savedSearch: {
      default: false,
    },
    noResultsMessage: {
      type: String,
      default: "cases",
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
      previousAdvancedFilter: "",
      tableHeaders: [],
      unreadColumnName: "user_viewed_at",
      columnMouseover: null,
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
    this.getFilterConfiguration();
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
          label: "Case #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 95,
        },
        {
          label: "Case title",
          field: "case_title",
          sortable: true,
          default: true,
          truncate: true,
          width: 375,
        },
        {
          label: "Process",
          field: "name",
          sortable: true,
          default: true,
          width: 145,
          truncate: true,
        },
        {
          label: "Task",
          field: "active_tasks",
          sortable: false,
          default: true,
          width: 175,
          truncate: true,
          tooltip: this.$t("This column can not be sorted or filtered."),
        },
        {
          label: "Participants",
          field: "participants",
          sortable: true,
          default: true,
          width: 175,
          truncate: true,
          filter_subject: { type: 'ParticipantsFullName' },
          hideSortingButtons: true,
        },
        {
          label: "Status",
          field: "status",
          sortable: true,
          default: true,
          width: 115,
          filter_subject: { type: 'Status' },
        },
        {
          label: "Started",
          field: "initiated_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 160,
        },
        {
          label: "Completed",
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
      return value.map((task) => {
        return `
          <a href="${this.openTask(task)}">
            ${task.element_name}
          </a>
        `;
      }).join('<br/>');
      return htmlString;
    },
    formatId(value) {
      return `
      <a href="${this.openRequest(value, 1)}"
         class="text-nowrap">
         # ${value.id}
      </a>`;
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
          "name-clickable": true,
        },
      };
    },
    formatProcessVersionAlternative(value) {
      let color = "primary";
      let badge = "alternative-a";

      if (value === "B") {
        color = "secondary";
        badge = "alternative-b";
      } else if (value === null) {
        return "-";
      }

      return `
        <span 
          class="badge badge-${color} status-${badge}"
        >
          ${this.$t('Alternative')} ${value}
        </span>`;
    },
    transform(dataInput) {
      const data = _.cloneDeep(dataInput);
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
        record["process_version_alternative"] = this.formatProcessVersionAlternative(record["process_version_alternative"]);
        record["id"] = this.formatId(record);
      }
      return data;
    },
    fetch(navigateToFirstPage = false) {
      Vue.nextTick(() => {
        if (this.cancelToken) {
          this.cancelToken();
          this.cancelToken = null;
        }

        const CancelToken = ProcessMaker.apiClient.CancelToken;

        const { pmql, filter, advancedFilter } = this.buildPmqlAndFilter(navigateToFirstPage);

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
            advancedFilter +
            "&row_format=",
            {
              cancelToken: new CancelToken((c) => {
                this.cancelToken = c;
              }),
              headers: {
                'Cache-Control': 'no-cache',
              }
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
    buildPmqlAndFilter(navigateToFirstPage) {
      let pmql = '';

      if (this.pmql !== undefined) {
        pmql = this.pmql;
      }

      let filter = this.filter;

      if (filter?.length) {
        if (filter.isPMQL()) {
          pmql = (pmql ? `${pmql} and ` : '') + `(${filter})`;
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

      const advancedFilter = this.getAdvancedFilter();

      if (this.previousAdvancedFilter !== advancedFilter && navigateToFirstPage) {
        this.page = 1;
      }

      return { pmql, filter, advancedFilter };

    },
    handleRowClick(row) {
    },
    /**
     * This method is used in PMColumnFilterPopoverCommonMixin.js
     * @returns {Array}
     */
    getStatus() {
      return [
        {value: "In Progress", text: this.$t("In Progress")},
        {value: "Completed", text: this.$t("Completed")},
        {value: "Error", text: this.$t("Error")},
        {value: "Canceled", text: this.$t("Canceled")}
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
    filterConfiguration() {
      return {
        order: {
          by: this.orderBy,
          direction: this.orderDirection
        },
        type: 'requestFilter',
      }
    },
    handleColumnMouseover(column) {
      this.columnMouseover = column;
    },
    handleColumnMouseleave() {
      this.columnMouseover = null;
    },
  }
};
</script>
<style>
  .pm-table-ellipsis-column{
    text-transform: uppercase;
  }
  .pm-table-column-header-text {
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
<style lang="scss" scoped>
  @import url("../../../sass/_scrollbar.scss");
</style>
