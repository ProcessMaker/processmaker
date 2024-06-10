<template>
  <div class="data-table">
    <data-loading
            :for="/screens/"
            v-show="shouldShowLoader"
            :empty="$t('No Data Available')"
            :empty-desc="$t('')"
            empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="screen-table-card" data-cy="screens-table">
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
            @click="handleEllipsisClick(column)"
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
                    :href="onScreenNavigate('edit-screen', row, rowIndex)"
                    v-if="permission.includes('edit-screens')"
                  ><span v-uni-id="row.id.toString()">{{row.title}}</span></b-link>
                  <span v-uni-id="row.id.toString()" v-else="permission.includes('edit-screens')">{{ row.title }}</span>
                </template>
                <template v-if="header.field === 'actions'">
                  <ellipsis-menu
                    :actions="screenActions"
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

      <add-to-project-modal id="add-to-project-modal" ref="add-to-project-modal"  assetType="screen" :assetId="screenId" :assetName="assetName" :assignedProjects="assignedProjects"/>
    </div>
    <pagination-table
      :meta="data.meta"
      @page-change="changePage"
      @per-page-change="changePerPage"
    />
    <b-modal ref="myModalRef" :title="$t('Copy Screen')" centered header-close-content="&times;">
      <form>
        <div class="form-group">
          <label for="title">{{$t('Name')}}<small class="ml-1">*</small></label>
          <input id="title"
            type="text"
            class="form-control"
            v-model="dupScreen.title"
            v-bind:class="{ 'is-invalid': errors.title }"
          />
          <div class="invalid-feedback" role="alert" v-if="errors.title">{{errors.title[0]}}</div>
        </div>
        <div class="form-group">
          <label for="type">{{$t('Type')}}</label>
          <select class="form-control" id="type" disabled>
            <option>{{dupScreen.type}}</option>
          </select>
        </div>
        <div class="form-group">
          <category-select
          :label="$t('Category')"
          api-get="screen_categories"
          api-list="screen_categories"
          v-model="dupScreen.screen_category_id"
          :errors="errors.screen_category_id">
          </category-select>
        </div>
        <div class="form-group">
          <label for="description">{{$t('Description')}}</label>
          <textarea class="form-control" id="description" rows="3" v-model="dupScreen.description"></textarea>
        </div>
      </form>
      <div slot="modal-footer" class="w-100" align="right">
        <button type="button" class="btn btn-outline-secondary" @click="hideModal">{{$t('Cancel')}}</button>
        <button type="button" @click="onSubmit" class="btn btn-secondary ml-2">{{$t('Save')}}</button>
      </div>
    </b-modal>

    <create-template-modal
      id="create-template-modal"
      ref="create-template-modal"
      asset-type="screen"
      :current-user-id="currentUserId"
      :asset-name="screenTemplateName"
      :asset-id="screenId"
      :screenType="screenType"
      :permission="permission"
      :types="types"
      headerClass="border-0"
      footerClass="border-0"
      modal-size="lg"
    />
  </div>
</template>

<script>
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";
import ellipsisMenuMixin from "../../../components/shared/ellipsisMenuActions";
import screenNavigationMixin from "../../../components/shared/screenNavigation";
import CreateTemplateModal from "../../../components/templates/CreateTemplateModal.vue";
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";
import PaginationTable from "../../../components/shared/PaginationTable.vue";

import { createUniqIdsMixin } from "vue-uniq-ids";
import AddToProjectModal from "../../../components/shared/AddToProjectModal.vue";
const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { EllipsisMenu, AddToProjectModal, CreateTemplateModal, PaginationTable },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ellipsisMenuMixin, screenNavigationMixin],
  props: ["filter", "id", "permission", "currentUserId", 'types'],
  data() {
    return {
      orderBy: "title",
      screenId: null,
      assetName: " ",
      assignedProjects: [],
      screenTemplateName: "",
      screenType: "",
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
          field: "category.name",
          sortable: true,
          direction: "none",
          width: 150,
          sortField: "category.name",
          callback(categories) {
            return categories.map(item => item.name).join(', ');
          },
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
          direction: "none",
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
        }
      ]
    };
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
        .put("screens/" + this.dupScreen.id + "/duplicate", this.dupScreen)
        .then(response => {
          ProcessMaker.alert(this.$t("The screen was duplicated."), "success");
          this.hideModal();
          this.fetch();
        })
        .catch(error => {
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
      //change method sort by slot name
      this.orderBy = this.orderBy === "__slot:title" ? "title" : this.orderBy;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "screens" +
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
            "&include=categories,category" +
            "&exclude=config"
        )
        .then(response => {
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
    handleEllipsisClick(categoryColumn) {
      this.fields.forEach(column => {
        if (column.field !== categoryColumn.field) {
          column.direction = "none";
          column.filterApplied = false;
        }
      });

      if (categoryColumn.direction === "asc") {
        categoryColumn.direction = "desc";
      } else if (categoryColumn.direction === "desc") {
        categoryColumn.direction = "none";
        categoryColumn.filterApplied = false;
      } else {
        categoryColumn.direction = "asc";
        categoryColumn.filterApplied = true;
      }

      if (categoryColumn.direction !== "none") {
        const sortOrder = [
          {
            sortField: categoryColumn.sortField || categoryColumn.field,
            direction: categoryColumn.direction,
          },
        ];
        this.dataManager(sortOrder);
      } else {
        this.fetch();
      }
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

:deep(.rounded-user) {
  border-radius: 50% !important;
  height: 1.5em;
  margin-right: 0.5em;
}

.screen-table-card {
    padding: 0;
}
</style>
