<template>
  <div class="assets p-3">
    <b-navbar type="faded">
      <b-navbar-brand class="text-uppercase">
        {{ $t("Assets") }}
      </b-navbar-brand>
    </b-navbar>

    <div class="mt-3">
      <b-card-group deck>
        <b-card
          v-for="(asset, index) in assets.slice(0, 3)"
          :key="index"
          bg-variant="light"
          class="text-center"
          @click="toggleButtons(index)"
        >
          <div v-show="!showButtons[index]">
            <asset
              :color="asset.color"
              :icon="asset.icon"
              :asset_name="asset.asset_name"
            />
          </div>

          <div
            v-show="showButtons[index]"
            class="text-left"
          >
            <asset-buttons
              :asset_name="asset.asset_name"
              :asset_name_all="asset.asset_name_all"
              :url-path="asset.urlPath"
              :url-asset="asset.urlAsset"
            />
          </div>
        </b-card>
      </b-card-group>
    </div>
    <div class="mt-3">
      <b-card-group deck>
        <b-card
          v-for="(asset, index) in assets.slice(3)"
          :key="index"
          bg-variant="light"
          class="text-center"
          @click="toggleButtons(index + 3)"
        >
          <div v-show="!showButtons[index + 3]">
            <asset
              :color="asset.color"
              :icon="asset.icon"
              :asset_name="asset.asset_name"
            />
          </div>

          <div v-show="showButtons[index + 3]">
            <asset-buttons
              :asset_name="asset.asset_name"
              :asset_name_all="asset.asset_name_all"
              :url-path="asset.urlPath"
              :url-asset="asset.urlAsset"
            />
          </div>
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
  data() {
    return {
      blankTemplate: true,
      selectedTemplate: false,
      templateData: {},
      isProjectsInstalled: false,
      showSelectTemplateModal: true,
      countCategories: 2,
      variant: "Primary",
      showModal: false,
      urlPath: "",
      assets: [
        {
          color: "#4DA2EB",
          icon: "fas fa-play-circle",
          asset_name: "Processes",
          asset_name_all: "See All Processes",
          urlPath: "/processes",
          urlAsset: "/processes?create=true",
        },
        {
          color: "#8EB86F",
          icon: "fas fa-file-alt",
          asset_name: "Screens",
          asset_name_all: "See All Screens",
          urlPath: "/designer/screens",
          urlAsset: "/designer/screens?create=true",
        },
        {
          color: "#F7CF5D",
          icon: "fas fa-code",
          asset_name: "Scripts",
          asset_name_all: "See All Scripts",
          urlPath: "/designer/scripts",
          urlAsset: "/designer/scripts?create=true",
        },
        {
          color: "#712F4A",
          icon: "fas fa-table",
          asset_name: "Decision Tables",
          asset_name_all: "See Decision Tables",
          urlPath: "/designer/decision-tables",
          urlAsset: "/designer/decision-tables?create=true",
        },
        {
          color: "#D66A5F",
          icon: "fas fa-database",
          asset_name: "Collections",
          asset_name_all: "See All Collections",
          urlPath: "/collections",
          urlAsset: "/collections?create=true",
        },
        {
          color: "#B5D3E7",
          icon: "fas fa-share-alt",
          asset_name: "Data Connectors",
          asset_name_all: "See All Data Conn",
          urlPath: "/designer/data-sources",
          urlAsset: "/designer/data-sources?create=true",
        },
      ],
      showButtons: new Array(6).fill(false),
    };
  },
  methods: {
    openSelectTemplateModal() {
      this.showSelectTemplateModal = true;
    },
    toggleButtons(index) {
      this.$set(this.showButtons, index, !this.showButtons[index]);
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
