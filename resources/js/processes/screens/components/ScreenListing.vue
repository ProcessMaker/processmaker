<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/\/screens\?page/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="screen-table-card"
      data-cy="screens-table"
    >
      <filter-table
        :headers="fields"
        :data="data"
        table-name="screens"
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
            :data-cy="`screens-table-td-${rowIndex}-${colIndex}`"
          >
            <div
              v-if="containsHTML(row[header.field])"
              :data-cy="`screens-table-html-${rowIndex}-${colIndex}`"
              v-html="sanitize(row[header.field])"
            />
            <template v-else>
              <template
                v-if="isComponent(row[header.field])"
                :data-cy="`screens-table-component-${rowIndex}-${colIndex}`"
              >
                <component
                  :is="row[header.field].component"
                  v-bind="row[header.field].props"
                />
              </template>
              <template
                v-else
                :data-cy="`screens-table-field-${rowIndex}-${colIndex}`"
              >
                <template v-if="header.field === 'title'">
                  <b-link
                    v-if="permission.includes('edit-screens')"
                    :href="onScreenNavigate('edit-screen', row, rowIndex)"
                  >
                    <span v-uni-id="row.id.toString()">{{ row.title }}</span>
                  </b-link>
                  <span
                    v-else="permission.includes('edit-screens')"
                    v-uni-id="row.id.toString()"
                  >{{ row.title }}</span>
                </template>
                <template v-if="header.field === 'actions'">
                  <ellipsis-menu
                    :actions="screenActionsWithAddToBundle"
                    :permission="permission"
                    :data="row"
                    :divider="true"
                    @navigate="onScreenNavigate"
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
        asset-type="screen"
        :asset-id="screenId"
        :asset-name="assetName"
        :assigned-projects="assignedProjects"
      />
    </div>
    <pagination-table
      :meta="data.meta"
      @page-change="changePage"
      @per-page-change="changePerPage"
    />
    <b-modal
      ref="myModalRef"
      :title="$t('Copy Screen')"
      centered
      header-close-content="&times;"
    >
      <form>
        <div class="form-group">
          <label for="title">{{ $t('Name') }}<small class="ml-1">*</small></label>
          <input
            id="title"
            v-model="dupScreen.title"
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
          <label for="type">{{ $t('Type') }}</label>
          <select
            id="type"
            class="form-control"
            disabled
          >
            <option>{{ dupScreen.type }}</option>
          </select>
        </div>
        <div class="form-group">
          <category-select
            v-model="dupScreen.screen_category_id"
            :label="$t('Category')"
            api-get="screen_categories"
            api-list="screen_categories"
            :errors="errors.screen_category_id"
          />
        </div>
        <div class="form-group">
          <label for="description">{{ $t('Description') }}</label>
          <textarea
            id="description"
            v-model="dupScreen.description"
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

    <create-template-modal
      id="create-template-modal"
      ref="create-template-modal"
      asset-type="screen"
      :current-user-id="currentUserId"
      :asset-name="screenTemplateName"
      :asset-id="screenId"
      :screen-type="screenType"
      :permission="permission"
      :types="types"
      header-class="border-0"
      footer-class="border-0"
      modal-size="lg"
    />
    <add-to-bundle asset-type="ProcessMaker\Models\Screen" />
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";
import ellipsisMenuMixin from "../../../components/shared/ellipsisMenuActions";
import screenNavigationMixin from "../../../components/shared/screenNavigation";
import CreateTemplateModal from "../../../components/templates/CreateTemplateModal.vue";
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";
import PaginationTable from "../../../components/shared/PaginationTable.vue";
import { ellipsisSortClick } from "../../../components/shared/UtilsTable";
import AddToBundle from "../../../components/shared/AddToBundle.vue";

import AddToProjectModal from "../../../components/shared/AddToProjectModal.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: {
    EllipsisMenu, AddToProjectModal, CreateTemplateModal, PaginationTable, AddToBundle,
  },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ellipsisMenuMixin, screenNavigationMixin],
  props: ["filter", "id", "permission", "currentUserId", "types"],
  data() {
    return {
      orderBy: "updated_at",
      orderDirection: "desc",
      screenId: null,
      assetName: " ",
      assignedProjects: [],
      screenTemplateName: "",
      screenType: "",
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
          width: 150,
          sortField: "title",
        },
        {
          title: () => this.$t("Description"),
          name: "description",
          label: this.$t("Description"),
          field: "description",
          sortable: true,
          direction: "none",
          width: 150,
          sortField: "description",
        },
        {
          title: () => this.$t("Category"),
          name: "categories",
          label: this.$t("Category"),
          field: "categories",
          sortable: true,
          direction: "none",
          width: 150,
          sortField: "category.name",
          cb: (categories) => this.formatCategory(categories),
        },
        {
          title: () => this.$t("Type"),
          name: "type",
          label: this.$t("Type"),
          field: "type",
          sortable: true,
          direction: "none",
          width: 100,
          sortField: "type",
          callback: this.formatType,
        },
        {
          title: () => this.$t("Modified"),
          name: "updated_at",
          label: this.$t("Modified"),
          field: "updated_at",
          sortable: true,
          direction: "desc",
          format: "datetime",
          width: 140,
          sortField: "updated_at",
          callback: "formatDate",
        },
        {
          title: () => this.$t("Created"),
          name: "created_at",
          label: this.$t("Created"),
          field: "created_at",
          sortable: true,
          direction: "none",
          format: "datetime",
          width: 140,
          sortField: "created_at",
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
    screenActionsWithAddToBundle() {
      return this.screenActions.toSpliced(3, 0, {
        value: "add-to-bundle",
        content: "Add to Bundle",
        icon: "fp-add-outlined",
        permission: "admin",
        emit_on_root: "add-to-bundle",
      });
    },
  },
  created() {
    ProcessMaker.EventBus.$on("api-data-process", () => {
      this.fetch();
      this.apiDataLoading = false;
      this.apiNoResults = false;
    });
  },
  methods: {
    formatType(type) {
      return this.$t(_.startCase(_.toLower(type)));
    },
    showModal() {
      this.$refs.myModalRef.show();
    },
    hideModal() {
      this.$refs.myModalRef.hide();
    },
    onSubmit() {
      ProcessMaker.apiClient
        .put(`screens/${this.dupScreen.id}/duplicate`, this.dupScreen)
        .then((response) => {
          ProcessMaker.alert(this.$t("The screen was duplicated."), "success");
          this.hideModal();
          this.fetch();
        })
        .catch((error) => {
          if (error.response.status && error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
    },
    showAddToProjectModal(title, id, projects) {
      this.screenId = id;
      this.assetName = title;
      this.assignedProjects = projects;
      this.$refs["add-to-project-modal"].show();
    },
    fetch() {
      this.loading = true;
      // change method sort by slot name
      this.orderBy = this.orderBy === "__slot:updated_at" ? "updated_at" : this.orderBy;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "screens"
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
            }&include=categories,category`
            + "&exclude=config",
        )
        .then((response) => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    },
    showCreateTemplateModal(name, id, type) {
      this.screenId = id;
      this.screenTemplateName = name;
      this.screenType = type;
      this.$refs["create-template-modal"].show();
    },
    changePage(page) {
      this.page = page;
      this.fetch();
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

:deep(.rounded-user) {
  border-radius: 50% !important;
  height: 1.5em;
  margin-right: 0.5em;
}

.screen-table-card {
    padding: 0;
}
</style>
