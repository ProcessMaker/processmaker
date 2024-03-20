<!-- eslint-disable vue/no-v-html -->
<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/screens/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="card card-body public-templates-table-card"
      data-cy="public-templates-table"
    >
      <filter-table
        :headers="fields"
        :data="data"
        table-name="public-screen-templates"
        style="height: calc(100vh - 355px)"
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
          #[`row-${rowIndex}`]
        >
          <td
            v-for="(header, colIndex) in fields"
            :key="`${rowIndex}_${colIndex}`"
            :data-cy="`public-templates-table-td-${rowIndex}-${colIndex}`"
          >
            <div
              v-if="containsHTML(row[header.field])"
              :data-cy="`public-templates-table-html-${rowIndex}-${colIndex}`"
              v-html="sanitize(row[header.field])"
            />
            <template v-else>
              <template
                v-if="isComponent(row[header.field])"
                :data-cy="`public-templates-table-component-${rowIndex}-${colIndex}`"
              >
                <component
                  :is="row[header.field].component"
                  v-bind="row[header.field].props"
                />
              </template>
              <template
                v-else
                :data-cy="`public-templates-table-field-${rowIndex}-${colIndex}`"
              >
                <template v-if="header.field === 'name'">
                  <div
                    :id="`public-templates-${row.id}`"
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
                <template v-if="header.field === 'actions'">
                  <ellipsis-menu
                    class="public-template-table"
                    :actions="publicTemplateActions"
                    :permission="permission"
                    :data="row"
                    :divider="true"
                    :screen-template="true"
                    @navigate="onTemplateNavigate"
                  />
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
      <pagination-table
        :meta="data.meta"
        data-cy="public-templates-pagination"
        @page-change="changePage"
        @per-page-change="changePerPage"
      />
      <pagination
        ref="pagination"
        :single="$t('Public Template')"
        :plural="$t('Public Templates')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
      />
    </div>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";
import ellipsisMenuMixin from "../../../components/shared/ellipsisMenuActions";
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";
import FilterTableBodyMixin from "../../../components/shared/FilterTableBodyMixin";
import paginationTable from "../../../components/shared/PaginationTable.vue";
import fieldsMixin from "../mixins/fieldsMixin";
import navigationMixin from "../mixins/navigationMixin";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { EllipsisMenu, paginationTable },
  mixins: [
    datatableMixin,
    dataLoadingMixin,
    ellipsisMenuMixin,
    FilterTableBodyMixin,
    uniqIdsMixin,
    fieldsMixin,
    navigationMixin,
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
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc",
        },
      ],
      fields: [],
    };
  },
  created() {
    this.insertFieldAfter("screen_type", {
      label: this.$t("Owner"),
      field: "owner",
      width: 160,
      sortable: true,
      direction: "none",
      sortField: "user.username",
    });
    this.fields = this.commonFields;
    ProcessMaker.EventBus.$on("api-data-public-screen-templates", () => {
      this.fetch();
      this.apiDataLoading = false;
      this.apiNoResults = false;
    });
  },
  methods: {
    fetch() {
      this.loading = true;
      // change method sort by slot name
      this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "templates/screen"
            + `?page=${this.page}&per_page=${this.perPage}&is_public=1`
            + `&filter=${this.filter}&pmql=${encodeURIComponent(this.pmql)}&order_by=${
              this.orderBy
            }&order_direction=${this.orderDirection}&include=categories,category,user`,
        )
        .then((response) => {
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

.public-templates-table-card {
  padding: 0;
}
</style>
