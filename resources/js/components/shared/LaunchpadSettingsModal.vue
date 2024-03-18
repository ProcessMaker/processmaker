<template>
  <modal
    ref="my-modal-save"
    size="lg"
    class="modal-dialog modal-dialog-centered"
    :title="$t('Launchpad Settings')"
    @ok.prevent="saveModal()"
    @hidden="hideModal()"
  >
    <p class="text-info-custom">
      {{ $t('Here you can personalize how your process will be shown in the process browser') }}
    </p>
    <div class="d-flex justify-content-between">
      <div class="mr-3">
        <div
          md="12"
          class="no-padding"
        >
          <div class="d-flex align-items-center w-100 mt-2">
            <label>{{ $t("Launchpad Carousel") }}</label>
            <input
              ref="fileInput"
              type="file"
              style="display: none"
              accept="image/*"
              @change="handleImageUpload"
            >
          </div>
        </div>
        <div
          ref="thumbnailsContainer"
          class="image-thumbnails-container"
          @drop="handleDrop"
          @dragover.prevent
          @dragstart.prevent="handleDragStart"
        >
          <div
            v-if="images.length === 0"
            md="12"
            class="text-center"
          >
            <div
              class="drag-and-drop-container"
              @dragover.prevent
            >
              <i class="fas fa-cloud-upload-alt" />
              <div>
                <strong>{{ $t("Drop your images here") }}</strong>
              </div>
              <div>
                {{ $t("Supported formats are PNG and JPG. ") }}
              </div>
            </div>
          </div>
          <b-row v-else>
            <b-col
              v-for="(image, index) in images"
              :key="index"
              md="6"
            >
              <div
                class="d-flex justify-content-end align-items-end thumbnail"
                @mouseover="showDeleteIcon(index)"
                @mouseleave="hideDeleteIcon(index)"
              >
                <div
                  v-if="showDeleteIcons[index] || focusIcons[index]"
                  class="m-1 delete-icon"
                >
                  <button
                    id="popover-button-event"
                    type="button"
                    class="btn btn-light p-0 px-1"
                    @click="focusIcon(index)"
                  >
                    <i class="fas fa-trash-alt p-0 custom-color" />
                  </button>
                  <b-popover
                    ref="popover"
                    :show.sync="focusIcons[index]"
                    target="popover-button-event"
                    triggers="focus"
                    placement="bottom"
                  >
                    <div class="p-3">
                      <p class="text-center">
                        {{ $t("Do you really want to delete this image?") }}
                      </p>
                      <div class="d-flex justify-content-around">
                        <button
                          type="button"
                          class="btn btn-secondary"
                          @click="unfocusIcon(index)"
                        >
                          {{ $t("Cancel") }}
                        </button>
                        <button
                          type="button"
                          class="btn btn-danger"
                          @click="deleteImage(index)"
                        >
                          {{ $t("Delete") }}
                        </button>
                      </div>
                    </div>
                  </b-popover>
                </div>
                <img
                  v-if="image.url"
                  :src="image.url"
                  :alt="$t('No Image')"
                  class="img-fluid"
                >
              </div>
            </b-col>
          </b-row>
          <b-button
            class="btn-custom-button"
            @click="openFileInput"
          >
            {{ $t("Upload Images") }}
          </b-button>
        </div>
      </div>
      <div class="options-launchpad">
        <label class="mt-2">
          {{ $t("Launchpad Icon") }}
        </label>
        <icon-dropdown ref="icon-dropdown" />
        <label class="mt-2">{{ $t("Chart") }}</label>
        <div class="dropdown">
          <button
            id="statusDropdown"
            class="btn dropdown-toggle dropdown-style w-100 d-flex justify-content-between align-items-center btn-custom"
            type="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
          >
            <div class="d-flex align-items-center">
              <i class="far fa-chart-bar" />
              <span class="ml-2 custom-text">{{ selectedSavedChart || 'Select Chart' }}</span>
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
              <i class="far fa-chart-bar custom-text" />
              {{ item.title || 'Select Chart' }}
            </a>
          </div>
        </div>
      </div>
    </div>
    <label class="mt-2">
      {{ $t("Description") }}
    </label>
    <input
      id="additional-details"
      v-model="processDescription"
      class="form-control input-custom"
      type="text"
      rows="5"
      :aria-label="$t('Description')"
    />
    <span v-if="!processDescription" class="error-message">
      {{ $t("The Description field is required.") }}
      <br>
    </span>
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
      selectedSavedChart: "",
      dropdownSavedCharts: [],
      maxImages: 4,
      processDescription: "",
      processDescriptionInitial: "",
      selectedLaunchpadIcon: "",
      selectedLaunchpadIconLabel: "",
      showVersionInfo: true,
      isSecondaryColor: false,
      selectedSavedChartId: "",
      processId: "",
      mediaImageId: [],
      dataProcess: {},
    };
  },
  computed: {
    
  },
  mounted() {
    this.retrieveSavedSearchCharts();
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
    hideModal() {
      this.$refs["my-modal-save"].hide();
      this.cleanTabLaunchpad();
    },
    cleanTabLaunchpad() {
      this.getProcessDescription();
      this.images = [];
      this.retrieveSavedSearchCharts();
      this.showVersionInfo = true;
      this.isSecondaryColor = false;
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

            this.dropdownSavedCharts = resultArray;
            this.selectedSavedChart = "";
          }
        })
        .catch((error) => {
          this.dropdownSavedCharts = [];
        });
    },
    /**
     * Adds index info to dragged object
     */
    handleDragStart(event, index) {
      event.dataTransfer.setData("text/plain", index);
      event.preventDefault();
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
     * Method that allows drag elements to the container
     */
    handleDragOver(event) {
      event.preventDefault();
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
     * Generic Method to manage drag and drop and selected images
     */
    handleImages(files) {
      this.validateImageExtension(files);
    },
    /**
     * Adds an image from drag and drop to image container
     */
    handleDroppedImage(event) {
      const { files } = event.dataTransfer;
      this.handleImages(files);
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
     * Validate image extensions
     */
    isValidFileExtension(fileName) {
      const allowedExtensions = [".jpg", ".jpeg", ".png"];
      return allowedExtensions.includes(
        fileName.slice(fileName.lastIndexOf(".")).toLowerCase(),
      );
    },
    /**
     * Method to open a screen for image selection from hard drive
     */
    openFileInput() {
      this.$refs.fileInput.click();
    },
    launchpadIconSelected(iconData) {
      this.selectedLaunchpadIcon = iconData.value;
      this.selectedLaunchpadIconLabel = iconData.label;
    },
  },
};
</script>

<style lang="scss">
label {
  color: #556271;
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
.modal-footer .btn-outline-secondary {
  color: #556271;
  background-color: white;
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
.modal-footer .btn-secondary {
  color: white;
  background-color: #6A7888;
  width: 99px;
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
}
.text-info-custom {
  color: #556271;
  margin-top: 16px;
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
  padding: 0px;
}
.drag-and-drop-container {
  border: none;
  border-radius: 5px;
  margin-top: 0px;
  color: #2381c8;
}
.modal-dialog, .modal-content {
  max-width: 727px;
  width: 727px;
}
.options-launchpad {
  width: 285px;
}
</style>
