<template>
  <modal
    ref="my-modal-save"
    size="lg"
    class="modal-dialog modal-dialog-centered"
    :title="$t('Publish New Version')"
    :hide-footer="true"
    @ok.prevent="saveModal()"
    @hidden="hideModal()"
  >
    <template
      v-if="isABTestingInstalled && alternative?.version_b_enabled"
    >
      <keep-alive>
        <component
          :is="publishVersionComponent"
          :alternative="alternative"
          @ab-publish-version="abPublishVersion"
          @ab-cancel="hideModal()"
        />
      </keep-alive>
    </template>
    <div
      v-else
      class="form-group"
    >
      <p>{{ $t("Once published, all new requests will use the new process model.") }}</p>
      <div>
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
      </div>

      <hr class="long-hr mt-4 mb-4">

      <div class="d-flex justify-content-end pv-actions">
        <button class="btn btn-outline-secondary text-uppercase mr-3" @click.prevent="hideModal()">
          Cancel
        </button>
        <button
          class="btn btn-secondary text-uppercase"
          data-test="btn-save-publish"
          @click.prevent="abPublishVersion"
        >
          Save and Publish
        </button>
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
      mediaImageId: [],
      dataProcess: {},
      // AB Testing
      isABTestingInstalled: false,
      publishVersionComponent: null,
      alternative: null,
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

      // AB Testing installed
      this.isABTestingInstalled = ProcessMaker.modeler.abPublish;
      // AB Testing component
      if (this.isABTestingInstalled && ProcessMaker?.AbTesting?.PublishVersion) {
        this.alternative = ProcessMaker?.modeler?.process?.alternative_info;
        this.publishVersionComponent = ProcessMaker?.AbTesting?.PublishVersion;
      }
    });
    this.retrieveSavedSearchCharts();
    this.getDescriptionInitial();
    this.getProcessDescription();

    // Receives selected Option from launchpad Icons multiselect
    this.$root.$on("launchpadIcon", this.launchpadIconSelected);
  },
  methods: {
    abPublishVersion(alternativeData) {
      this.subject = alternativeData.subject;
      this.description = alternativeData.description;

      this.saveModal(alternativeData);
    },
    /**
     * Get all information related to Launchpad Settings Modal
     */
    getLaunchpadSettings() {
      this.images = [];
      ProcessMaker.apiClient
        .get(`processes/${this.processId}/media`)
        .then((response) => {
          const firstResponse = response.data.data.shift();
          const launchpadProperties = JSON.parse(
            firstResponse?.launchpad_properties,
          );
          if (launchpadProperties && Object.keys(launchpadProperties).length > 0) {
            this.selectedSavedChart = launchpadProperties.saved_chart_title
              ? launchpadProperties.saved_chart_title
              : "";
            this.selectedSavedChartId = launchpadProperties.saved_chart_id;
            this.selectedLaunchpadIcon = launchpadProperties.icon;
            this.selectedLaunchpadIconLabel = launchpadProperties.icon_label;
            this.$refs["icon-dropdown"].setIcon(launchpadProperties.icon);
          } else {
            this.selectedSavedChart = "";
            this.selectedSavedChartId = "";
          }
          // Load Images into Carousel Container
          const mediaArray = firstResponse.media;
          mediaArray.forEach((media) => {
            this.convertImageUrlToBase64(media);
          });
        });
    },
    /**
     * Converts Image from URL to Base64
     */
    convertImageUrlToBase64(media) {
      fetch(media.original_url)
        .then((response) => response.blob())
        .then((blob) => {
          const reader = new FileReader();
          reader.onloadend = () => {
            const base64Data = reader.result;
            this.images.push({ url: base64Data, uuid: media.uuid });
          };
          reader.readAsDataURL(blob);
        })
        .catch((error) => {
          console.error("Error loading image:", error);
        });
    },
    swapLabel() {
      const tempLabel = this.labelButton;
      this.showVersionInfo = !this.showVersionInfo;
      this.labelButton = this.labelTab;
      this.labelTab = tempLabel;

      this.isSecondaryColor = !this.isSecondaryColor;
    },
    launchpadIconSelected(iconData) {
      this.selectedLaunchpadIcon = iconData.value;
      this.selectedLaunchpadIconLabel = iconData.label;
    },
    /**
     * Method that allows drag elements to the container
     */
    handleDragOver(event) {
      event.preventDefault();
    },
    /**
     * Generic Method to manage drag and drop and selected images
     */
    handleImages(files) {
      this.validateImageExtension(files);
    },
    /**
     * This method handles dragged image files and adds each image to list
     */
    handleDrop(event) {
      event.preventDefault();

      // Checks if event has 'dataTransfer' property
      if (event.dataTransfer) {
        const { files } = event.dataTransfer;

        // Checks if 'dataTransfer' has 'files' property
        if (files && files.length > 0) {
          if (this.images.length + files.length > this.maxImages) {
            ProcessMaker.alert(
              this.$t("It is not possible to include more than four images."),
              "danger",
            );
            return;
          }
          this.validateImageExtension(files);
        }
      }
    },
    /**
     *  Validates images with png and jpg extensions.
     */
    validateImageExtension(files) {
      Array.from(files).forEach((file) => {
        if (this.images.length < this.maxImages) {
          if (this.isValidFileExtension(file.name)) {
            const reader = new FileReader();
            reader.onload = (event) => {
              this.images.push({
                file,
                url: event.target.result,
                uuid: "",
              });
              this.showDeleteIcons.push(false);
            };
            reader.readAsDataURL(file);
          } else {
            ProcessMaker.alert(
              this.$t("Only PNG and JPG extensions are allowed."),
              "danger",
            );
          }
        }
      });
    },
    /**
     * Adds an image from drag and drop to image container
     */
    handleDroppedImage(event) {
      const { files } = event.dataTransfer;
      this.handleImages(files);
    },
    /**
     * Adds index info to dragged object
     */
    handleDragStart(event, index) {
      event.dataTransfer.setData("text/plain", index);
      event.preventDefault();
    },
    /**
     * Validate image extensions
     */
    isValidFileExtension(fileName) {
      const allowedExtensions = [".jpg", ".jpeg", ".png"];
      return allowedExtensions.includes(
        fileName.slice(fileName.lastIndexOf(".")).toLowerCase(),
      );
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

            this.dropdownSavedCharts = resultArray;
            this.selectedSavedChart = "";
          }
        })
        .catch((error) => {
          this.dropdownSavedCharts = [];
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
    /**
     * Method to open a screen for image selection from hard drive
     */
    openFileInput() {
      this.$refs.fileInput.click();
    },
    /**
     * Method to add image files to thumbnails container
     */
    handleImageUpload(event) {
      if (this.images.length >= this.maxImages) {
        // The amount of images allowed was reached.
        ProcessMaker.alert(
          this.$t("It is not possible to include more than four images."),
          "danger",
        );
        this.$refs.fileInput.value = "";
        return;
      }
      const { files } = event.target;
      this.handleImages(files);
      event.target.value = "";
    },
    /**
     * Method to show trash image
     */
    showDeleteIcon(index) {
      return this.$set(this.showDeleteIcons, index, true);
    },
    /**
     * Method to hide trash image
     */
    hideDeleteIcon(index) {
      return this.$set(this.showDeleteIcons, index, false);
    },
    /**
     * Method to focus trash image
     */
    focusIcon(index) {
      this.focusIcons = Array(4).fill(false);
      this.$set(this.focusIcons, index, true);
    },
    /**
     * Method to unfocus trash image
     */
    unfocusIcon(index) {
      this.$set(this.focusIcons, index, false);
    },
    /**
     * Method to delete image from carousel container
     */
    deleteImage(index) {
      const { uuid } = this.images[index];
      this.images.splice(index, 1);
      this.$set(this.showDeleteIcons, index, false);
      this.$set(this.focusIcons, index, false);

      // Call API to delete
      ProcessMaker.apiClient
        .delete(`processes/${this.processId}/media`, {
          data: { uuid },
        })
        .then((response) => {
          ProcessMaker.alert(this.$t("The image was deleted"), "success");
        })
        .catch((error) => {
          console.error("Error", error);
        });
      const params = {
        indexImage: index,
        type: "delete",
      };
      ProcessMaker.EventBus.$emit("getLaunchpadImagesEvent", params);
    },
    hideModal() {
      this.$refs["my-modal-save"].hide();
      this.cleanTabLaunchpad();
    },
    cleanTabLaunchpad() {
      this.getProcessDescription();
      this.images = [];
      this.retrieveSavedSearchCharts();
      this.labelTab = "Launchpad Settings";
      this.labelButton = "Version Info";
      this.showVersionInfo = true;
      this.isSecondaryColor = false;
    },
    /**
     * Method to save modal
     * @param alternativeData {
     * publishedVersion: string A|B|AB,
     * subject: string,
     * description: string,
     * } | null - Alternative data from AB Testing
     *
     * @returns {Promise<void>}
     */
    saveModal(alternativeData = null) {
      const eventType = this.types[this.options.type];

      let publishedVersion = 'A';

      if (eventType === "modeler-save" && alternativeData) {
        publishedVersion = alternativeData.publishedVersion;
      }

      // if method is called from ProcessMaker core
      if (this.origin === "core") {
        this.saveFromEditLaunchpad();
        this.dataProcess = this.process;
        this.dataProcess.description = this.processDescription;
        this.saveProcessDescription(publishedVersion);
        return;
      }
      this.dataProcess = ProcessMaker.modeler.process;
      this.dataProcess.description = this.processDescription;

      const promise = new Promise((resolve, reject) => {
        // emit save types
        window.ProcessMaker.EventBus.$emit(
          eventType,
          this.redirectUrl,
          this.nodeId,
          this.options.type === "Screen" ? (false, resolve) : resolve,
          reject,
          eventType === "modeler-save" ? false : null,
          "",
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
      this.saveProcessDescription(publishedVersion);
    },
    /**
     * Save description field in Process
     *
     * @param {string} publishedVersion
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
     * Method to store version info from Launchpad Window
     */
    saveFromEditLaunchpad() {
      if (!this.processDescription) {
        ProcessMaker.alert(this.$t("The Description field is required."), "danger");
        return;
      }
      ProcessMaker.apiClient
        .post("/version_histories", {
          subject: this.subject,
          description: this.description,
          versionable_id: this.options.id,
          versionable_type: this.options.type,
        })
        .then((response) => {
          ProcessMaker.alert(this.$t("The version was saved."), "success");
        })
        .catch((error) => {
          if (error.response.status && error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
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

.dropdown-toggle {
  font-size: 14px;
  padding: 4px 10px;
}

.dropdown-item {
  font-size: 12px;
}

.dropdown-style {
  background-color: white;
  color: black;
}

.image-thumbnails {
  max-height: 300px;
  overflow-y: auto;
}

.thumbnail {
  margin-bottom: 10px;
}

.image-thumbnails-container {
  border: 1px solid #ccc;
  padding: 5px;
  max-height: 300px;
  overflow-y: auto;
  height: 100vh;
}

.delete-icon {
  position: absolute;
}

.delete-icon i {
  font-size: 18px;
  color: darkgray;
}

.btn-custom {
  text-transform: none;
  border-color: #6C757D;
}

.drag-and-drop-container {
  border: 1px dashed #ccc;
  padding: 20px;
  border-radius: 5px;
  margin-top: 20px;
  color: #2381c8;
}

.drag-and-drop-container i {
  font-size: 36px;
  margin-bottom: 10px;
  color: rgba(35, 118, 200, 0.33);
}

.drag-and-drop-container div {
  margin-bottom: 10px;
  color: #2381c8;
}

.drag-and-drop-container b-button {
  background-color: #2381c8;
  text-transform: none;
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

.long-hr {
  margin: auto -24px;
}
</style>
