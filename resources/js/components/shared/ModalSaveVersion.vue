<template>
  <modal
    ref="my-modal-save"
    size="lg"
    class="modal-dialog modal-dialog-centered"
    :title="$t('Publish New Version')"
    @ok.prevent="saveModal()"
    @hidden="hideModal()"
  >
    <div class="form-group">
      <p>{{ $t("Once published, all new requests will use the new process model.") }}</p>
      <div>
        <b-card no-body>
          <b-tabs card>
            <button
              type="button"
              class="btn btn-custom-button btn-sm position-absolute modeler-save-button custom-button"
              :style="btnStyle"
              @click="swapLabel"
            >
              <i class="fas fa-book mr-1" />
              {{ labelButton }}
            </button>
            <b-tab :title="labelTab">
              <b-card v-show="!showVersionInfo">
                <label for="name">{{ $t("Version Name") }} </label>
                <input
                  id="name"
                  v-model="subject"
                  class="form-control mt-2"
                  type="text"
                  name="name"
                >
                <div
                  v-if="errors.subject"
                  class="invalid-feedback d-block"
                  role="alert"
                >
                  {{ errors.subject[0] }}
                </div>
                <label
                  class="mt-2"
                  for="additional-details"
                >
                  {{ $t("Description") }}
                </label>
                <textarea
                  id="additional-details"
                  v-model="description"
                  class="form-control mt-2"
                  type="text"
                  rows="8"
                  :aria-label="$t('Description')"
                />
              </b-card>
            </b-tab>
          </b-tabs>
        </b-card>
      </div>
    </div>
  </modal>
</template>

<script>
import Modal from "./Modal.vue";
import IconDropdown from "./IconDropdown.vue";

export default {
  components: { Modal, IconDropdown },
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
      images: [],
      imagesMedia: [],
      showDeleteIcons: Array(4).fill(false),
      focusIcons: Array(4).fill(false),
      list: {},
      subject: "",
      description: "",
      errors: "",
      types: {
        Screen: "save-screen",
        Script: "save-script",
        Process: "modeler-save",
      },
      redirectUrl: null,
      nodeId: null,
      selectedSavedChart: "",
      dropdownSavedCharts: [],
      maxImages: 4,
      processDescription: "",
      processDescriptionInitial: "",
      selectedLaunchpadIcon: "",
      selectedLaunchpadIconLabel: "",
      showVersionInfo: true,
      labelButton: "Version Info",
      labelTab: "Launchpad Settings",
      btnColorClass: "btn-custom-button",
      isSecondaryColor: false,
      selectedSavedChartId: "",
      processId: "",
      mediaImageId: [],
      dataProcess: {},
    };
  },
  computed: {
    btnStyle() {
      return this.isSecondaryColor
        ? { backgroundColor: "#6a7888", color: "white" }
        : { backgroundColor: "white", color: "#6a7888" };
    },
  },
  mounted() {
    ProcessMaker.EventBus.$on("open-modal-versions", (redirectUrl, nodeId) => {
      this.redirectUrl = redirectUrl;
      this.nodeId = nodeId;
      this.showModal();
    });
    this.getDescriptionInitial();
    this.getProcessDescription();

    // Receives selected Option from launchpad Icons multiselect
    this.$root.$on("launchpadIcon", this.launchpadIconSelected);
  },
  methods: {
    swapLabel() {
      const tempLabel = this.labelButton;
      this.showVersionInfo = !this.showVersionInfo;
      this.labelButton = this.labelTab;
      this.labelTab = tempLabel;

      this.isSecondaryColor = !this.isSecondaryColor;
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
    hideModal() {
      this.$refs["my-modal-save"].hide();
      this.cleanTabLaunchpad();
    },
    cleanTabLaunchpad() {
      this.getProcessDescription();
      this.images = [];
      this.labelTab = "Launchpad Settings";
      this.labelButton = "Version Info";
      this.showVersionInfo = true;
      this.isSecondaryColor = false;
    },
    saveModal() {
      this.dataProcess = ProcessMaker.modeler.process;
      this.dataProcess.description = this.processDescription;
      const promise = new Promise((resolve, reject) => {
        // emit save types
        window.ProcessMaker.EventBus.$emit(
          this.types[this.options.type],
          this.redirectUrl,
          this.nodeId,
          this.options.type === "Screen" ? (false, resolve) : resolve,
          reject,
          this.types[this.options.type] === "modeler-save" ? false : null,
        );
      });

      promise
        .then((response) => {
          ProcessMaker.apiClient
            .post("/version_histories", {
              subject: this.subject,
              description: this.description,
              versionable_id: this.options.id,
              versionable_type: this.options.type,
            })
            .catch((error) => {
              if (error.response.status && error.response.status === 422) {
                this.errors = error.response.data.errors;
              }
              if (error.response.status === 404) {
                // version_histories route not found because package-versions is not installed
                console.error(err);
              }
            });
        })
        .catch((err) => {
          console.error(err);
        });

      // Save only process description field using Process API
      this.saveProcessDescription();
    },
    /**
     * Save description field in Process
     */
    saveProcessDescription() {
      if (!this.processDescription) return;
      this.dataProcess.imagesCarousel = this.images;
      this.dataProcess.launchpad_properties = JSON.stringify({
        saved_chart_id: this.selectedSavedChartId,
        saved_chart_title: this.selectedSavedChart,
        icon: this.selectedLaunchpadIcon,
        icon_label: this.selectedLaunchpadIconLabel,
      });

      ProcessMaker.apiClient
        .put(`processes/${this.options.id}`, {
          imagesCarousel: this.dataProcess.imagesCarousel,
          description: this.dataProcess.description,
          launchpad_properties: this.dataProcess.launchpad_properties,
        })
        .then((response) => {
          ProcessMaker.alert(this.$t("The process was saved."), "success", 5, true);
          const params = {
            indexImage: null,
            type: "add",
          };
          ProcessMaker.EventBus.$emit("getLaunchpadImagesEvent", params);
          ProcessMaker.EventBus.$emit("getChartId");
          this.hideModal();
        })
        .catch((error) => {
          console.error("Error: ", error);
        });
    },
    showModal() {
      this.subject = "";
      this.description = "";
      this.errors = "";
      this.images = [];
      this.$refs["my-modal-save"].show();
    },
    /**
     * Method to set selected option to custom dropdown
     */
    selectOption(option) {
      this.selectedSavedChart = option.title;
      this.selectedSavedChartId = option.id;
    },
  },
};
</script>

<style lang="scss">
$iconSize: 29px;
$multiselect-height: 38px;
.no-padding {
  padding: 0;
}

.expanded .dropdown {
  display: none;
}

.btn-custom {
  text-transform: none;
  border-color: #6C757D;
}

.btn-custom-button {
  text-transform: none;
  border-color: rgba(35, 118, 200, 0.33);
  background-color: white;
  color: #212529;
}

.text-black {
  color: #212529;
}

.cursor-default {
  cursor: default;
}

.toolbar-item {
  font-style: normal;
  font-size: 14px;
  letter-spacing: -0.02em;
  line-height: 21px;
}

.custom-button {
  right: 10px;
  top: 20px;
}

.custom-color {
  color: #6a7888;
}

.custom-dropdown {
  width: 100%;
  max-height: 200px;
  overflow-y: auto;
  transform: none;
}

.error-message {
  color: red;
  font-size: 0.8rem;
  margin-top: 5px;
}

.custom-text {
  font-size: 16px;
}
</style>
