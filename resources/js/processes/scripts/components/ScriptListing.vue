<template>
  <div class="data-table">
    <data-loading
            :for="/scripts\?page/"
            v-show="shouldShowLoader"
            :empty="$t('No Data Available')"
            :empty-desc="$t('')"
            empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="scripts-table-card" data-cy="scripts-table">
      <filter-table
        :headers="fields"
        :data="data"
        table-name="scripts"
        style="height: calc(100vh - 355px);"
      >
        <!-- Slot Table Header filter Button -->
        <template v-for="(column, index) in fields" v-slot:[`filter-${column.field}`]>
          <div
            v-if="column.sortable"
            :key="index"
            @click="onClickEllipsis(column)"
          >
            <i
              :class="['fas', {
                'fa-sort': column.direction === 'none',
                'fa-sort-up': column.direction === 'asc',
                'fa-sort-down': column.direction === 'desc',
              }]"
            ></i>
          </div>
        </template>
        <template v-for="(row, rowIndex) in data.data" v-slot:[`row-${rowIndex}`]>
          <td
            v-for="(header, colIndex) in fields"
            :key="colIndex"
            :data-cy="`scripts-table-td-${rowIndex}-${colIndex}`"
          >
            <div
              :data-cy="`datasource-table-html-${rowIndex}-${colIndex}`"
              v-if="containsHTML(row[header.field])"
              v-html="sanitize(row[header.field])"
            >
            </div>
            <template v-else>
              <template
                v-if="isComponent(row[header.field])"
                :data-cy="`scripts-table-component-${rowIndex}-${colIndex}`"
              >
                <component
                  :is="row[header.field].component"
                  v-bind="row[header.field].props"
                >
                </component>
              </template>
              <template
                v-else
                :data-cy="`scripts-table-field-${rowIndex}-${colIndex}`"
              >
                <template v-if="header.field === 'title'">
                  <b-link
                    v-if="permission.includes('edit-scripts')"
                    :href="`/designer/scripts/${row.id}/builder`"
                    v-uni-id="row.id.toString()"
                  >{{ row.title }}</b-link>
                  <span v-uni-id="row.id.toString()" v-else="permission.includes('edit-scripts')">{{ row.title }}</span>
                </template>
                <template v-if="header.field === 'actions'">
                  <ellipsis-menu
                    :actions="scriptActions"
                    :permission="permission"
                    :data="row"
                    :divider="true"
                    @navigate="onScriptNavigate"
                  />
                </template>
                <template v-if="header.field !== 'title' && header.field !== 'actions'">
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

      <add-to-project-modal id="add-to-project-modal" ref="add-to-project-modal"  assetType="script" :assetId="assetId" :assetName="assetName"/>

      <pagination-table
        :meta="data.meta"
        @page-change="changePage"
        @per-page-change="changePerPage"
      />
    </div>
    <b-modal ref="myModalRef" :title="$t('Copy Script')" centered  header-close-content="&times;" >
      <form>
        <div class="form-group">
          <label for="title">{{ $t('Name') }}<small class="ml-1">*</small></label>
          <input id="title"
            type="text"
            class="form-control"
            v-model="dupScript.title"
            v-bind:class="{ 'is-invalid': errors.title }"
          />
          <div class="invalid-feedback" role="alert" v-if="errors.title">{{errors.title[0]}}</div>
        </div>
        <div class="form-group">
          <category-select
          :label="$t('Category')"
          api-get="script_categories"
          api-list="script_categories"
          v-model="dupScript.script_category_id"
          :errors="errors.script_category_id">
          </category-select>
        </div>
        <div class="form-group">
          <label for="description">{{ $t('Description') }}</label>
          <textarea class="form-control" id="description" rows="3" v-model="dupScript.description"></textarea>
        </div>
      </form>
      <div slot="modal-footer" class="w-100" align="right">
        <button type="button" class="btn btn-outline-secondary" @click="hideModal">{{$t('Cancel')}}</button>
        <button type="button" @click="onSubmit" class="btn btn-secondary ml-2">{{$t('Save')}}</button>
      </div>
    </b-modal>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";
import ellipsisMenuMixin from "../../../components/shared/ellipsisMenuActions";
import scriptNavigationMixin from "../../../components/shared/scriptNavigation";
import AddToProjectModal from "../../../components/shared/AddToProjectModal.vue";
import { FilterTableBodyMixin, ellipsisSortClick } from "../../../components/shared";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { EllipsisMenu, AddToProjectModal },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ellipsisMenuMixin, scriptNavigationMixin, FilterTableBodyMixin],
  props: ["filter", "id", "permission", "scriptExecutors"],
  data() {
    return {
      assetId: null,
      assetName: "",
      orderBy: "title",

      sortOrder: [
        {
          field: "title",
          sortField: "title",
          direction: "asc"
        }
      ],

      fields: [
        {
          title: () => this.$t("Name"),
          name: "__slot:title",
          label: this.$t("Name"),
          field: "title",
          sortable: true,
          direction: "none",
          width: 180,
          sortField: "title",
        },
        {
          title: () => this.$t("Description"),
          name: "description",
          label: this.$t("Description"),
          field: "description",
          sortable: true,
          direction: "none",
          width: 180,
          sortField: "description",
        },
        {
          title: () => this.$t("Category"),
          name: "categories",
          sortField: "category.name",
          label: this.$t("Category"),
          field: "category.name",
          sortable: true,
          direction: "none",
          width: 150,
          callback(categories) {
            return categories.map(item => item.name).join(', ');
          },
        },
        {
          title: () => this.$t("Language"),
          name: "language",
          sortField: "language",
          label: this.$t("Language"),
          field: "language",
          sortable: true,
          direction: "none",
          width: 130,
          callback: this.formatLanguage,
        },
        {
          title: () => this.$t("Modified"),
          name: "updated_at",
          sortField: "updated_at",
          label: this.$t("Modified"),
          field: "updated_at",
          sortable: true,
          direction: "none",
          format: "datetime",
          width: 140,
          callback: "formatDate",
        },
        {
          title: () => this.$t("Created"),
          name: "created_at",
          sortField: "created_at",
          label: this.$t("Created"),
          field: "created_at",
          sortable: true,
          direction: "none",
          format: "datetime",
          width: 140,
          callback: "formatDate",
        },
        {
          name: "__slot:actions",
          title: "",
          label: "",
          field: "actions",
        }
      ]
    };
  },

  methods: {
    showModal() {
      this.$refs.myModalRef.show();
    },
    hideModal() {
      this.$refs.myModalRef.hide();
    },
    onSubmit() {
      ProcessMaker.apiClient
        .put("scripts/" + this.dupScript.id + "/duplicate", this.dupScript)
        .then(response => {
          ProcessMaker.alert(this.$t("The script was duplicated."), "success");
          this.hideModal();
          this.fetch();
        })
        .catch(error => {
          if (error.response.status && error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
    },
    formatLanguage(language) {
      return language;
    },
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "scripts" +
            "?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection +
            "&include=categories,category"
        )
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    },
    showAddToProjectModal(title, id) {
      this.assetId = id;
      this.assetName = title;
      this.$refs["add-to-project-modal"].show();
    },
    onClickEllipsis(column) {
      ellipsisSortClick(column, this);
    },
  },

  computed: {}
};
</script>

<style lang="scss" scoped>
:deep(th#_total_users) {
  width: 150px;
  text-align: center;
}

:deep(th#_description) {
  width: 250px;
}

.scripts-table-card {
    padding: 0;
  }
</style>
