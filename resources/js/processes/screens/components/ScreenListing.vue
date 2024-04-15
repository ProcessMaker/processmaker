<template>
  <div class="data-table">
    <data-loading
            :for="/screens/"
            v-show="shouldShowLoader"
            :empty="$t('No Data Available')"
            :empty-desc="$t('')"
            empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="card card-body screen-table-card" data-cy="screens-table">
      <vuetable
        :dataManager="dataManager"
        :noDataTemplate="$t('No Data Available')"
        :sortOrder="sortOrder"
        :css="css"
        :api-mode="false"
        @vuetable:pagination-data="onPaginationData"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
      >
        <template slot="title" slot-scope="props">
          <b-link
            :href="onScreenNavigate('edit-screen', props.rowData, props.rowIndex)"
            v-if="permission.includes('edit-screens')"
          ><span v-uni-id="props.rowData.id.toString()">{{props.rowData.title}}</span></b-link>
          <span v-uni-id="props.rowData.id.toString()" v-else="permission.includes('edit-screens')">{{props.rowData.title}}</span>
        </template>

        <template slot="actions" slot-scope="props">
          <ellipsis-menu
            :actions="screenActions"
            :permission="permission"
            :data="props.rowData"
            :divider="true"
            @navigate="onScreenNavigate"
          />
        </template>
      </vuetable>

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
          field: "title",
          sortField: "title"
        },
        {
          title: () => this.$t("Description"),
          name: "description",
          sortField: "description"
        },
        {
          title: () => this.$t("Category"),
          name: "categories",
          sortField: "category.name",
          callback(categories) {
            return categories.map(item => item.name).join(', ');
          }
        },
        {
          title: () => this.$t("Type"),
          name: "type",
          sortField: "type",
          callback: this.formatType
        },
        {
          title: () => this.$t("Modified"),
          name: "updated_at",
          sortField: "updated_at",
          callback: "formatDate"
        },
        {
          title: () => this.$t("Created"),
          name: "created_at",
          sortField: "created_at",
          callback: "formatDate"
        },
        {
          name: "__slot:actions",
          title: ""
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
