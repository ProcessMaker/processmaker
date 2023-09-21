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
        
      <div v-show="showButtons[index]" class="text-left">
        <asset-buttons
        :asset_name_all="asset.asset_name_all"
          :variant="variant"
          :urlPath="asset.urlPath">

      <template v-slot:select-template-slot>
        <div v-if="asset.flag === 1">
            <select-template-modal 
            :type="'Process'"
            ref="selectTemplateModal"
            :countCategories="countCategories"
            :customButton="'custom-button'"
          >
          </select-template-modal>
        </div>
        <div v-if="asset.flag === 2">
          <create-screen-modal
                        :type="$t('Screens')"
                        ref="create-screen-modal" 
                        :count-categories=2
                        :script-executors=false
                        is-projects-installed="false"
                    />
        </div>
        <div v-if="asset.flag === 3">
          <create-script-modal
                        :type="$t('Screen')"
                        ref="create-script-modal" 
                        :count-categories=2
                        :script-executors=false
                        is-projects-installed="false"
                    />
        </div>
        
       

      </template>
    </asset-buttons>
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
        :asset_name_all="asset.asset_name_all"
          :variant="variant"
          :urlPath="asset.urlPath">

      <template v-slot:select-template-slot>
        <div v-if="asset.flag === 4">
            <div>
              <b-button :aria-label="createProcess" v-b-modal.selectTemplate class="mb-3 mb-md-0 ml-md-2">
                <i class="fas fa-plus"/> Decision Tables
              </b-button>
            </div>
        </div>
        <div v-if="asset.flag === 5">
          <div>
              <b-button :aria-label="createProcess" v-b-modal.selectTemplate class="mb-3 mb-md-0 ml-md-2">
                <i class="fas fa-plus"/> Collections
              </b-button>
            </div>
        </div>
        <div v-if="asset.flag === 6">
          <div>
              <b-button :aria-label="createProcess" v-b-modal.createScript class="mb-3 mb-md-0 ml-md-2">
                <i class="fas fa-plus"/> Data Connectors
              </b-button>
            </div>
        </div>

      </template>
    </asset-buttons>
      </div>

        </b-card>
          
      </b-card-group>
    </div>
  </div>
</template>
<script>
import Asset from "./Asset.vue";
import AssetButtons from "./AssetButtons.vue";
import SelectTemplateModal from "../../components/templates/SelectTemplateModal.vue";
import CreateProcessModal from "../components/CreateProcessModal.vue";
import ProcessesListing from "../components/ProcessesListing.vue";
import CategorySelect from "../categories/components/CategorySelect.vue";
import CreateScriptModal from "../../../js/processes/scripts/components/CreateScriptModal.vue";
import CreateScreenModal from "../screens/components/CreateScreenModal.vue";


export default {
  components: {
    Asset,
    AssetButtons,
    SelectTemplateModal,
    CreateProcessModal,
    ProcessesListing,
    CategorySelect,
    CreateScriptModal,
    CreateScreenModal,
  },
  data() {
    return {
      blankTemplate: true,
        selectedTemplate: false,
        templateData: {},
        isProjectsInstalled: false,
        showSelectTemplateModal: true,
        countCategories: 2,
        variant: 'Primary',
      showModal: false,
      //showButtons: false,
      urlPath: '',
      assets: [
        {
          color: "#4DA2EB",
          icon: "fas fa-play-circle",
          asset_name: "Processes",
          asset_name_all: "See All Processes",
          urlPath: "/processes",
          flag: 1,
        },
        {
          color: "#8EB86F",
          icon: "fas fa-file-alt",
          asset_name: "Screens",
          asset_name_all: "See All Screens",
          urlPath: "/designer/screens",
          flag: 2,
        },
        {
          color: "#F7CF5D",
          icon: "fas fa-code",
          asset_name: "Scripts",
          asset_name_all: "See All Scripts",
          urlPath: "/designer/scripts",
          flag: 3,
        },
        {
          color: "#712F4A",
          icon: "fas fa-table",
          asset_name: "Decision Tables",
          asset_name_all: "See Decision Tables",
          urlPath: "https://carlospinell.developer.processmaker.net/decision-tables",
          flag: 4,
        },
        {
          color: "#D66A5F",
          icon: "fas fa-database",
          asset_name: "Collections",
          asset_name_all: "See All Collections",
          urlPath: "/collections",
          flag: 5,
        },
        {
          color: "#B5D3E7",
          icon: "fas fa-share-alt",
          asset_name: "Data Connectors",
          asset_name_all: "See All Data Conn",
          urlPath: "https://carlospinell.developer.processmaker.net/data-connectors",
          flag: 6,
        },
      ],
      showButtons: new Array(6).fill(false),
    };
  },
  methods: {
    openSelectTemplateModal() {
      this.showSelectTemplateModal = true;
    },
    openModal() {
      //this.showModal = true;
      //console.log("Refs: ", this.$refs);
      //this.$refs.selectTemplateModalChild.openModalProcess();
      //this.showModal = true;


      //this.$refs["create-process-modal"].show(); //OK
      //this.$refs["create-script-modal"].show();
    },
    toggleButtons(index) {
      this.$set(this.showButtons, index, !this.showButtons[index]);
      //this.showSelectTemplateModal = true;
      //this.showButtons = true;
      console.log('click en card index:', index);
    },
    handleButtonClick(action, assetName) {
      if (action === "new") {
        // Navegar a la ruta "New" correspondiente usando Vue Router
        this.$router.push({ name: "new", params: { assetName } });
      } else if (action === "seeall") {
        // Navegar a la ruta "See All" correspondiente usando Vue Router
        this.$router.push({ name: "seeall", params: { assetName } });
      } else {
        // Realizar otras acciones según sea necesario
      }
    },
    getNewAssetURL(assetName) {
      if (assetName === "Processes") {
        this.openModal();
        //window.location.href = `http://172.16.3.48:9001/${assetName}`;
        //console.log('Ruta base:',window.ProcessMaker.apiClient.get);
      }
    },
    getSeeAllAssetURL(assetName) {
      // Define la lógica para obtener la URL "See All" según el nombre del activo
      return `http://ejemplo.com/${assetName}/seeall`;
    },
  },
};
</script>

<style scoped>
.custom-button {
  color: #5e6469;
  font-family: "Open Sans", sans-serif;
  font-size: 12px;
  font-style: normal;
  font-weight: 400;
  line-height: normal;
  letter-spacing: -0.28px;
  border-radius: 4px;
  border: 1px solid #b7d8ff;
  background: #d1e3fe;
}

.custom-text {
  color: #5e6469;
  font-family: "Open Sans", sans-serif;
  font-size: 12px;
  font-style: normal;
  font-weight: 400;
  line-height: normal;
  letter-spacing: -0.28px;
}

.assets {
  background-color: #f9f9f9;
}
.card {
  border-radius: 8px;
}

.align-middle {
  vertical-align: middle;
}

/* Establece el ancho de los botones al 100% del card */
.b-card .btn {
  width: 100%;
}

.custom-text {
  color: #5e6469;
  font-family: "Open Sans", sans-serif;
  font-size: 12px;
  font-style: normal;
  font-weight: 400;
  line-height: normal;
  letter-spacing: -0.28px;
}
</style>
