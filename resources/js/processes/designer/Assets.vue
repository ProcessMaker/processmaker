<template>
  <div
    v-if="hasPermission()"
    class="assets mb-3 border"
  >
    <b-navbar type="faded">
      <b-navbar-brand class="title-designer">
        {{ $t("Assets") }}
      </b-navbar-brand>
    </b-navbar>

    <div class="mx-2">
      <b-card-group deck>
        <b-card
          v-for="(asset, index) in assetsCore"
          :key="index"
          bg-variant="light"
          class="text-center"
          @mouseover="toggleButtons(index, 'core', true)"
          @mouseleave="toggleButtons(index, 'core', false)"
        >
          <template v-if="!showButtonsCore[index]">
            <asset
              :color="asset.color"
              :icon="asset.icon"
              :image_icon="asset.image_icon"
              :asset_name="asset.asset_name"
            />
          </template>

          <template v-if="showButtonsCore[index]">
            <asset-buttons
              :asset_name_all="asset.asset_name_all"
              :asset_name_new="asset.asset_name_new"
              :url-path="asset.urlPath"
              :url-asset="asset.urlAsset"
            />
          </template>
        </b-card>
      </b-card-group>
    </div>
    <div class="mx-2">
      <b-card-group deck>
        <b-card
          v-for="(asset, index) in assetsPackage"
          v-if="isPackageInstalled(asset.package)"
          :key="index"
          bg-variant="light"
          class="text-center"
          @mouseover="toggleButtons(index, 'package', true)"
          @mouseleave="toggleButtons(index, 'package', false)"
        >
          <template v-if="!showButtonsPackage[index]">
            <asset
              :color="asset.color"
              :icon="asset.icon"
              :image_icon="asset.image_icon"
              :asset_name="asset.asset_name"
            />
          </template>

          <template v-if="showButtonsPackage[index]">
            <asset-buttons
              :asset_name_all="asset.asset_name_all"
              :asset_name_new="asset.asset_name_new"
              :url-path="asset.urlPath"
              :url-asset="asset.urlAsset"
            />
          </template>
        </b-card>
      </b-card-group>
    </div>
    <div class="pl-2 col-4">
      <b-card-group deck>
        <b-card
          v-for="(asset, index) in assetsPackageExtra"
          v-if="isPackageInstalled(asset.package)"
          :key="index"
          bg-variant="light"
          class="text-center"
          @mouseover="toggleButtons(index, 'package-extra', true)"
          @mouseleave="toggleButtons(index, 'package-extra', false)"
        >
          <template v-if="!showButtonsPackageExtra[index]">
            <asset
              :color="asset.color"
              :icon="asset.icon"
              :image_icon="asset.image_icon"
              :asset_name="asset.asset_name"
            />
          </template>

          <template v-if="showButtonsPackageExtra[index]">
            <asset-buttons
              :asset_name_all="asset.asset_name_all"
              :asset_name_new="asset.asset_name_new"
              :url-path="asset.urlPath"
              :url-asset="asset.urlAsset"
            />
          </template>
        </b-card>
      </b-card-group>
    </div>
  </div>
</template>
<script>
import Asset from "./Asset.vue";
import AssetButtons from "./AssetButtons.vue";

export default {
  components: {
    Asset,
    AssetButtons,
  },
  props: ["permission"],
  data() {
    return {
      urlPath: "",
      assetsCore: [
        {
          color: "#4DA2EB",
          icon: "fas fa-play-circle",
          asset_name: "Processes",
          asset_name_all: "View All Processes",
          asset_name_new: "New Process",
          urlPath: "/processes",
          urlAsset: "/processes?new=true",
        },
        {
          color: "#8EB86F",
          icon: "fas fa-file-alt",
          asset_name: "Screens",
          asset_name_all: "View All Screens",
          asset_name_new: "New Screen",
          urlPath: "/designer/screens",
          urlAsset: "/designer/screens?create=true",
        },
        {
          color: "#F7CF5D",
          icon: "fas fa-code",
          asset_name: "Scripts",
          asset_name_all: "View All Scripts",
          asset_name_new: "New Script",
          urlPath: "/designer/scripts",
          urlAsset: "/designer/scripts?create=true",
        },
      ],
      assetsPackage: [
        {
          color: "#712F4A",
          icon: "fas fa-table",
          asset_name: "Decision Tables",
          asset_name_all: "View All Decision Tables",
          asset_name_new: "New Decision Table",
          urlPath: "/designer/decision-tables",
          urlAsset: "/designer/decision-tables?create=true",
          package: "package-decision-engine",
        },
        {
          color: "#B5D3E7",
          icon: "fas fa-share-alt",
          asset_name: "Data Connectors",
          asset_name_all: "View All Data Connectors",
          asset_name_new: "New Data Connector",
          urlPath: "/designer/data-sources",
          urlAsset: "/designer/data-sources?create=true",
          package: "package-data-sources",
        },
        {
          color: "#556271",
          image_icon: require("../../../img/flowGenieIcon.svg"),
          asset_name: "FlowGenie",
          asset_name_all: "View All Genies",
          asset_name_new: "New Genie",
          urlPath: "/designer/flow-genies",
          urlAsset: "/designer/flow-genies?create=true",
          package: "package-ai",
        },
      ],
      assetsPackageExtra: [
        {
          color: "#5E4FE2",
          icon: "fas fa-database",
          asset_name: "Collections",
          asset_name_all: "View All Collections",
          asset_name_new: "New Collection",
          urlPath: "/collections",
          urlAsset: "/collections?create=true",
          package: "package-collections",
        },
      ],
      showButtonsCore: new Array(3).fill(false),
      showButtonsPackage: new Array(3).fill(false),
      showButtonsPackageExtra: new Array(3).fill(false),
    };
  },
  methods: {
    isPackageInstalled(packageName) {
      return window.ProcessMaker?.packages?.includes(packageName);
    },
    toggleButtons(index, section, status) {
      if (section === "core") {
        this.$set(this.showButtonsCore, index, status);
      }
      if (section === "package") {
        this.$set(this.showButtonsPackage, index, status);
      }
      if (section === "package-extra") {
        this.$set(this.showButtonsPackageExtra, index, status);
      }
    },
    hasPermission() {
      return this.permission.includes("view-processes")
      || this.permission.includes("view-screens")
      || this.permission.includes("view-data-sources")
      || this.permission.includes("view-decision_tables")
      || this.permission.includes("view-scripts")
      || this.permission.includes("create-data-sources")
      || this.permission.includes("create-decision_tables")
      || this.permission.includes("create-processes")
      || this.permission.includes("create-screens")
      || this.permission.includes("create-scripts");
    },
  },
};
</script>

<style scoped>
.assets {
  background-color: #f9f9f9;
  border-radius: 8px;
  padding: 0rem 1rem 1rem 1rem;
}
.card {
  margin: 8px;
  border: none;
  padding: 0;
  border-radius: 8px;
  box-shadow: 0px 0px 11px 0px rgba(98, 124, 144, 0.20);
}
.b-card-group {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  padding: 10px;
}
.card-body {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 8px;
}
</style>
