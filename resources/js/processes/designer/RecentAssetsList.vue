<template>
  <div v-if="data.data.length === 0" class="container">
    <div class="content">
      <img
        class="image"
        src="/img/recent_assets.svg"
        alt="resent assets"
      >
      <div class="content-text">
        <span class="title">
          {{ $t("Recent Assets") }}
        </span>
        <p>{{ $t("No assets to display here yet") }}</p>
      </div>
    </div>
  </div>
  <div v-else class="data-table">
    <div class="data-table">
      <data-loading
        v-if="shouldShowLoader"
        :for="/projects\{project_id}?/"
        :empty="'No Data Available'"
        :empty-desc="''"
        empty-icon="noData"
      />
      <div
        v-show="!shouldShowLoader"
        class="card card-body processes-table-card asset-listing-table"
        data-cy="asset-listing-table"
      >
        <vuetable
          :dataManager="dataManager"
          :sort-order="sortOrder"
          :api-mode="false"
          :css="css"
          :fields="fields"
          :data="data"
          data-path="data"
          pagination-path="meta"
          :no-data-template="$t('No Data Available')"
        >
          <template slot="asset_type" slot-scope="props">
            <span
              v-uni-id="props.rowData.id.toString()"
              class="asset_title" :class="'asset_type_' + formatClassName(props.rowData.asset_type)"
            >
              {{ props.rowData.asset_type }}
            </span>
          </template>

          <template slot="actions" slot-scope="props">
            <ellipsis-menu
              :actions="getActions(props.rowData)"
              :data="props.rowData"
              :permission="permission"
              :is-documenter-installed="isDocumenterInstalled"
              :divider="getAssetDivider(props.rowData)"
              @navigate="onNavigate"
            />
          </template>

          <template
            slot="name"
            slot-scope="props"
          >
            <a :href="generateAssetLink(props.rowData)">{{ props.rowData.name }}</a>
          </template>
        </vuetable>
        <create-template-modal
          id="create-template-modal"
          ref="create-template-modal"
          assetType="process"
          :currentUserId="currentUserId"
          :assetName="processTemplateName"
          :assetId="assetId"
        />
        <create-pm-block-modal
          id="create-pm-block-modal"
          ref="create-pm-block-modal"
          :currentUserId="currentUserId"
          :assetName="pmBlockName"
          :assetId="assetId"
        />
        <add-to-project-modal
          id="add-to-project-modal"
          ref="add-to-project-modal"
          :assetType="assetType"
          :assetId="assetId"
          :assetName="assetName"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import dataSourceNavigationMixin from "../../components/shared/dataSourceNavigation";
import decisionTableNavigationMixin from "../../components/shared/decisionTableNavigation";
import flowGenieNavigationMixin from "../../components/shared/flowGenieNavigation";
import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
import processNavigationMixin from "../../components/shared/processNavigation";
import screenNavigationMixin from "../../components/shared/screenNavigation";
import scriptNavigationMixin from "../../components/shared/scriptNavigation";

import AddToProjectModal from "../../components/shared/AddToProjectModal.vue";
import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";
import CreatePmBlockModal from "../../components/pm-blocks/CreatePmBlockModal.vue";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: {
    AddToProjectModal,
    CreateTemplateModal,
    CreatePmBlockModal,
    EllipsisMenu,
  },
  mixins: [
    datatableMixin,
    dataLoadingMixin,
    dataSourceNavigationMixin,
    decisionTableNavigationMixin,
    flowGenieNavigationMixin,
    ellipsisMenuMixin,
    processNavigationMixin,
    screenNavigationMixin,
    scriptNavigationMixin,
    uniqIdsMixin,
  ],
  props: ["types", "currentUserId", "permission", "isDocumenterInstalled", "project"],
  data() {
    return {
      data: {
        data: [],
      },
      sortOrder: [{
        field: "updated_at",
        sortField: "updated_at",
        direction: "desc",
      }],
      fields: [{
        title: () => "Type",
        name: "__slot:asset_type",
      },
      {
        title: () => "Name",
        name: "__slot:name",
        sortField: "name",
      },
      {
        title: () => this.$t("Last Modified"),
        name: "updated_at",
        sortField: "updated_at",
        callback: "formatDate",
      },
      {
        name: "__slot:actions",
        title: "",
      }],
      assetType: "",
      configs: "",
      assetName: "",
      assetId: "",
      processTemplateName: "",
      pmBlockName: "",
    };
  },
  methods: {
    /**
     * get data for Recent Assets
     */
    fetch(pmql = "") {
      if (this.project) {
        this.loading = true;
        this.apiDataLoading = true;
        let url = "projects/assets/recent?";
        // Load from our api client
        window.ProcessMaker.apiClient
          .get(
              url +
              "asset_types=" +
              this.types +
              "&pmql=" +
              encodeURIComponent(pmql) +
              "&order_by=" +
              this.orderBy +
              "&order_direction=" +
              this.orderDirection,
            )
          .then((response) => {
            this.data = this.transform(response.data);
            this.apiDataLoading = false;
            this.loading = false;
          }).catch((error) => {
            ProcessMaker.alert(error.response?.data?.message, "danger");
            this.data = [];
          });
      }
    },
    /**
     * reload page
     */
    reload() {
      this.$emit("reload");
    },
    /**
     * get assets actions
     */
    getActions(data) {
      switch (data.asset_type) {
        case "Process":
          return this.processActions;
        case "Screen":
          return this.screenActions.filter((object) => object.value !== "duplicate-item");
        case "Script":
          return this.scriptActions;
        case "Data Source":
          return this.dataSourceActions;
        case "Decision Table":
          return this.decisionTableActions;
        case "Flow Genie":
          return this.flowGenieActions;
        default:
          return []; // Handle unknown asset types as needed
      }
    },
    /**
     * get asset menu divider
     */
    getAssetDivider(data) {
      return data.asset_type !== "Process";
    },
    /**
     * get asset link
     */
    generateAssetLink(data) {
      switch (data.asset_type) {
        case 'Process':
            return `/modeler/${data.id}`;
        case 'Screen':
            return `/designer/screen-builder/${data.id}/edit`;
        case 'Script':
            return `/designer/scripts/${data.id}/builder`;
        case 'Data Source':
            return `/designer/data-sources/${data.id}/edit`;
        case 'Decision Table':
            return `/designer/decision-tables/table-builder/${data.id}/edit`;
        default:
            return ''; // Handle unknown asset types as needed
      }
    },
    /**
     * get class for asset
     */
    formatClassName(name) {
      return name.toLowerCase().replace(/\s+/g, "_");
    },
    /**
     * go to navigate action
     */
    onNavigate(action, data) {
      switch (data.asset_type) {
        case "Process":
          this.assetType = "process";
          this.onProcessNavigate(action, data);
          break;
        case "Screen":
          this.assetType = "screen";
          this.onScreenNavigate(action, data);
          break;
        case "Script":
          this.assetType = "script";
          this.onScriptNavigate(action, data);
          break;
        case "Data Source":
          this.assetType = "data-source";
          this.onDataSourceNavigate(action, data);
          break;
        case "Decision Table":
          this.assetType = "decision-table";
          this.onDecisionTableNavigate(action, data);
          break;
        case "Flow Genie":
          this.assetType = "flow-genie";
          this.onFlowGenieNavigate(action, data);
          break;
        default:
          break; // Handle unknown asset types as needed
      }
    },
    /**
     * open modal add to project
     */
    showAddToProjectModal(title, id) {
      this.assetId = id;
      this.assetName = title;
      this.$refs["add-to-project-modal"].show();
    },
    /**
     * open modal to create template
     */
    showCreateTemplateModal(name, id) {
      this.processId = id;
      this.processTemplateName = name;
      this.$refs["create-template-modal"].show();
    },
    /**
     * open modal to PM Block
     */
    showPmBlockModal(name, id) {
      this.processId = id;
      this.pmBlockName = name;
      this.$refs["create-pm-block-modal"].show();
    },
  },
};
</script>

<style lang="scss">

.asset-listing-table {
  .table {
    .vuetable-body {
      tr {
        td {
          padding: 7px 15px;
          vertical-align: middle;
          border-bottom: 1px solid #e9edf1;
        }
      }
    }
  }
  .asset_title {
    position: relative;
    &::before {
      content: " ";
      position: absolute;
      height: 44px;
      width: 4px;
      left: -11px;
      top: 0;
      bottom: 0;
      margin: auto;
      border-radius: 10px;
    }
    &.asset_type_screen {
      &::before {
        background:#8EB86F;
      }
    }

    &.asset_type_process {
      &::before {
        background:#4DA2EB;
      }
    }

    &.asset_type_script {
      &::before {
        background:#F7CF5D;
      }
    }

    &.asset_type_data_source {
      &::before {
        background: #73BAE38F;
      }
    }

    &.asset_type_decision_table {
      &::before {
        background:#712F4A;
      }
    }

    &.asset_type_flow_genie {
      &::before {
        background:#4b667c;
      }
    }
  }
  .processes-table-card {
    padding: 0;
    overflow-y: scroll;
    display: block;
  }
}
.container {
  display: flex;
  justify-content: center;
  align-items: center;
  flex: 1 0 0;
  align-self: stretch;
  width: 100%;
  height: 815px;
}
.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.image {
  width: 244px;
  height: 219px;
}
.content-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}
.title {
  color: var(--secondary-800, #44494E);
  font-size: 32px;
  font-style: normal;
  font-weight: 600;
  line-height: 38px;
  letter-spacing: -1.28px;
}
</style>
