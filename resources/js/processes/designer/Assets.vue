<template>
  <div
    v-if="hasPermission()"
    class="assets p-3 mb-3"
  >
    <b-navbar type="faded">
      <b-navbar-brand class="text-uppercase">
        {{ $t("Assets") }}
      </b-navbar-brand>
    </b-navbar>

    <div class="mt-3">
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
    <div class="mt-3">
      <b-card-group deck>
        <b-card
          v-for="(asset, index) in assetsPackage"
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
        <b-card style="opacity: 0"/>
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
          asset_name_new: "Create New Process",
          urlPath: "/processes",
          urlAsset: "/processes?new=true",
        },
        {
          color: "#8EB86F",
          icon: "fas fa-file-alt",
          asset_name: "Screens",
          asset_name_all: "View All Screens",
          asset_name_new: "Create New Screen",
          urlPath: "/designer/screens",
          urlAsset: "/designer/screens?create=true",
        },
        {
          color: "#F7CF5D",
          icon: "fas fa-code",
          asset_name: "Scripts",
          asset_name_all: "View All Scripts",
          asset_name_new: "Create New Script",
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
          asset_name_new: "Create New Decision Table",
          urlPath: "/designer/decision-tables",
          urlAsset: "/designer/decision-tables?create=true",
        },
        {
          color: "#B5D3E7",
          icon: "fas fa-share-alt",
          asset_name: "Data Connectors",
          asset_name_all: "View All Data Connectors",
          asset_name_new: "Create New Data Connector",
          urlPath: "/designer/data-sources",
          urlAsset: "/designer/data-sources?create=true",
        },
      ],
      showButtonsCore: new Array(3).fill(false),
      showButtonsPackage: new Array(3).fill(false),
    };
  },
  methods: {
    toggleButtons(index, section, status) {
      if (section === "core") {
        this.$set(this.showButtonsCore, index, status);
      }
      if (section === "package") {
        this.$set(this.showButtonsPackage, index, status);
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
}
.b-card {
  margin: 5px;
  border: none;
  padding: 0;
}
.b-card-group {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
}
</style>
