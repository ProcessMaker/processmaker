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
      >
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
              <template v-if="isComponent(row[header.field])">
                <component
                  :is="row[header.field].component"
                  :data-cy="`process-table-component-${rowIndex}-${colIndex}`"
                  v-bind="row[header.field].props"
                />
              </template>
              <template v-else>
                <div
                  v-if="header.field === 'name'"
                  :data-cy="`process-table-field-${rowIndex}-${colIndex}`"
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
                  <span
                    v-uni-id="row.id.toString()"
                  >
                    {{ row[header.field] }}
                  </span>
                </div>
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
                    {{ row[header.field] }}
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
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import TemplateExistsModal from "../../components/templates/TemplateExistsModal.vue";
import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";
import CreatePmBlockModal from "../../components/pm-blocks/CreatePmBlockModal.vue";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import AddToProjectModal from "../../components/shared/AddToProjectModal.vue";
import paginationTable from "../../components/shared/PaginationTable.vue";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
import processNavigationMixin from "../../components/shared/processNavigation";
import FilterTableBodyMixin from "../../components/shared/FilterTableBodyMixin";
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
  },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ellipsisMenuMixin, processNavigationMixin, FilterTableBodyMixin, ProcessMixin],
  props: ["filter", "id", "status", "permission", "isDocumenterInstalled", "pmql", "processName", "currentUserId"],
  data() {
    return {
      orderBy: "name",
      processId: null,
      processTemplateName: "",
      pmBlockName: "",
      assetName: "",
      processData: {},
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc",
        },
      ],

      fields: [
        {
          label: "NAME",
          field: "name",
          width: 200,
          sortable: true,
        },
        {
          label: "CATEGORY",
          field: "category_list",
          width: 160,
          sortable: true,
        },
        {
          label: "OWNER",
          field: "owner",
          width: 160,
          sortable: true,
        },
        {
          label: "MODIFIED",
          field: "updated_at",
          format: "datetime",
          width: 160,
          sortable: true,
        },
        {
          label: "CREATED",
          field: "created_at",
          format: "datetime",
          width: 160,
          sortable: true,
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
    ProcessMaker.EventBus.$on("api-data-archived-process", (val) => {
      this.fetch();
    });
  },
  methods: {},
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
