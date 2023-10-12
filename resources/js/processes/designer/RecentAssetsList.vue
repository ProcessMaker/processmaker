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
              :actions="actions"
              :data="props.rowData"
              :divider="true"
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
      </div>
    </div>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: {
    EllipsisMenu,
  },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  props: ["types"],
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
        sortField: "asset_type",
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
      actions: [{
        value: "duplicate-item",
        content: "Duplicate",
        icon: "fas fa-copy",
        link: false,
      },
      {
        value: "remove-item",
        content: "Remove",
        icon: "fas fa-unlink",
        link: false,
      }],
      configs: "",
    };
  },
  mounted() {
    this.fetch();
  },
  methods: {
    fetch() {
      this.loading = true;
      this.apiDataLoading = true;
      // Load from our api client
      window.ProcessMaker.apiClient
        .get(
          `projects/assets/recent?
          asset_types=
          ${this.types}
          `,
        )
        .then((response) => {
          this.data = this.transform(response.data);
          console.log(this.data);
          this.apiDataLoading = false;
          this.loading = false;
        }).catch((error) => {
          ProcessMaker.alert(error.response?.data?.message, "danger");
          this.data = [];
        });
    },
    reload() {
      this.$emit("reload");
    },
    generateAssetLink(data) {
      switch (data.asset_type) {
        case "Process":
          return `/modeler/${data.id}`;
        case "Screen":
          return `/designer/screen-builder/${data.id}/edit`;
        case "Script":
          return `/designer/scripts/${data.id}/edit`;
        case "Data Source":
          return `/designer/data-sources/${data.id}/edit`;
        case "Decision Table":
          return `/designer/decision-tables/${data.id}/edit`;
        default:
          return ""; // Handle unknown asset types as needed
      }
    },
    formatClassName(name) {
      return name.toLowerCase().replace(/\s+/g, "_");
    },
    onNavigate(action, data) {
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
  }
  .processes-table-card {
    padding: 0;
    overflow-y: scroll;
    display: block;
  }
}

</style>
