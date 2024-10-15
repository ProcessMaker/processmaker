<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/\/processes\?page/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="processes-table-card"
      data-cy="processes-table"
    >
      <filter-table
        :headers="fields"
        :data="data"
        table-name="processes"
        style="height: calc(100vh - 355px);"
      >
        <!-- Slot Table Header filter Button -->
        <template
          v-for="(column, index) in fields"
          #[`filter-${column.field}`]
        >
          <div
            v-if="column.sortable"
            :key="index"
            @click="handleEllipsisClick(column)"
          >
            <i
              :class="['fas', {
                'fa-sort': column.direction === 'none',
                'fa-sort-up': column.direction === 'asc',
                'fa-sort-down': column.direction === 'desc',
              }]"
            />
          </div>
        </template>
        <!-- Slot Table Body -->
        <template
          v-for="(row, rowIndex) in data.data"
          #[`row-${rowIndex}`]
        >
          <td
            v-for="(header, colIndex) in fields"
            :key="`${rowIndex}_${colIndex}`"
            :data-cy="`process-table-td-${rowIndex}-${colIndex}`"
          >
            <div
              v-if="containsHTML(row[header.field])"
              :data-cy="`process-table-html-${rowIndex}-${colIndex}`"
              v-html="sanitize(row[header.field])"
            />
            <template v-else>
              <template
                v-if="isComponent(row[header.field])"
                :data-cy="`process-table-component-${rowIndex}-${colIndex}`"
              >
                <component
                  :is="row[header.field].component"
                  v-bind="row[header.field].props"
                />
              </template>
              <template
                v-else
                :data-cy="`process-table-field-${rowIndex}-${colIndex}`"
              >
                <template v-if="header.field === 'name'">
                  <div
                    :id="`element-${row.id}`"
                    :class="{ 'pm-table-truncate': header.truncate }"
                    :style="{ maxWidth: header.width + 'px' }"
                  >
                    <i
                      v-b-tooltip
                      tabindex="0"
                      :title="row.warningMessages.join(' ')"
                      class="text-warning fa fa-exclamation-triangle"
                      :class="{'invisible': row.warningMessages.length == 0}"
                    />
                    <i
                      v-if="row.status == 'ACTIVE' || row.status == 'INACTIVE'"
                      v-b-tooltip
                      tabindex="0"
                      :title="row.status"
                      class="mr-2"
                      :class="{ 'fas fa-check-circle text-success': row.status == 'ACTIVE', 'far fa-circle': row.status == 'INACTIVE' }"
                    />
                    <a :href="openModeler(row)" class="text-nowrap">
                      {{ row[header.field] }}
                    </a>
                  </div>
                  <b-tooltip
                    v-if="header.truncate"
                    :target="`element-${row.id}`"
                    custom-class="pm-table-tooltip"
                    @show="checkIfTooltipIsNeeded"
                  >
                    {{ row[header.field] }}
                  </b-tooltip>
                </template>
                <ellipsis-menu
                  v-if="header.field === 'actions'"
                  class="process-table"
                  :actions="processActions"
                  :permission="permission"
                  :data="row"
                  :is-documenter-installed="isDocumenterInstalled"
                  :divider="false"
                  @navigate="onProcessNavigate"
                />
                <template v-if="header.field !== 'name'">
                  <div
                    :style="{ maxWidth: header.width + 'px' }"
                  >
                    {{ getNestedPropertyValue(row, header) }}
                  </div>
                </template>
              </template>
            </template>
          </td>
        </template>
      </filter-table>
      <create-template-modal
        id="create-template-modal"
        ref="create-template-modal"
        asset-type="process"
        :current-user-id="currentUserId"
        :asset-name="processTemplateName"
        :asset-id="processId"
      />
      <create-pm-block-modal
        id="create-pm-block-modal"
        ref="create-pm-block-modal"
        :current-user-id="currentUserId"
        :asset-name="pmBlockName"
        :asset-id="processId"
      />
      <add-to-project-modal
        id="add-to-project-modal"
        ref="add-to-project-modal"
        asset-type="process"
        :asset-id="processId"
        :asset-name="assetName"
      />
      <pagination-table
        :meta="data.meta"
        data-cy="process-pagination"
        @page-change="changePage"
        @per-page-change="changePerPage"
      />
      <pagination
        ref="pagination"
        :single="$t('Process')"
        :plural="$t('Processes')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
      />
    </div>
    <add-to-bundle asset-type="ProcessMaker\Models\Process" />
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import TemplateExistsModal from "../../components/templates/TemplateExistsModal.vue";
import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";
import CreatePmBlockModal from "../../components/pm-blocks/CreatePmBlockModal.vue";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
import AddToProjectModal from "../../components/shared/AddToProjectModal.vue";
import processNavigationMixin from "../../components/shared/processNavigation";
import paginationTable from "../../components/shared/PaginationTable.vue";
import FilterTableBodyMixin from "../../components/shared/FilterTableBodyMixin";
import AddToBundle from "../../components/shared/AddToBundle.vue";
import ProcessMixin from "./ProcessMixin";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: {
    TemplateExistsModal,
    CreateTemplateModal,
    EllipsisMenu,
    CreatePmBlockModal,
    AddToProjectModal,
    paginationTable,
    AddToBundle,
  },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ellipsisMenuMixin, processNavigationMixin, FilterTableBodyMixin, ProcessMixin],
  props: ["filter", "id", "status", "permission", "isDocumenterInstalled", "pmql", "processName", "currentUserId"],
  data() {
    return {
      orderBy: "updated_at",
      orderDirection: "desc",
      processId: null,
      processTemplateName: "",
      pmBlockName: "",
      assetName: "",
      processData: {},
      previousFilter: "",
      previousPmql: "",
      sortOrder: [
        {
          field: "updated_at",
          sortField: "updated_at",
          direction: "desc",
        },
      ],

      fields: [
        {
          label: this.$t("Name"),
          field: "name",
          width: 200,
          sortable: true,
          truncate: true,
          direction: "none",
        },
        {
          label: this.$t("Category"),
          field: "category_list",
          width: 160,
          sortable: true,
          direction: "none",
          sortField: "category.name",
        },
        {
          label: this.$t("Owner"),
          field: "owner",
          width: 160,
          sortable: true,
          direction: "none",
          sortField: "user.username",
        },
        {
          label: this.$t("Modified"),
          field: "updated_at",
          format: "datetime",
          width: 160,
          sortable: true,
          direction: "desc",
        },
        {
          label: this.$t("Created"),
          field: "created_at",
          format: "datetime",
          width: 160,
          sortable: true,
          direction: "none",
        },
        {
          name: "__slot:actions",
          field: "actions",
          width: 60,

        },
      ],
    };
  },
  created() {
    ProcessMaker.EventBus.$on("api-data-process", (val) => {
      this.fetch();
      this.apiDataLoading = false;
      this.apiNoResults = false;
    });
  },
};
</script>

<style lang="scss" scoped>
  :deep(th#_updated_at) {
    width: 14%;
  }

  :deep(th#_created_at) {
    width: 14%;
  }

  .processes-table-card {
    padding: 0;
  }

</style>