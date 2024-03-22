<template>
  <div>
    <input
      ref="fileInput"
      type="file"
      style="display: none"
      accept="image/*"
      @change="handleImageUpload"
    >
    <div
      ref="thumbnailsContainer"
      class="image-thumbnails-container"
      @drop="handleDrop"
      @dragover.prevent
      @dragstart.prevent="handleDragStart"
    >
      <div
        v-if="images.length === 0 && !loadingImage"
        md="12"
        class="text-center images-info images-container"
      >
        <div
          class="drag-and-drop-container"
        >
          <i class="fas fa-image" />
          <div>
            {{ $t("Formats: PNG, JPG. 2 MB") }}
          </div>
            {{ $t("Recommended: 1800 x 750 px") }}
          </div>
      </div>
      <div
        v-else
        class="images-container"
      >
        <div
          v-for="(image, index) in images"
          :key="index"
          md="6"
        >
          <div
            v-if="image.type !== 'embed'"
            class="d-flex justify-content-end align-items-end thumbnail image-style mr-2"
            @mouseover="showDeleteIcon(index)"
            @mouseleave="hideDeleteIcon(index)"
          >
            <div
              v-if="showDeleteIcons[index] || focusIcons[index]"
              :id="`popover-button-event-${index}`"
              class="delete-icon"
              @click="focusIcon(index)"
            >
              <div>
                  <i class="fas fa-trash-alt p-0 custom-color" />
              </div>
              <b-popover
                ref="popover"
                :show.sync="focusIcons[index]"
                :target="`popover-button-event-${index}`"
                triggers="focus"
                placement="bottom"
              >
                <div class="d-flex popover-custom">
                  <p class="m-0">
                    {{ $t("Do you want to delete this image?") }}
                  </p>
                  <button
                    type="button"
                    class="btn btn-delete-image btns-popover"
                    @click="deleteImage(index)"
                  >
                    {{ $t("Delete") }}
                  </button>
                  <button
                    type="button"
                    class="btn btn-cancel-delete btns-popover"
                    @click="unfocusIcon(index)"
                  >
                    {{ $t("Cancel") }}
                  </button>
                </div>
              </b-popover>
            </div>
            <img
              v-if="image.url"
              :src="image.url"
              :alt="$t('No Image')"
              class="image-style"
            >
          </div>
          <div
            v-else
            id="embedFile"
          >
            <div
              :id="`popover-embed-event-${index}`"
              class="square-image image-style"
              @click="focusIcon(index)"
            >
              <i class="fas fa-link" />
            </div>
            <b-popover
              ref="popover"
              :show.sync="focusIcons[index]"
              :target="`popover-embed-event-${index}`"
              triggers="focus"
              placement="bottom"
            >
              <div class="popover-embed">
                <label>
                  {{ $t("Embed URL") }}
                </label>
                <input
                  :id="`embed-input-${index}`"
                  v-model="embedUrls[index]"
                  class="form-control input-custom"
                  type="text"
                  rows="5"
                  :aria-label="$t('Embed URL')"
                />
                  <div
                    v-if="!deleteEmbed"
                    class="d-flex justify-content-between"
                  >
                    <i
                      class="fas fa-trash-alt custom-trash-icon"
                      @click="deleteEmbed = true"
                    />
                    <div class="d-flex">
                      <button
                        type="button"
                        class="btn btn-cancel-delete btns-popover"
                        @click="cancelEmbed(index)"
                      >
                        {{ $t("Cancel") }}
                      </button>
                      <button
                        type="button"
                        class="btn btn-delete-image btns-popover"
                        @click="saveEmbed(index)"
                      >
                        {{ $t("Apply") }}
                      </button>
                  </div>
                </div>
                <div
                  v-else
                  class="d-flex justify-content-between"
                >
                  <span
                    class="text-delete-embed"
                  >
                    {{ $t("Delete this embed media?") }}
                  </span>
                  <div class="d-flex">
                    <button
                      type="button"
                      class="btn btn-cancel-delete btns-popover"
                      @click="deleteEmbed = false"
                    >
                      {{ $t("Cancel") }}
                    </button>
                    <button
                      type="button"
                      class="btn btn-delete-embed btns-popover"
                      @click="deleteEmbedMedia(index)"
                    >
                      {{ $t("Delete") }}
                    </button>
                  </div>
                </div>
              </div>
            </b-popover>
          </div>
        </div>
        <div
          v-if="loadingImage"
          class="square-image image-style"
        >
          <i class="fas fa-solid fa-circle-notch fa-spin" />
        </div>
      </div>
      <div
        v-if="!loadingImage && !notValidImage"
        id="idDropdownMenuUpload"
        class="d-flex justify-content-center align-items-center"
      >
        <div
          class="input-file-custom dropdown-toggle"
          @dragover.prevent
          type="button"
          id="dropdownMenuUpload"
          data-toggle="dropdown"
          aria-haspopup="true"
          aria-expanded="false"
        >
          <i class="fa fa-plus mr-1" />
          <span class="font-weight-bold mr-1">
            {{ $t("Drag or click here") }}
          </span>
          {{ $t("to upload an image") }}
        </div>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuUpload">
          <a
            class="dropdown-item"
            href="#"
            @click="openFileInput"
          >
            {{ $t("Load an Image") }}
          </a>
          <a
            class="dropdown-item"
            href="#"
            @click="addEmbedMedia"
          >
            {{ $t("Embed Media") }}
          </a>
        </div>
      </div>
      <div
        v-if="loadingImage"
        class="d-flex justify-content-center align-items-center"
      >
        <div
          class="input-file-custom"
        >
          {{ $t("Loading...") }}
        </div>
      </div>
      <div
        v-if="notValidImage"
        class="d-flex justify-content-center align-items-center"
      >
        <div
          class="input-file-custom"
        >
          <b-icon icon="exclamation-triangle-fill" class="mr-1" variant="warning" />
          {{ $t("Image not valid, try another") }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>

export default {
  props: {
    process: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      images: [],
      embedUrls: Array(4).fill(""),
      showDeleteIcons: Array(4).fill(false),
      focusIcons: Array(4).fill(false),
      maxImages: 4,
      loadingImage: false,
      notValidImage: false,
      deleteEmbed: false,
      processId: "",
    };
  },
  computed: {
  },
  methods: {
    /**
     * Converts Image from URL to Base64
     */
     convertImageUrlToBase64(media) {
      this.processId = processId;
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
    cleanCarousel() {
      this.images = [];
      this.embedUrls = Array(4).fill("");
      this.notValidImage = false;
      this.loadingImage = false;
    },
    setProcessId(processId) {
      this.processId = processId;
    },
    getImages() {
      return this.images;
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
      this.loadingImage = true;
      Array.from(files).forEach((file) => {
        if (this.images.length < this.maxImages) {
          if (this.isValidFileExtension(file.name)) {
            const reader = new FileReader();
            reader.onload = (event) => {
              this.images.push({
                file,
                url: event.target.result,
                uuid: "",
                type: "image",
              });
              this.showDeleteIcons.push(false);
            };
            reader.readAsDataURL(file);
          } else {
            this.notValidImage = true;
            setTimeout(() => {
              this.notValidImage = false;
            }, 4000);
            ProcessMaker.alert(
              this.$t("Only PNG and JPG extensions are allowed."),
              "danger",
            );
          }
          this.loadingImage = false;
        }
      });
    },
    addEmbedMedia() {
      if (this.images.length >= this.maxImages) {
        // The amount of images allowed was reached.
        ProcessMaker.alert(
          this.$t("It is not possible to include more than four images."),
          "danger",
        );
        return;
      }
      this.images.push({
        url: "",
        uuid: "",
        type: "embed",
      });
      this.focusIcon(this.images.length - 1);
    },
    cancelEmbed(index) {
      this.$set(this.embedUrls, index, this.images[index].url);
      this.unfocusIcon(index);
    },
    saveEmbed(index) {
      this.images[index].url = this.embedUrls[index];
      this.unfocusIcon(index);
    },
    deleteEmbedMedia(index) {
      this.embedUrls[index] = '';
      this.deleteImage(index);
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
    /**
     * Adds index info to dragged object
     */
    handleDragStart(event, index) {
      event.dataTransfer.setData("text/plain", index);
      event.preventDefault();
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
      this.deleteEmbed = false;
      this.$set(this.focusIcons, index, true);
    },
    /**
     * Method to unfocus trash image
     */
    unfocusIcon(index) {
      this.$set(this.focusIcons, index, false);
      this.deleteEmbed = false;
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
  },
};
</script>

<style lang="scss">

</style>