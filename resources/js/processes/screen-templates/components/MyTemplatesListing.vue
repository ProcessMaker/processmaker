<template>
  <div class="data-table">
    <data-loading :for="/screens/" v-show="shouldShowLoader" :empty="$t('No Data Available')" :empty-desc="$t('')"
      empty-icon="noData" />
    <div v-show="!shouldShowLoader" class="card card-body my-templates-table-card" data-cy="my-templates-table">
      <filter-table :headers="fields" :data="data" table-name="my-screen-templates" style="height: calc(100vh - 355px);">
        <!-- Slot Table Header filter Button -->
        <template v-for="(column, index) in fields" #[`filter-${column.field}`]>
          <div v-if="column.sortable" :key="index" @click="handleEllipsisClick(column)">
            <i :class="['fas', {
              'fa-sort': column.direction === 'none',
              'fa-sort-up': column.direction === 'asc',
              'fa-sort-down': column.direction === 'desc',
            }]" />
          </div>
        </template>
        <!-- Slot Table Body -->
        <template v-for="(row, rowIndex) in data.data" #[`row-${rowIndex}`]>
          <td v-for="(header, colIndex) in fields" :key="`${rowIndex}_${colIndex}`"
            :data-cy="`my-templates-table-td-${rowIndex}-${colIndex}`">
            <div v-if="containsHTML(row[header.field])" :data-cy="`my-templates-table-html-${rowIndex}-${colIndex}`"
              v-html="sanitize(row[header.field])" />
            <template v-else>
              <template v-if="isComponent(row[header.field])"
                :data-cy="`my-templates-table-component-${rowIndex}-${colIndex}`">
                <component :is="row[header.field].component" v-bind="row[header.field].props" />
              </template>
              <template v-else :data-cy="`my-templates-table-field-${rowIndex}-${colIndex}`">
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
                  <b-tooltip v-if="header.truncate" :target="`element-${row.id}`" custom-class="pm-table-tooltip">
                    {{ row[header.field] }}
                  </b-tooltip>
                </template>
                <template v-if="header.field === 'actions'">
                  <ellipsis-menu class="my-template-table" :actions="myTemplateActions" :permission="permission"
                    :data="row" :divider="true" :screen-template="true" @navigate="onMyTemplateNavigate" />
                </template>
                <template v-if="header.field !== 'name'">
                  <div :style="{ maxWidth: header.width + 'px' }">
                    {{ getNestedPropertyValue(row, header) }}
                  </div>
                </template>
              </template>
            </template>
          </td>
        </template>
      </filter-table>

      <pagination-table :meta="data.meta" data-cy="my-templates-pagination" @page-change="changePage" />
      <pagination ref="pagination" :single="$t('My Template')" :plural="$t('My Templates')"
        :per-page-select-enabled="true" @changePerPage="changePerPage" @vuetable-pagination:change-page="onPageChange" />
    </div>
  </div>
</template>
  
<script>
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";
import ellipsisMenuMixin from "../../../components/shared/ellipsisMenuActions";
import screenNavigationMixin from "../../../components/shared/screenNavigation";
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";
import FilterTableBodyMixin from "../../../components/shared/FilterTableBodyMixin";
import paginationTable from "../../../components/shared/PaginationTable.vue";

import { createUniqIdsMixin } from "vue-uniq-ids";
const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { EllipsisMenu, paginationTable },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ellipsisMenuMixin, screenNavigationMixin, FilterTableBodyMixin],
  props: ["filter", "id", "pmql", "permission"],
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
          direction: "asc"
        }
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
          label: this.$t("Description"),
          field: "description",
          width: 200,
          sortable: true,
          direction: "none",
          sortField: "description",
        },
        {
          label: this.$t("Type of Screen"),
          field: "screen_type",
          width: 160,
          sortable: true,
          direction: "none",
          sortField: "screen_type",
        },
        {
          label: this.$t("Modified"),
          field: "updated_at",
          format: "datetime",
          width: 160,
          sortable: true,
          direction: "none",
        },
        {
          name: "__slot:actions",
          field: "actions",
          width: 60,
        }
      ]
    };
  },
  created() {
    ProcessMaker.EventBus.$on("api-data-my-screen-templates", (val) => {
      this.fetch();
      this.apiDataLoading = false;
      this.apiNoResults = false;
    });
  },
  methods: {
    onMyTemplateNavigate(actionType, data) {
      switch (actionType?.value) {
        case "placeholder-action":
          break;
        case "placeholder-action-2":
          break;
        case "delete-template":
          ProcessMaker.confirmModal(
            this.$t("Caution!"),
            this.$t(
              "Are you sure you want to delete the screen {{item}}? Deleting this asset will break any active tasks that are assigned.",
              {
                item: data.title,
              },
            ),
            "",
            () => {
              ProcessMaker.apiClient.delete(`template/screen/${data.id}`).then(() => {
                ProcessMaker.alert(this.$t("The template was deleted."), "success");
                this.fetch();
              });
            },
          );
          break;
        default:
          break;
      }
    },
    fetch() {
      this.loading = true;
      //change method sort by slot name
      this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "templates/screen" +
          "?page=" +
          this.page +
          "&per_page=" +
          this.perPage +
          "&is_public=0" +
          "&filter=" +
          this.filter +
          "&pmql=" + 
          encodeURIComponent(this.pmql) +
          "&order_by=" +
          this.orderBy +
          "&order_direction=" +
          this.orderDirection +
          "&include=categories,category,user"
        )
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
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
}
</style>
