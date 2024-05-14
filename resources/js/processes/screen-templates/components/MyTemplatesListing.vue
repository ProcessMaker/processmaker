<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/\/screens\?/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="my-templates-table-card"
      data-cy="my-templates-table"
    >
      <filter-table
        filter-table-id="my-templates-table"
        :headers="fields"
        :data="data"
        :loading="shouldShowLoader"
        :selected-row="selectedRow"
        table-name="my-screen-templates"
        style="height: calc(100vh - 355px)"
        @table-row-mouseover="handleRowMouseover"
        @table-row-mouseleave="handleRowMouseleave"
      >
        <!-- Slot Table Header filter Button -->
        <template
          v-for="(column, index) in fields"
          #[`filter-${column.field}`]
        >
          <div
            v-if="column.sortable"
            :key="index"
            style="display: inline-block"
            @click="handleEllipsisClick(column)"
          >
            <i
              :class="[
                'fas',
                {
                  'fa-sort': column.direction === 'none',
                  'fa-sort-up': column.direction === 'asc',
                  'fa-sort-down': column.direction === 'desc',
                },
              ]"
            />
          </div>
        </template>
        <!-- Slot Table Body -->
        <template
          v-for="(row, rowIndex) in data.data"
          v-slot:[`row-${rowIndex}`]
        >
          <td
            v-for="(header, colIndex) in fields"
            :key="`${rowIndex}_${colIndex}`"
            :data-cy="`my-templates-table-td-${rowIndex}-${colIndex}`"
          >
            <div
              v-if="containsHTML(row[header.field])"
              :data-cy="`my-templates-table-html-${rowIndex}-${colIndex}`"
              v-html="sanitize(row[header.field])"
            />
            <template v-else>
              <template
                v-if="isComponent(row[header.field])"
                :data-cy="`my-templates-table-component-${rowIndex}-${colIndex}`"
              >
                <component
                  :is="row[header.field].component"
                  v-bind="row[header.field].props"
                />
              </template>
              <template
                v-else
                :data-cy="`my-templates-table-field-${rowIndex}-${colIndex}`"
              >
                <template v-if="header.field === 'name'">
                  <div
                    :id="`my-templates-${row.id}`"
                    :class="{ 'pm-table-truncate': header.truncate }"
                    :style="{ maxWidth: header.width + 'px' }"
                  >
                    <span>
                      {{ row[header.field] }}
                    </span>
                  </div>
                  <b-tooltip
                    v-if="header.truncate"
                    :target="`element-${row.id}`"
                    custom-class="pm-table-tooltip"
                  >
                    {{ row[header.field] }}
                  </b-tooltip>
                </template>
                <template v-if="header.field !== 'name'">
                  <div
                    :style="{ maxWidth: header.width + 'px' }"
                    :class="{ 'pm-table-truncate': header.truncate }"
                  >
                    {{ getNestedPropertyValue(row, header) }}
                  </div>
                </template>
              </template>
            </template>
          </td>
        </template>
      </filter-table>
      <screen-templates-tooltip
        v-show="isTooltipVisible"
        :position="rowPosition"
      >
        <template #screen-templates-tooltip-body>
          <div
            @mouseover="clearHideTimer"
            @mouseleave="hideTooltip"
          >
            <slot
              name="tooltip"
              :tooltipRowData="tooltipRowData"
              :previewTemplate="previewTemplate"
            >
              <span>
                <b-button
                  class="icon-button"
                  variant="light"
                  :aria-label="$t('Public Screen Template Preview')"
                  @click="previewTemplate(tooltipRowData)"
                >
                  <i class="fas fa-eye py-2" />
                </b-button>
              </span>
              <ellipsis-menu
                :actions="myTemplateActions"
                :data="tooltipRowData"
                :divider="false"
                :permission="permission"
                :screen-template="true"
                @navigate="onTemplateNavigate"
              />
            </slot>
          </div>
        </template>
      </screen-templates-tooltip>
      <pagination-table
        :meta="data.meta"
        data-cy="my-templates-pagination"
        @page-change="changePage"
        @per-page-change="changePerPage"
      />
      <pagination
        ref="pagination"
        :single="$t('My Template')"
        :plural="$t('My Templates')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
      />
    </div>
    <template-preview-container
      ref="preview"
      :selected-template="selectedTemplate"
      @mark-selected-row="markSelectedRow"
    />
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";
import ellipsisMenuMixin from "../../../components/shared/ellipsisMenuActions";
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";
import TemplatePreviewContainer from "./TemplatePreviewContainer.vue";
import ScreenTemplatesTooltip from "./ScreenTemplatesTooltip.vue";
import FilterTableBodyMixin from "../../../components/shared/FilterTableBodyMixin";
import paginationTable from "../../../components/shared/PaginationTable.vue";
import fieldsMixin from "../mixins/fieldsMixin";
import navigationMixin from "../mixins/navigationMixin";
import templateMixin from "../mixins/templateMixin.js";
import templatePreviewMixin from "../mixins/templatePreviewMixin";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: {
    EllipsisMenu,
    paginationTable,
    TemplatePreviewContainer,
    ScreenTemplatesTooltip,
  },
  mixins: [
    datatableMixin,
    dataLoadingMixin,
    uniqIdsMixin,
    ellipsisMenuMixin,
    FilterTableBodyMixin,
    fieldsMixin,
    navigationMixin,
    templateMixin,
    templatePreviewMixin,
  ],
  props: {
    permission: {
      type: [String, Object, Array],
      default: "",
    },
    filter: {
      type: String,
      default: "",
    },
    pmql: {
      type: String,
      default: "",
    },
    id: {
      type: String,
      default: "",
    },
  },
  data() {
    return {
      orderBy: "name",
      screenId: null,
      assetName: " ",
      assignedProjects: [],
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc",
        },
      ],
      fields: [],
      isTooltipVisible: false,
      rowPosition: {},
      tooltipRowData: {},
      hideTimer: null,
      selectedRow: 0,
      showTemplatePreview: false,
      selectedTemplate: null,
      tableId: "my-templates-table",
    };
  },
  created() {
    this.fields = this.commonFields;
    ProcessMaker.EventBus.$on("api-data-my-screen-templates", () => {
      this.fetch();
      this.apiDataLoading = false;
      this.apiNoResults = false;
      this.loading = false;
    });
  },
  methods: {
    fetch() {
      this.loading = true;
      this.apiDataLoading = true;
      // change method sort by slot name
      this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "templates/screen"
            + `?page=${this.page}&per_page=${this.perPage}&is_public=0`
            + `&filter=${this.filter}&pmql=${encodeURIComponent(this.pmql)}&order_by=${
              this.orderBy
            }&order_direction=${this.orderDirection}&include=categories,category,user`,
        )
        .then((response) => {
          this.data = this.transform(response.data);
          this.loading = false;
          this.apiDataLoading = false;
        })
        .catch((error) => {
          console.error(error);
        });
    },
  },
};
</script>

<style lang="scss" scoped>
:deep(th#_description) {
  width: 250px;
}

.my-templates-table-card {
  padding: 0;
  border: none;
}

.icon-button {
  color: #888;
  width: 32px;
  height: 32px;
  margin-top: 4px;
}
</style>
