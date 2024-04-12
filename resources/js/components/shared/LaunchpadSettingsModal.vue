<template>
  <modal
    id="launchpadSettingsModal"
    ref="my-modal-save"
    size="lg"
    class="modal-dialog modal-dialog-centered"
    :title="$t('Launchpad Settings')"
    :set-custom-buttons="true"
    :custom-buttons="customModalButtons"
    @saveModal="saveModal"
    @closeModal="closeModal"
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
          <div class="multiselect-screen custom-multiselect">
            <b-input-group>
              <multiselect
                v-model="selectedScreen"
                :placeholder="$t('Type to search Screen')"
                :options="dropdownSavedScreen"
                :multiple="false"
                track-by="id"
                label="title"
                :show-labels="false"
                :searchable="true"
                :internal-search="true"
                :allow-empty="false"
                @open="retrieveDisplayScreen"
                @search-change="retrieveDisplayScreen"
              >
                <template slot="noResult">
                  {{ $t("No elements found. Consider changing the search query.") }}
                </template>
                <template slot="noOptions">
                  {{ $t("No Data Available") }}
                </template>
              </multiselect>
            </b-input-group>
          </div>
          <label>
            {{ $t("Launchpad Icon") }}
          </label>
          <icon-dropdown ref="icon-dropdown" />
          <label>{{ $t("Chart") }}</label>
          <div class="multiselect-chart custom-multiselect">
            <b-input-group>
              <multiselect
                v-model="selectedSavedChart"
                :placeholder="$t('Type to search Chart')"
                :options="dropdownSavedCharts"
                :multiple="false"
                track-by="id"
                label="title"
                :show-labels="false"
                :searchable="true"
                :internal-search="true"
                :allow-empty="false"
                @open="retrieveSavedSearchCharts"
                @search-change="retrieveSavedSearchCharts"
              >
                <template slot="noResult">
                  {{ $t("No elements found. Consider changing the search query.") }}
                </template>
                <template slot="noOptions">
                  {{ $t("No Data Available") }}
                </template>
              </multiselect>
            </b-input-group>
          </div>
        </div>
      </div>
      <label>
        {{ $t("Description") }}
      </label>
      <textarea
        id="additional-details"
        v-model="processDescription"
        class="form-control input-custom mb-0"
        type="text"
        rows="5"
        :aria-label="$t('Description')"
        disabled
      />
    </div>
    <template #modal-footer>
      <b-button
        variant="outline-secondary"
        @click="hideModal"
      >
        Cancel 2
      </b-button>
      <b-button
        variant="secondary"
        @click="saveModal"
      >
        Save 1
      </b-button>
    </template>
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
      selectedSavedChart: null,
      defaultScreen: {
        id: 0,
        uuid: "",
        title: this.$t("Default Launchpad"),
      },
      defaultChart: {
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
      selectedScreen: null,
      processId: "",
      mediaImageId: [],
      dataProcess: {},
      oldScreen: 0,
      customModalButtons: [
        {
          content: "Cancel",
          action: "closeModal",
          variant: "outline-secondary",
          dataTest: "launchpad-modal-btn-cancel",
          disabled: false,
        },
        {
          content: "Save",
          action: "saveModal",
          variant: "secondary",
          dataTest: "launchpad-modal-btn-ok",
          disabled: false,
        },
      ],
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
          const launchpadProperties = unparseProperties ? JSON.parse(unparseProperties) : "";
          if (launchpadProperties && Object.keys(launchpadProperties).length > 0) {
            this.selectedSavedChart = {
              id: launchpadProperties.saved_chart_id ?? this.defaultChart.id,
              title: launchpadProperties.saved_chart_title ?? this.defaultChart.title,
            };
            this.selectedLaunchpadIcon = launchpadProperties.icon ?? this.defaultIcon;
            this.selectedLaunchpadIconLabel = launchpadProperties.icon_label ?? this.defaultIcon;
            this.selectedScreen = {
              id: launchpadProperties.screen_id ?? this.defaultScreen.id,
              uuid: launchpadProperties.screen_uuid ?? this.defaultScreen.uuid,
              title: launchpadProperties.screen_title ?? this.defaultScreen.title,
            };
            this.$refs["icon-dropdown"].setIcon(launchpadProperties.icon);
          } else {
            this.selectedSavedChart = {
              id: this.defaultChart.id,
              title: this.defaultChart.title,
            };
            this.selectedScreen = {
              id: this.defaultScreen.id,
              uuid: this.defaultScreen.uuid,
              title: this.defaultScreen.title,
            };
          }
          this.oldScreen = this.selectedScreen.id;
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
    closeModal() {
      this.$bvModal.hide("launchpadSettingsModal");
      this.errors = "";
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
      if (!this.$refs["image-carousel"].checkImages()) return;
      this.dataProcess.imagesCarousel = this.$refs["image-carousel"].getImages();
      this.dataProcess.properties = JSON.stringify({
        saved_chart_id: this.selectedSavedChart.id,
        saved_chart_title: this.selectedSavedChart.title,
        screen_id: this.selectedScreen.id,
        screen_uuid: this.selectedScreen.uuid,
        screen_title: this.selectedScreen.title,
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
          ProcessMaker.alert(this.$t("The launchpad settings were saved."), "success", 5, true);
          const params = {
            indexImage: null,
            type: "add",
          };
          if (this.oldScreen !== this.selectedScreen.id) {
            ProcessMaker.EventBus.$emit("reloadByNewScreen", this.selectedScreenId);
          }
          ProcessMaker.EventBus.$emit("getLaunchpadImagesEvent", params);
          ProcessMaker.EventBus.$emit("getChartId", this.selectedSavedChart.id);
          this.customModalButtons[1].disabled = false;
          this.hideModal();
        })
        .catch((error) => {
          console.error("Error: ", error);
        });
    },
    saveModal() {
      this.customModalButtons[1].disabled = true;
      this.dataProcess = this.process;
      // if method is not called from ProcessMaker core
      if (this.origin !== "core") {
        this.dataProcess = ProcessMaker.modeler.process;
      }
      this.saveProcessDescription();
    },
    /**
     * Initial method to retrieve Saved Search Charts and populate dropdown
     * Package Collections and Package SavedSearch always go together
     */
    retrieveSavedSearchCharts(query = "") {
      if (!ProcessMaker.packages.includes("package-collections")) return;
      const filter = (query === "" || query === null) ? "" : `&filter=${query}`;
      ProcessMaker.apiClient
        .get("saved-searches?page=1&per_page=10&order_by=title&order_direction=asc"
          + "&has=charts&include=charts&get=id,title,charts.id,charts.title,charts.saved_search_id,type"
          + `${filter}`)
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

            this.dropdownSavedCharts = [this.defaultChart].concat(resultArray);
          }
        })
        .catch((error) => {
          this.dropdownSavedCharts = [];
        });
    },
    /**
     * Initial method to retrieve Screens and populate dropdown
     */
    retrieveDisplayScreen(query = "") {
      const filter = (query === "" || query === null) ? "" : `&filter=${query}`;
      ProcessMaker.apiClient
        .get(
          `screens?page=1&per_page=10&order_by=title&order_direction=asc&include=categories,category&exclude=config&type=DISPLAY${
            filter}`,
        )
        .then((response) => {
          if (response.data.data) {
            const resultArray = response.data.data.flatMap((item) => ({
              id: item.id,
              title: item.title,
              uuid: item.uuid,
            }));
            this.dropdownSavedScreen = [this.defaultScreen].concat(resultArray);
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
          if (ProcessMaker.modeler.process.description === "") {
            this.processDescription = this.processDescriptionInitial;
          }
        }
      } else {
        this.processDescription = this.descriptionSettings;
        this.processId = this.process.id;
        if (!this.processDescription) {
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
.modal-title div {
  color: #556271;
  font-family: 'Open Sans', sans-serif;
  font-size: 21px;
  font-weight: 400;
  line-height: 29px;
  letter-spacing: -0.02em;
  text-align: left;
}
.modal-header {
  align-items: center;
}
.modal-footer {
  margin-top: 0px;
  padding: 20px 24px;
}
.modal-footer .btn-outline-secondary {
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
  border: 1px solid #6a7888;
}
.modal-footer .btn-secondary {
  color: white;
  background-color: #6a7888;
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
  color: #556271;
  background: white;
  border-radius: 4px;
  border: 1px solid #cdddee;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 21.79px;
  letter-spacing: -0.02em;
  gap: 6px;
}
.modal-dialog, .modal-content {
  min-width: 800px;
}
.options-launchpad {
  width: 350px;
}
.modal-content-custom {
  padding: 11px 8px 0px 8px;
}
</style>

<style lang="scss">
.multiselect-chart.custom-multiselect,
.multiselect-screen.custom-multiselect {
  .input-group {
    width: 100%;
  }
  .multiselect,
  .multiselect__tags {
    height: 40px;
    min-height: 40px;
    max-height: 40px;
    border-radius: 4px;
    border-color: #cdddee;
  }
  .multiselect__tags {
    padding: 9px 12px;
  }
  .multiselect__single {
    width: 239px;
    height: 20px;
    overflow: hidden;
    text-align: left;
    text-overflow: ellipsis;
    font-family: 'Open Sans', sans-serif;
    font-size: 16px;
    font-weight: 400;
    line-height: 21.79px;
    letter-spacing: -0.02em;
    color: #556271;
    padding-left: 0px;
  }
  .multiselect__select:before {
    border-width: 4px 4px 0 4px;
    border-color: #556271 transparent;
  }
  .multiselect__option--selected {
    font-weight: 400;
    background: white;
    color: #556271;
  }
  .multiselect__input::placeholder {
    color: #ebeef2;
    color: #556271;
  }
  .multiselect__input {
    max-width: 239px;
    height: 20px;
    font-family: 'Open Sans', sans-serif;
    font-size: 16px;
    font-weight: 400;
    line-height: 21.79px;
    letter-spacing: -0.02em;
    color: #556271;
    padding-left: 0px;
  }
  .multiselect__option--highlight {
    background: #ebeef2;
    color: #556271;
  }
  .multiselect__content-wrapper {
    max-height: 200px !important;
    position: static;
  }

  .multiselect__option {
    color: #556271;
    padding: 12px;
    font-family: 'Open Sans', sans-serif;
    font-size: 16px;
    font-weight: 400;
    line-height: 21.79px;
    letter-spacing: -0.02em;
    text-align: left;
  }
}
</style>
