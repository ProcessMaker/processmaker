<template>
  <modal
    id="launchpadSettingsModal"
    ref="my-modal-save"
    size="lg"
    class="modal-dialog modal-dialog-centered"
    :title="$t('Launchpad Settings')"
    @ok.prevent="saveModal()"
    @hidden="hideModal()"
  > 
    <div class="modal-content-custom">
      <p class="text-info-custom">
        {{ $t('Here you can personalize how your process will be shown in the process browser') }}
      </p>
      <div class="d-flex justify-content-between">
        <div class="mr-3">
          <div
            md="12"
            class="no-padding"
          >
            <label>{{ $t("Launchpad Carousel") }}</label>
            <input-image-carousel ref="image-carousel" />
          </div>
        </div>
        <div class="options-launchpad">
          <label>{{ $t("Launch Screen") }}</label>
          <div class="dropdown">
            <button
              id="statusDropdownScreen"
              class="btn dropdown-toggle dropdown-style w-100 d-flex justify-content-between align-items-center btn-custom"
              type="button"
              data-toggle="dropdown"
              data-bs-auto-close="outside"
              aria-haspopup="true"
              aria-expanded="false"
            >
              <div class="d-flex align-items-center">
                <span class="custom-text">{{ selectedScreen || $t('Select Screen') }}</span>
              </div>
            </button>
            <div
              class="dropdown-menu custom-dropdown"
              aria-labelledby="statusDropdownScreen"
            >
              <a
                v-for="(item, index) in dropdownSavedScreen"
                :key="index"
                class="dropdown-item"
                @click="selectScreenOption(item)"
              >
                {{ item.title || $t('Select Screen') }}
              </a>
            </div>
          </div>
          <label>
            {{ $t("Launchpad Icon") }}
          </label>
          <icon-dropdown ref="icon-dropdown" />
          <label>{{ $t("Chart") }}</label>
          <div class="dropdown">
            <button
              id="statusDropdown"
              class="btn dropdown-toggle dropdown-style w-100 d-flex justify-content-between align-items-center btn-custom"
              type="button"
              data-toggle="dropdown"
              data-bs-auto-close="outside"
              aria-haspopup="true"
              aria-expanded="false"
            >
              <div class="d-flex align-items-center">
                  <span class="custom-text">{{ selectedSavedChart || $t('Select Chart') }}</span>
              </div>
            </button>
            <div
              class="dropdown-menu custom-dropdown"
              aria-labelledby="statusDropdown"
            >
              <a
                v-for="(item, index) in dropdownSavedCharts"
                :key="index"
                class="dropdown-item"
                @click="selectOption(item)"
              >
                {{ item.title || $t('Select Chart') }}
              </a>
            </div>
          </div>
        </div>
      </div>
      <label>
        {{ $t("Description") }}
      </label>
      <input
        id="additional-details"
        v-model="processDescription"
        class="form-control input-custom mb-0"
        type="text"
        rows="5"
        :aria-label="$t('Description')"
      />
      <span v-if="!processDescription" class="error-message">
        {{ $t("The description field is required.") }}
        <br>
      </span>
    </div>
  </modal>
</template>

<script>
import Modal from "./Modal.vue";
import IconDropdown from "./IconDropdown.vue";
import InputImageCarousel from "./InputImageCarousel.vue";

export default {
  components: { Modal, IconDropdown, InputImageCarousel },
  props: {
    options: {
      type: Object,
      default: {
        id: "",
        type: "",
      },
    },
    filter: {
      type: String,
      default: "",
    },
    origin: {
      type: String,
      default: "",
    },
    descriptionSettings: {
      type: String,
      default: "",
    },
    process: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      imagesMedia: [],
      list: {},
      subject: "",
      description: "",
      errors: "",
      selectedSavedChart: "",
      defaultScreen:{
        id: 0,
        uuid: "",
        title: this.$t("Default Launchpad"),
      },
      defaultChart:{
        id: 0,
        title: this.$t("Default Launchpad Chart"),
      },
      defaultIcon: "Default Icon",
      dropdownSavedCharts: [],
      dropdownSavedScreen: [],
      processDescription: "",
      processDescriptionInitial: "",
      selectedLaunchpadIcon: "",
      selectedLaunchpadIconLabel: "",
      showVersionInfo: true,
      isSecondaryColor: false,
      selectedSavedChartId: "",
      selectedScreen: "",
      selectedScreenId: "",
      selectedScreenUuid: "",
      processId: "",
      mediaImageId: [],
      dataProcess: {},
      oldScreen: 0,
    };
  },
  mounted() {
    this.retrieveSavedSearchCharts();
    this.retrieveDisplayScreen();
    this.getDescriptionInitial();
    this.getProcessDescription();

    // Receives selected Option from launchpad Icons multiselect
    this.$root.$on("launchpadIcon", this.launchpadIconSelected);
  },
  methods: {
    /**
     * Get all information related to Launchpad Settings Modal
     */
    getLaunchpadSettings() {
      ProcessMaker.apiClient
        .get(`process_launchpad/${this.processId}`)
        .then((response) => {
          const firstResponse = response.data.shift();
          const unparseProperties = firstResponse?.launchpad?.properties;
          const launchpadProperties = unparseProperties ? JSON.parse(unparseProperties) : '';
          if (launchpadProperties && Object.keys(launchpadProperties).length > 0) {
            this.selectedSavedChart = launchpadProperties.saved_chart_title ?? this.defaultChart.title;
            this.selectedSavedChartId = launchpadProperties.saved_chart_id ?? this.defaultChart.id;
            this.selectedLaunchpadIcon = launchpadProperties.icon ?? this.defaultIcon;
            this.selectedLaunchpadIconLabel = launchpadProperties.icon_label ?? this.defaultIcon;
            this.selectedScreen = launchpadProperties.screen_title ?? this.defaultScreen.title;
            this.selectedScreenId = launchpadProperties.screen_id ?? this.defaultScreen.id;
            this.selectedScreenUuid = launchpadProperties.screen_uuid ?? this.defaultScreen.uuid;
            this.$refs["icon-dropdown"].setIcon(launchpadProperties.icon);
          } else {
            this.selectedSavedChart = this.defaultChart.title;
            this.selectedSavedChartId = this.defaultChart.id;
            this.selectedScreenUuid = this.defaultScreen.uuid;
            this.selectedScreen = this.defaultScreen.title;
            this.selectedScreenId = this.defaultScreen.id;
          }
          this.oldScreen = this.selectedScreenId;
          // Load media into Carousel Container
          const mediaArray = firstResponse.media;
          const embedArray = firstResponse.embed;
          mediaArray.forEach((media) => {
            this.$refs["image-carousel"].convertImageUrlToBase64(media);
          });
          embedArray.forEach((media) => {
            this.$refs["image-carousel"].addEmbedFile(media);
          });
          this.$refs["image-carousel"].setProcessId(this.processId);
        });
    },
    showModal() {
      this.subject = "";
      this.description = "";
      this.errors = "";
      this.getLaunchpadSettings();
      this.$refs["my-modal-save"].show();
    },
    /**
     * Method to set selected option to custom dropdown
     */
    selectOption(option) {
      this.selectedSavedChart = option.title;
      this.selectedSavedChartId = option.id;
    },
    /**
     * Method to set selected option to screen dropdown
     */
    selectScreenOption(option) {
      this.selectedScreen = option.title;
      this.selectedScreenId = option.id;
      this.selectedScreenUuid = option.uuid;
    },
    hideModal() {
      this.$refs["my-modal-save"].hide();
      this.cleanTabLaunchpad();
    },
    cleanTabLaunchpad() {
      this.getProcessDescription();
      this.retrieveSavedSearchCharts();
      this.retrieveDisplayScreen();
      this.showVersionInfo = true;
      this.isSecondaryColor = false;
    },
    /**
     * Save description field in Process
     */
    saveProcessDescription() {
      if (!this.processDescription) return;
      if (!this.$refs["image-carousel"].checkImages()) return;
      this.dataProcess.imagesCarousel = this.$refs["image-carousel"].getImages();
      this.dataProcess.properties = JSON.stringify({
        saved_chart_id: this.selectedSavedChartId,
        saved_chart_title: this.selectedSavedChart,
        screen_id: this.selectedScreenId,
        screen_uuid: this.selectedScreenUuid,
        screen_title: this.selectedScreen,
        icon: this.selectedLaunchpadIcon,
        icon_label: this.selectedLaunchpadIconLabel,
      });

      ProcessMaker.apiClient
        .put(`process_launchpad/${this.options.id}`, {
          imagesCarousel: this.dataProcess.imagesCarousel,
          description: this.dataProcess.description,
          properties: this.dataProcess.properties,
        })
        .then((response) => {
          ProcessMaker.alert(this.$t("The process was saved."), "success", 5, true);
          const params = {
            indexImage: null,
            type: "add",
          };
          if (this.oldScreen !== this.selectedScreenId) {
            ProcessMaker.EventBus.$emit("reloadByNewScreen", this.selectedScreenId);
          }
          ProcessMaker.EventBus.$emit("getLaunchpadImagesEvent", params);
          ProcessMaker.EventBus.$emit("getChartId", this.selectedSavedChartId);
          this.hideModal();
        })
        .catch((error) => {
          console.error("Error: ", error);
        });
    },
    saveModal() {
      this.dataProcess = this.process;
      // if method is not called from ProcessMaker core
      if (this.origin !== "core") {
        this.dataProcess = ProcessMaker.modeler.process;
      }
      this.dataProcess.description = this.processDescription;
      this.saveProcessDescription();
    },
    /**
     * Initial method to retrieve Saved Search Charts and populate dropdown
     * Package Collections and Package SavedSearch always go together
     */
    retrieveSavedSearchCharts() {
      if (!ProcessMaker.packages.includes("package-collections")) return;
      ProcessMaker.apiClient
        .get(
          "saved-searches?has=charts&include=charts&per_page=100&filter=&get=id,title,charts.id,charts.title,charts.saved_search_id,type",
        )
        .then((response) => {
          if (response.data.data[0].charts) {
            const resultArray = response.data.data.flatMap((item) => {
              if (item.charts && Array.isArray(item.charts)) {
                return item.charts.map((chartItem) => ({
                  id: chartItem.id,
                  title: chartItem.title,
                }));
              }
              return [];
            });

            this.dropdownSavedCharts = [this.defaultChart].concat(resultArray);;
            this.selectOption(this.defaultChart);
          }
        })
        .catch((error) => {
          this.dropdownSavedCharts = [];
        });
    },
    /**
     * Initial method to retrieve Screens and populate dropdown
     */
     retrieveDisplayScreen() {
      ProcessMaker.apiClient
        .get(
          "screens?page=1&per_page=10&filter=&order_by=title&order_direction=asc&include=categories,category&exclude=config&type=DISPLAY",
        )
        .then((response) => {
          if (response.data.data) {
            const resultArray = response.data.data.flatMap((item) => {
              return {
                id: item.id,
                title: item.title,
                uuid: item.uuid,
              }
            });
            this.dropdownSavedScreen = [this.defaultScreen].concat(resultArray);
            this.selectScreenOption(this.defaultScreen);
          }
        })
        .catch((error) => {
          this.dropdownSavedScreen = [];
        });
    },
    /**
     * Method to store initial data from process description field
     */
    getDescriptionInitial() {
      if (this.origin !== "core") {
        if (ProcessMaker.modeler?.process) {
          this.processDescriptionInitial = ProcessMaker.modeler.process.description;
        }
      } else {
        this.processDescriptionInitial = this.descriptionSettings;
      }
    },
    /**
     * Method to retrieve data from process description field
     */
    getProcessDescription() {
      if (this.origin !== "core") {
        if (ProcessMaker.modeler?.process) {
          this.processDescription = ProcessMaker.modeler.process.description;
          this.processId = ProcessMaker.modeler.process.id;
          if(ProcessMaker.modeler.process.description === "") {
            this.processDescription = this.processDescriptionInitial;
          }
        } 
      } else {
        this.processDescription = this.descriptionSettings;
        this.processId = this.process.id;
          if(!this.processDescription) {
            this.processDescription = this.processDescriptionInitial;
          }
      }
    },
    launchpadIconSelected(iconData) {
      this.selectedLaunchpadIcon = iconData.value;
      this.selectedLaunchpadIconLabel = iconData.label;
    },
  },
};
</script>

<style lang="css" scoped>
label {
  color: #556271;
  margin-top: 16px;
  margin-bottom: 4px;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 22px;
  letter-spacing: 0px;
  text-align: left;
}
.image-style {
  width: 80px;
  height: 80px;
  border-radius: 4px;
}
#launchpadSettingsModal .modal-title div {
  color: #556271;
  font-family: 'Open Sans', sans-serif;
  font-size: 21px;
  font-weight: 400;
  line-height: 29px;
  letter-spacing: -0.02em;
  text-align: left;
}
#launchpadSettingsModal .modal-header {
  align-items: center;
}
#launchpadSettingsModal .modal-footer {
  margin-top: 0px;
  padding: 20px 24px;
}
#launchpadSettingsModal .modal-footer .btn-outline-secondary {
  color: #556271;
  background-color: white;
  margin: 0px;
  width: 90px;
  height: 40px;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 600;
  line-height: 24px;
  letter-spacing: -0.02em;
  text-align: center;
  padding: 0px 15px;
  border-radius: 4px;
  gap: 6px;
  border: 1px solid #6A7888;
}
#launchpadSettingsModal .modal-footer .btn-secondary {
  color: white;
  background-color: #6A7888;
  width: 99px;
  height: 40px;
  margin: 0px;
  margin-left: 7px;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 600;
  line-height: 24px;
  letter-spacing: -0.02em;
  text-align: center;
  padding: 0px 15px;
  border-radius: 4px;
  gap: 6px;
}
.text-info-custom {
  color: #556271;
  margin-bottom: 17px;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 22px;
  letter-spacing: 0px;
  text-align: left;
}
.custom-row {
  margin: 16px 0px;
}
.input-custom {
  height: 40px;
  margin-bottom: 16px;
  padding: 0px, 12px, 0px, 12px;
  border-radius: 4px;
  gap: 6px;
  border: 1px solid #CDDDEE;
}
.image-thumbnails-container {
  border: 1px solid #CDDDEE;
  width: 369px;
  height: 204px;
  border-radius: 4px;
  gap: 10px;
  padding: 12px;
}
.images-info {
  display: flex;
  justify-content: center;
  align-items: center;
}
.images-container {
  display: flex;
  width: 345px;
  height: 128px;
  margin-bottom: 12px;
}
#launchpadSettingsModal .drag-and-drop-container {
  font-family: 'Open Sans', sans-serif;
  font-size: 14px;
  font-weight: 400;
  line-height: 19.07px;
  letter-spacing: -0.02em;
  text-align: center;
  color: #6a7888;
  margin-bottom: 9px
}
#launchpadSettingsModal .drag-and-drop-container i {
  font-size: 32px;
}
#launchpadSettingsModal .modal-dialog, .modal-content {
  min-width: 800px;
}
.options-launchpad {
  width: 285px;
}
.input-file-custom {
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6a7888;
  width: 344px;
  height: 40px;
  padding: 10px 0px;
  background-color: #ebeef2;
  border: 1px dashed #6a7888;
  border-radius: 4px;
  font-family: 'Open Sans', sans-serif;
  font-size: 15px;
  font-weight: 400;
  line-height: 20.43px;
  letter-spacing: -0.02em;
  text-align: center;
}
#launchpadSettingsModal .modal-content-custom {
  padding: 11px 8px 0px 8px;
}
#launchpadSettingsModal b-row, b-col {
  margin: 0px;
  padding: 0px;
}
.delete-icon {
  cursor: pointer;
  position: absolute;
  display: flex;
  justify-content: center;
  align-items: center;
  width: 80px;
  height: 80px;
  border-radius: 4px;
  background-color: #00000080;
  
}
.delete-icon i {
  font-size: 24px;
  color: white;
}
.btns-popover {
  height: 32px;
  padding: 0px 14px;
  border-radius: 4px;
  border: 0px;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 600;
  line-height: 24px;
  letter-spacing: -0.02em;
  text-align: left;
  margin-left: 11px;
}
.btn-delete-image {
  color: white;
  background-color: #6a7888;
}
.btn-delete-embed {
  color: white;
  background-color: #ed4858;
}
.btn-cancel-delete {
  color: #556271;
  background-color: #d8e0e9;
}
.text-delete-embed {
  color: #556271;
  font-family: 'Open Sans', sans-serif;
  font-size: 15px;
  font-weight: 700;
  line-height: 27px;
  letter-spacing: -0.02em;
  text-align: left;
}
.popover {
  max-width: 474px;
}
.popover-custom {
  display: flex;
  align-items: center;
  color: #556271;
  padding: 16px;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 21.79px;
  letter-spacing: -0.02em;
}
.square-image {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 8px;
  font-size: 24px;
  color: #6a7888;
  background-color: #f6f9fb;
  border: 1px solid #CDDDEE;
}
.custom-trash-icon {
  color: #6a7888;
  font-size: 24px;
}
#idDropdownMenuUpload .dropdown-toggle::after {
    display:none;
}
#idDropdownMenuUpload .dropdown-menu.show {
  width: 229px;
  padding: 0px;
}
#launchpadSettingsModal .dropdown-item {
  color: #556271;
  padding: 12px;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 21.79px;
  letter-spacing: -0.02em;
  text-align: left;
}
.popover-embed {
  padding: 21px;
  width: 474px;
}
.dropdown-style {
  padding: 9px 12px;
  color: #556271;
  border-radius: 4px;
  border: 1px solid #CDDDEE;
}
#launchpadSettingsModal .dropdown-menu.show {
  width: 285px;
  padding: 0px;
}
.custom-text {
  width: 239px;
  overflow: hidden;
  text-align: left;
  text-overflow: ellipsis;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 21.79px;
  letter-spacing: -0.02em;
}
</style>
