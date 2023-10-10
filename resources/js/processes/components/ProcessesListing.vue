<template>
  <div class="data-table">
    <data-loading
            :for="/\/processes\?page/"
            v-show="shouldShowLoader"
            :empty="$t('No Data Available')"
            :empty-desc="$t('')"
            empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="card card-body processes-table-card" data-cy="processes-table">
      <vuetable
              :data-manager="dataManager"
              :sort-order="sortOrder"
              :css="css"
              :api-mode="false"
              @vuetable:pagination-data="onPaginationData"
              :fields="fields"
              :data="data"
              data-path="data"
              pagination-path="meta"
              :no-data-template="$t('No Data Available')"
      >
        <template slot="name" slot-scope="props">
          <i tabindex="0"
            v-b-tooltip
            :title="props.rowData.warningMessages.join(' ')"
            class="text-warning fa fa-exclamation-triangle"
            :class="{'invisible': props.rowData.warningMessages.length == 0}">
          </i>
          <i tabindex="0"
            v-if="props.rowData.status == 'ACTIVE' || props.rowData.status == 'INACTIVE'"
            v-b-tooltip
            :title="props.rowData.status"
            class="mr-2"
            :class="{ 'fas fa-check-circle text-success': props.rowData.status == 'ACTIVE', 'far fa-circle': props.rowData.status == 'INACTIVE' }">
          </i>
          <span v-uni-id="props.rowData.id.toString()">{{props.rowData.name}}</span>
        </template>
        <template slot="owner" slot-scope="props">
          <avatar-image
                  class="d-inline-flex pull-left align-items-center"
                  size="25"
                  :input-data="props.rowData.user"
                  hide-name="true"
          ></avatar-image>
        </template>
        <template slot="actions" slot-scope="props">
          <ellipsis-menu 
            @navigate="onProcessNavigate"
            :actions="processActions"
            :permission="permission"
            :data="props.rowData"
            :is-documenter-installed="isDocumenterInstalled"
            :divider="false"
          />
        </template>
      </vuetable>
      <create-template-modal id="create-template-modal" ref="create-template-modal" assetType="process" :currentUserId="currentUserId" :assetName="processTemplateName" :assetId="processId" />
      <create-pm-block-modal id="create-pm-block-modal" ref="create-pm-block-modal" :currentUserId="currentUserId" :assetName="pmBlockName" :assetId="processId" />
      <add-to-project-modal id="add-to-project-modal" ref="add-to-project-modal"  assetType="process" :assetId="processId" :assetName="assetName"/>
      <pagination
        :single="$t('Process')"
        :plural="$t('Processes')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      ></pagination>
    </div>
  </div>
</template>

<script>
  import datatableMixin from "../../components/common/mixins/datatable";
  import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
  import { createUniqIdsMixin } from "vue-uniq-ids";
  import isPMQL from "../../modules/isPMQL";
  import TemplateExistsModal from "../../components/templates/TemplateExistsModal.vue";
  import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";
  import CreatePmBlockModal from "../../components/pm-blocks/CreatePmBlockModal.vue";
  import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
  import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
  import AddToProjectModal from "../../components/shared/AddToProjectModal.vue";
  import processNavigationMixin from "../../components/shared/processNavigation";

  const uniqIdsMixin = createUniqIdsMixin();

  export default {
    components: { TemplateExistsModal, CreateTemplateModal, EllipsisMenu, CreatePmBlockModal, AddToProjectModal},
    mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin, ellipsisMenuMixin, processNavigationMixin],
    props: ["filter", "id", "status", "permission", "isDocumenterInstalled", "pmql", "processName", "currentUserId"],
    data() {
      return {
        orderBy: "name",
        processId: null,
        processTemplateName: '',
        pmBlockName: '',
        assetName: '',
        processData: {},
        sortOrder: [
          {
            field: "name",
            sortField: "name",
            direction: "asc"
          }
        ],

        fields: [
          {
            title: () => this.$t("Name"),
            name: "__slot:name",
            field: "name",
            sortField: "name"
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
            title: () => this.$t("Owner"),
            name: "__slot:owner",
            callback: this.formatUserName
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
    created () {
      ProcessMaker.EventBus.$on("api-data-process", (val) => {
        this.fetch();
      });
    },
    methods: {      
      showCreateTemplateModal(name, id) {        
        this.processId = id;
        this.processTemplateName = name;
        this.$refs["create-template-modal"].show();
      },
      showPmBlockModal(name, id) {        
        this.processId = id;
        this.pmBlockName = name;
        this.$refs["create-pm-block-modal"].show();
      },
      showAddToProjectModal(name, id) {        
        this.processId = id;
        this.assetName = name;
        this.assetType = "process";
        this.$refs["add-to-project-modal"].show();
      },
      formatStatus(status) {
        status = status.toLowerCase();
        let bubbleColor = {
          active: "text-success",
          inactive: "text-danger",
          draft: "text-warning",
          archived: "text-info"
        };
        let response =
            '<i class="fas fa-circle ' + bubbleColor[status] + ' small"></i> ';
        status = status.charAt(0).toUpperCase() + status.slice(1);
        return '<div style="white-space:nowrap">' + response + status + "</div>";
      },
      formatUserName(user) {
        return (
            (user.avatar
                ? this.createImg({
                  src: user.avatar,
                  class: "rounded-user"
                })
                : '<i class="fa fa-user rounded-user"></i>') +
            "<span>" +
            user.fullname +
            "</span>"
        );
      },
      createImg(properties) {
        let container = document.createElement("div");
        let node = document.createElement("img");
        for (let property in properties) {
          node.setAttribute(property, properties[property]);
        }
        container.appendChild(node);
        return container.innerHTML;
      },
      fetch() {
        Vue.nextTick(() => {

          if (this.cancelToken) {
              this.cancelToken();
              this.cancelToken = null;
            }
            const CancelToken = ProcessMaker.apiClient.CancelToken;

          this.loading = true;
          this.apiDataLoading = true;
          //change method sort by user
          this.orderBy = this.orderBy === "user" ? "user.firstname" : this.orderBy;
          //change method sort by slot name
          this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;

          let url =
              this.status === null || this.status === "" || this.status === undefined
                  ? "processes?"
                  : "processes?status=" + this.status + "&";

          let pmql = "";
          if (this.pmql !== undefined) {
              pmql = this.pmql;
          }

          let filter = this.filter;

          if (filter && filter.length) {
            if (filter.isPMQL()) {
              pmql = `(${pmql}) and (${filter})`;
              filter = "";
            }
          }

          // Load from our api client
          ProcessMaker.apiClient
              .get(
                  url +
                  "page=" +
                  this.page +
                  "&per_page=" +
                  this.perPage +
                  "&pmql=" +
                  encodeURIComponent(pmql) +
                  "&filter=" +
                  this.filter +
                  "&order_by=" +
                  this.orderBy +
                  "&order_direction=" +
                  this.orderDirection +
                  "&include=categories,category,user" +
                  "&with=events",
                  {
                    cancelToken: new CancelToken(c => {
                      this.cancelToken = c;
                    }),
                  }
              )
              .then(response => {
                const data = this.addWarningMessages(response.data);
                this.data = this.transform(data);
                this.apiDataLoading = false;
                this.apiNoResults = false;
                this.loading = false;
              }).catch(error => {
                if (error.code === "ERR_CANCELED") {
                  return;
                }
                window.ProcessMaker.alert(error.response.data.message, "danger");
                this.data = [];
              });
        });
      },
      addWarningMessages(data) {
        data.data = data.data.map(process => {
          process.warningMessages = [];
          if (!process.manager_id) {
            process.warningMessages.push(this.$t('Process Manager not configured.'));
          }
          if (process.warnings) {
            process.warningMessages.push(this.$t('BPMN validation issues. Request cannot be started.'));
          }
          return process;
        });
        return data;
      },
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
