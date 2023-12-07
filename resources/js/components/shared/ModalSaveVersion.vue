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
      <p>
        {{
          $t("Once published, all new requests will use the new process model.")
        }}
      </p>
      <div>
        <b-card no-body>
          <b-tabs card>
            <button
              type="button"
              class="btn btn-custom-button btn-sm position-absolute modeler-save-button custom-button"
              :style="btnStyle"
              @click="swapLabel"
            >
              <i class="fas fa-book mr-1"></i>
              {{ labelButton }}
            </button>
            <b-tab :title="labelTab">
              <b-card-text v-if="showVersionInfo">
                <b-row>
                  <b-col>
                    <label class="label-text mt-2">
                      {{ $t("Description of Process") }}
                    </label>
                    <textarea
                      v-model="processDescription"
                      id="additional-details"
                      class="form-control"
                      type="text"
                      rows="5"
                      :aria-label="$t('Description')"
                    ></textarea>
                    <label class="label-text mt-2">
                      {{ $t("Launchpad Icon") }}
                    </label>
                    <icon-dropdown></icon-dropdown>
                    <label class="label-text mt-2">{{ $t("Chart") }}</label>
                    <div class="dropdown mt-2">
                      <button
                        id="statusDropdown"
                        class="btn btn-secondary dropdown-toggle dropdown-style w-100 d-flex justify-content-between align-items-center btn-custom"
                        type="button"
                        data-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                      >
                        <div class="d-flex align-items-center">
                          <i class="far fa-chart-bar" />
                          <span class="ml-2">{{ selectedSavedChart }}</span>
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
                          <i class="far fa-chart-bar" />
                          {{ item.title }}
                        </a>
                      </div>
                    </div>
                  </b-col>
                  <b-col>
                    <div
                      md="12"
                      class="no-padding"
                    >
                      <div class="d-flex align-items-center w-100 mt-2">
                        <label
                          class="label-text"
                          for="name"
                          >{{ $t("Images for carousel") }}
                        </label>
                        <input
                          type="file"
                          style="display: none"
                          ref="fileInput"
                          accept="image/*"
                          @change="handleImageUpload"
                        />
                        <i
                          class="fas fa-plus-square ml-auto"
                          style="cursor: pointer"
                          @click="openFileInput"
                        />
                      </div>
                    </div>
                    <b-row
                      class="image-thumbnails-container"
                      ref="thumbnailsContainer"
                      @drop="handleDrop"
                      @dragover.prevent
                    >
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
                          />
                        </div>
                      </b-col>
                      <b-col
                        v-if="images.length === 0"
                        md="12"
                        class="text-center"
                      >
                        <div
                          class="drag-and-drop-container"
                          @dragover.prevent
                        >
                          <i class="fas fa-cloud-upload-alt"></i>
                          <div>
                            <strong>{{ $t("Drop your images here") }}</strong>
                          </div>
                          <div>
                            {{ $t("Supported formats are PNG and JPG. ") }}
                          </div>
                          <b-button
                            class="btn-custom-button"
                            @click="openFileInput"
                          >
                            {{ $t("Upload Images") }}
                          </b-button>
                        </div>
                      </b-col>
                    </b-row>
                  </b-col>
                </b-row>
              </b-card-text>
              <b-card-text v-if="!showVersionInfo">
                <label for="name">{{ $t("Version Name") }} </label>
                <input
                  v-model="subject"
                  id="name"
                  class="form-control mt-2"
                  type="text"
                  name="name"
                />
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
                  v-model="description"
                  id="additional-details"
                  class="form-control mt-2"
                  type="text"
                  rows="8"
                  :aria-label="$t('Description')"
                ></textarea>
              </b-card-text>
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
    }
  },
  data() {
    return {
      images: [],
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
      selectedLaunchpadIcon: "",
      showVersionInfo: true,
      labelButton: "Version Info",
      labelTab: "Launchpad Settings",
      btnColorClass: "btn-custom-button",
      isSecondaryColor: false,
    };
  },
  computed: {
    btnStyle() {
      return this.isSecondaryColor
        ? { backgroundColor: "#6a7888", color: 'white' }
        : { backgroundColor: "white", color: '#6a7888' };
    },
  },
  mounted() {
    ProcessMaker.EventBus.$on("open-modal-versions", (redirectUrl, nodeId) => {
      this.redirectUrl = redirectUrl;
      this.nodeId = nodeId;
      this.showModal();
    });
    this.retrieveSavedSearchCharts();
    this.getProcessDescription();
    //Receives selected Option from launchpad Icons multiselect
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
    launchpadIconSelected(iconData) {
      this.selectedLaunchpadIcon = iconData;
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
      Array.from(files).forEach((file) => {
        if (this.images.length < this.maxImages) {
          const reader = new FileReader();
          reader.onload = (event) => {
            this.images.push({ file, url: event.target.result });
            this.showDeleteIcons.push(false);
          };
          reader.readAsDataURL(file);
        }
      });
    },
    /**
     * This method handles dragged image files and adds each image to list
     */
    handleDrop(event) {
      event.preventDefault();

      // Checks if event has 'dataTransfer' property
      if (event.dataTransfer) {
        const files = event.dataTransfer.files;

        // Checks if 'dataTransfer' has 'files' property
        if (files && files.length > 0) {
          if (this.images.length + files.length > this.maxImages) {
            ProcessMaker.alert(
              this.$t("It is not possible to include more than four images."),
              "danger"
            );
            return;
          }

          Array.from(files).forEach((file) => {
            if (this.images.length < this.maxImages) {
              const reader = new FileReader();
              reader.onload = (event) => {
                this.images.push({ file, url: event.target.result });
                this.showDeleteIcons.push(false);
              };
              reader.readAsDataURL(file);
            }
          });
        }
      }
    },
    /**
     * Adds an image from drag and drop to image container
     */
    handleDroppedImage(event) {
      const files = event.dataTransfer.files;
      this.handleImages(files);
    },
    /**
     * Adds index info to dragged object
     */
    handleDragStart(event, index) {
      event.dataTransfer.setData("text/plain", index);
    },
    /**
     * Initial method to retrieve Saved Search Charts and populate dropdown
     */
    retrieveSavedSearchCharts() {
      ProcessMaker.apiClient
        .get(
          "saved-searches?has=charts&include=charts&per_page=100&filter=&get=id,title,charts.id,charts.title,charts.saved_search_id,type"
        )
        .then((response) => {
          if (response.data.data[0].charts) {
            const resultArray = response.data.data.flatMap((item) => {
              if (item.charts && Array.isArray(item.charts)) {
                return item.charts.map((chartItem) => ({
                  title: chartItem.title,
                }));
              }
              return [];
            });

            this.dropdownSavedCharts = resultArray;
            this.selectedSavedChart = resultArray[0].title;
          }
        })
        .catch((error) => {
          this.dropdownSavedCharts = [];
        });
    },
    /**
     * Method to retrieve data from process description field
     */
    getProcessDescription() {
      if( this.origin === "") {
        this.processDescription = ProcessMaker.modeler.process.description;
      } else {
        this.processDescription = this.descriptionSettings;
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
          "danger"
        );
        return;
      }
      const files = event.target.files;
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
      this.images.splice(index, 1);
      this.$set(this.showDeleteIcons, index, false);
      this.$set(this.focusIcons, index, false);
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
    saveModal() {
      //if method is called from package-version PUBLISH button
      if(this.origin !== "core") {
        let promise = new Promise((resolve, reject) => {
        //emit save types
        window.ProcessMaker.EventBus.$emit(
          this.types[this.options.type],
          this.redirectUrl,
          this.nodeId,
          this.options.type === "Screen" ? (false, resolve) : resolve,
          reject
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
            .then((response) => {
              ProcessMaker.alert(this.$t("The version was saved."), "success");
              this.hideModal();
            })
            .catch((error) => {
              if (error.response.status && error.response.status === 422) {
                this.errors = error.response.data.errors;
              }
            });
        })
        .catch((err) => {
          console.log(err);
        });
      } else {
        this.saveFromEditLaunchpad();
      }
      
    },
    showModal() {
      this.subject = "";
      this.description = "";
      this.errors = "";
      this.$refs["my-modal-save"].show();
    },
    /**
     * Method to set selected option to custom dropdown
     */
    selectOption(option) {
      this.selectedSavedChart = option.title;
    },
    saveFromEditLaunchpad() {
      ProcessMaker.apiClient
          .post("/version_histories", {
            subject: this.subject,
            description: this.description,
            versionable_id: this.options.id,
            versionable_type: this.options.type,
          })
          .then((response) => {
            ProcessMaker.alert(this.$t("The version was saved."), "success");
            this.hideModal();
          })
          .catch((error) => {
            if (error.response.status && error.response.status === 422) {
              this.errors = error.response.data.errors;
            }
          });
    }
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
  font-size: 12px;
  padding: 5px 10px;
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

.label-text {
  font-size: 12px;
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

.icon-square {
  color: #788793;
  font-size: $iconSize;
  padding: calc($iconSize / 1.5);
  text-align: center;
}

.btn-custom {
  text-transform: none;
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
  color: black;
}

.text-black {
  color: #000000;
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
}
</style>
