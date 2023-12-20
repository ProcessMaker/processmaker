<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/tasks\?page|results\?page/"
      :empty="$t('Congratulations')"
      :empty-desc="$t('You don\'t currently have any tasks assigned to you')"
      empty-icon="beach"
    />
    <div
      v-show="!shouldShowLoader"
      data-cy="tasks-table"
    >
      <filter-table
        :headers="tableHeaders"
        :data="data"
        @table-row-click="handleRowClick"
      >
        <!-- Slot Table Header -->
        <template v-for="(column, index) in tableHeaders" v-slot:[column.field]>
          <div :key="index">{{ column.label }}</div>
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
                                   :sort="order_direction"
                                   :container="''"
                                   @onChangeSort="onChangeSort"
                                   @onApply="onApply($event, index)"
                                   @onClear="onClear(index)"
                                   @onUpdate="onUpdate($event, index)">
            </PMColumnFilterPopover>
        </template>
        <!-- Slot Table Body -->
        <template v-for="(row, rowIndex) in data.data" v-slot:[`row-${rowIndex}`]>
          <td
            v-for="(header, colIndex) in tableHeaders"
            :key="colIndex"
          >
            <template v-if="containsHTML(row[header.field])">
              <div
                :id="`element-${rowIndex}-${colIndex}`"
                :class="{ 'pm-table-truncate': header.truncate }"
                :style="{ maxWidth: header.width + 'px' }"
                  >
                <div v-html="sanitize(row[header.field])"></div>
              </div>
              <b-tooltip
                v-if="header.truncate"
                :target="`element-${rowIndex}-${colIndex}`"
                custom-class="pm-table-tooltip"
              >
                {{ sanitizeTooltip(row[header.field]) }}
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
                  {{ row[header.field] }}
                  <b-tooltip
                    v-if="header.truncate"
                    :target="`element-${rowIndex}-${colIndex}`"
                    custom-class="pm-table-tooltip"
                  >
                    {{ row[header.field] }}
                  </b-tooltip>
                </div>
              </template>
            </template>
          </td>
        </template>
      </filter-table>
      <pagination-table
        :meta="data.meta"
        @page-change="changePage"
      />
    </div>
    <tasks-preview ref="preview" />
  </div>
</template>

<script>
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
import paginationTable from "../../components/shared/PaginationTable.vue";

const uniqIdsMixin = createUniqIdsMixin();

Vue.component("AvatarImage", AvatarImage);
Vue.component("TasksPreview", TasksPreview);

export default {
  components: {
    EllipsisMenu,
    PMColumnFilterPopover,
    paginationTable,
  },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ListMixin],
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
      advancedFilter: [],
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
          record["status"] = this.formatStatus(record);
          record["assignee"] = this.formatAsignee(record["user"]);
          record["request"] = this.formatRequest(record);
        }
      }
    },
  },
  mounted: function mounted() {
    this.setupColumns();
    const params = new URL(document.location).searchParams;
    const successRouting = params.get("successfulRouting") === "true";
    if (successRouting) {
      ProcessMaker.alert(this.$t("The request was completed."), "success");
    }
  },
  methods: {
    setupColumns() {
      const columns = this.getColumns();
      this.tableHeaders = this.getColumns();
    },
    getColumns() {
      if (this.$props.columns) {
        return this.$props.columns;
      }
      const columns = [
        {
          label: "#",
          field: "id",
          sortable: true,
          default: true,
          width: 45,
        },
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
        {
          label: "ASSIGNEE",
          field: "assignee",
          sortable: true,
          default: true,
          width: 140,
        },
        {
          label: "COMPLETED",
          field: "completed_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 140,
        },
      ];
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
    formatRequest(request) {
      return `
          <a 
            href="${this.onAction('showRequestSummary', request, 1)}" 
            class="text-nowrap">#${request.process_request.id}
              ${request.process.name}
          </a>`;
    },
    openTask(task) {
      return `/tasks/${task.id}/edit`;
    },
    handleRowClick(row) {
      window.location.href = this.openTask(row, 1);
    },
    containsHTML(text) {
      const doc = new DOMParser().parseFromString(text, 'text/html');
      return Array.from(doc.body.childNodes).some(node => node.nodeType === Node.ELEMENT_NODE);
    },
    isComponent(content) {
      if (content && typeof content === 'object') {
        return content.component && typeof content.props === 'object';
      }
      return false;
    },
    sanitize(html) {
      let cleanHtml = html.replace(/<script(.*?)>[\s\S]*?<\/script>/gi, "");
      cleanHtml = cleanHtml.replace(/<style(.*?)>[\s\S]*?<\/style>/gi, "");
      cleanHtml = cleanHtml.replace(/<(?!br|img|input|hr|a|link|meta|time|button|select|textarea|datalist|progress|meter|span)[^>]*>/gi, "");
      cleanHtml = cleanHtml.replace(/\s+/g, " ");

      return cleanHtml;
    },
    sanitizeTooltip(html) {
      let cleanHtml = html.replace(/<script(.*?)>[\s\S]*?<\/script>/gi, "");
      cleanHtml = cleanHtml.replace(/<style(.*?)>[\s\S]*?<\/style>/gi, "");
      cleanHtml = cleanHtml.replace(/<(?!img|input|meta|time|button|select|textarea|datalist|progress|meter)[^>]*>/gi, "");
      cleanHtml = cleanHtml.replace(/\s+/g, " ");

      return cleanHtml;
    },
    changePage(page) {
      this.page = page;
      this.fetch();
    },
    onChangeSort(value) {
      this.order_direction = value;
    },
    onApply(json, index) {
      this.advancedFilter[index] = json;
      console.log(this.advancedFilter, index);
      this.fetch();
    },
    onClear(index) {
      this.advancedFilter[index] = [];
      this.fetch();
    },
    onUpdate(object, index) {
      if (object.$refs.pmColumnFilterForm && 
        this.advancedFilter.length > 0 &&
        this.advancedFilter[index] && 
        this.advancedFilter[index].length > 0) {
        object.$refs.pmColumnFilterForm.setValues(this.advancedFilter[index]);
      }
    },
    getAdvancedFilter() {
      let flat = this.advancedFilter.flat(1);
      return flat.length > 0 ? "&advanced_filter=" + JSON.stringify(flat) : "";
    },
    getFormat(column) {
      let format = "string";
      if (column.format) {
        format = column.format;
      }
      if (column.field === "status" || column.field === "assignee") {
        format = "stringSelect";
      }
      return format;
    },
    getFormatRange(column) {
      let formatRange = [];
      if (column.field === "status") {
        formatRange = ["In Progress", "Completed", "Error", "Canceled"];
      }
      if (column.field === "assignee") {
        formatRange = ["user1", "user2", "user3", "user4"];
      }
      return formatRange;
    },
    getOperators(column) {
      let operators = [];
      if (column.field === "status" || column.field === "assignee") {
        operators = ["=", "in"];
      }
      return operators;
    },
    getViewConfigFilter() {
      return [
        {
          "type": "string",
          "includes": ["=", "<", "<=", ">", ">=", "contains", "regex"],
          "control": "PMColumnFilterOpInput",
          "input": ""
        },
        {
          "type": "string",
          "includes": ["between"],
          "control": "PMColumnFilterOpBetween",
          "input": []
        },
        {
          "type": "string",
          "includes": ["in"],
          "control": "PMColumnFilterOpIn",
          "input": []
        },
        {
          "type": "datetime",
          "includes": ["=", "<", "<=", ">", ">=", "contains", "regex"],
          "control": "PMColumnFilterOpDatetime",
          "input": ""
        },
        {
          "type": "datetime",
          "includes": ["between"],
          "control": "PMColumnFilterOpBetweenDatepicker",
          "input": []
        },
        {
          "type": "datetime",
          "includes": ["in"],
          "control": "PMColumnFilterOpInDatepicker",
          "input": []
        },
        {
          "type": "stringSelect",
          "includes": ["="],
          "control": "PMColumnFilterOpSelect",
          "input": ""
        },
        {
          "type": "stringSelect",
          "includes": ["in"],
          "control": "PMColumnFilterOpSelectMultiple",
          "input": []
        },
        {
          "type": "boolean",
          "includes": ["="],
          "control": "PMColumnFilterOpBoolean",
          "input": false
        }
      ];
    }
  },
};
</script>

<style>
.tasks-table-card {
  padding: 0;
}
</style>
