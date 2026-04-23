<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/scripts\?page/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="scripts-table-card"
      data-cy="scripts-table"
    >
      <filter-table
        :headers="fields"
        :data="data"
        table-name="scripts"
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
            @click="onClickEllipsis(column)"
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
        <template
          v-for="(row, rowIndex) in data.data"
          #[`row-${rowIndex}`]
        >
          <td
            v-for="(header, colIndex) in fields"
            :key="colIndex"
            :data-cy="`scripts-table-td-${rowIndex}-${colIndex}`"
          >
            <div
              v-if="containsHTML(row[header.field])"
              :data-cy="`datasource-table-html-${rowIndex}-${colIndex}`"
              v-html="sanitize(row[header.field])"
            />
            <template v-else>
              <template
                v-if="isComponent(row[header.field])"
                :data-cy="`scripts-table-component-${rowIndex}-${colIndex}`"
              >
                <component
                  :is="row[header.field].component"
                  v-bind="row[header.field].props"
                />
              </template>
              <template
                v-else
                :data-cy="`scripts-table-field-${rowIndex}-${colIndex}`"
              >
                <template v-if="header.field === 'title'">
                  <b-link
                    v-if="permission.includes('edit-scripts')"
                    v-uni-id="row.id.toString()"
                    :href="`/designer/scripts/${row.id}/builder`"
                  >
                    {{ row.title }}
                  </b-link>
                  <span
                    v-else="permission.includes('edit-scripts')"
                    v-uni-id="row.id.toString()"
                  >{{ row.title }}</span>
                </template>
                <template v-if="header.field === 'actions'">
                  <ellipsis-menu
                    :actions="scriptActionsWithAddToBundle"
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

      <add-to-project-modal
        id="add-to-project-modal"
        ref="add-to-project-modal"
        asset-type="script"
        :asset-id="assetId"
        :asset-name="assetName"
      />

      <pagination-table
        :meta="data.meta"
        @page-change="changePage"
        @per-page-change="changePerPage"
      />
    </div>
    <b-modal
      ref="myModalRef"
      :title="$t('Copy Script')"
      centered
      header-close-content="&times;"
    >
      <form>
        <div class="form-group">
          <label for="title">{{ $t('Name') }}<small class="ml-1">*</small></label>
          <input
            id="title"
            v-model="dupScript.title"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': errors.title }"
          >
          <div
            v-if="errors.title"
            class="invalid-feedback"
            role="alert"
          >
            {{ errors.title[0] }}
          </div>
        </div>
        <div class="form-group">
          <category-select
            v-model="dupScript.script_category_id"
            :label="$t('Category')"
            api-get="script_categories"
            api-list="script_categories"
            :errors="errors.script_category_id"
          />
        </div>
        <div class="form-group">
          <label for="description">{{ $t('Description') }}</label>
          <textarea
            id="description"
            v-model="dupScript.description"
            class="form-control"
            rows="3"
          />
        </div>
      </form>
      <div
        slot="modal-footer"
        class="w-100"
        align="right"
      >
        <button
          type="button"
          class="btn btn-outline-secondary"
          @click="hideModal"
        >
          {{ $t('Cancel') }}
        </button>
        <button
          type="button"
          class="btn btn-secondary ml-2"
          @click="onSubmit"
        >
          {{ $t('Save') }}
        </button>
      </div>
    </b-modal>
    <add-to-bundle asset-type="ProcessMaker\Models\Script" />
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
import AddToBundle from "../../../components/shared/AddToBundle.vue";
import { FilterTableBodyMixin, ellipsisSortClick } from "../../../components/shared";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { EllipsisMenu, AddToProjectModal, AddToBundle },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ellipsisMenuMixin, scriptNavigationMixin, FilterTableBodyMixin],
  props: ["filter", "id", "permission", "scriptExecutors"],
  data() {
    return {
      assetId: null,
      assetName: "",
      orderBy: "updated_at",
      orderDirection: "desc",
      sortOrder: [
        {
          field: "updated_at",
          sortField: "updated_at",
          direction: "desc",
        },
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
          field: "categories",
          sortable: true,
          direction: "none",
          width: 150,
          cb: (categories) => this.formatCategory(categories),
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
          direction: "desc",
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
        },
      ],
    };
  },
  computed: {
    scriptActionsWithAddToBundle() {
      return this.scriptActions.toSpliced(3, 0, {
        value: "add-to-bundle",
        content: "Add to Bundle",
        icon: "fp-add-outlined",
        permission: "admin",
        emit_on_root: "add-to-bundle",
      });
    },
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
        .put(`scripts/${this.dupScript.id}/duplicate`, this.dupScript)
        .then((response) => {
          ProcessMaker.alert(this.$t("The script was duplicated."), "success");
          this.hideModal();
          this.fetch();
        })
        .catch((error) => {
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
          "scripts"
            + `?page=${
              this.page
            }&per_page=${
              this.perPage
            }&filter=${
              this.filter
            }&order_by=${
              this.orderBy
            }&order_direction=${
              this.orderDirection
            }&include=categories,category`,
        )
        .then((response) => {
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
