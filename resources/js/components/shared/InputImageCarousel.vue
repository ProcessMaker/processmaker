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
              placement="bottom"
            >
              <div class="popover-embed">
                <label class="mt-0">
                  {{ $t("Embed URL") }}
                </label>
                <input
                  :id="`embed-input-${index}`"
                  v-model="embedUrls[index]"
                  class="form-control input-custom mb-0"
                  type="url"
                  rows="5"
                  :aria-label="$t('Embed URL')"
                >
                <span
                  v-if="notURL"
                  class="error-message"
                >
                  {{ $t("The URL is required.") }}
                  <br>
                </span>
                <div
                  v-if="!deleteEmbed"
                  class="d-flex justify-content-between mt-3"
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
                  class="d-flex justify-content-between mt-3"
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
        v-show="!loadingImage && !notValidImage"
        id="idDropdownMenuUpload"
        class="justify-content-center align-items-center"
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
      validSizeImageMB: 2,
      notURL: false,
    };
  },
  methods: {
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
            this.images.push({ url: base64Data, uuid: media.uuid, type: media.custom_properties.url ?? "image" });
          };
          reader.readAsDataURL(blob);
        })
        .catch((error) => {
          console.error("Error loading image:", error);
        });
    },
    /**
     * Adding File embed url
     */
    addEmbedFile(media) {
      const customProperties = JSON.parse(media.custom_properties)
      const mediaURL = customProperties.url;
      this.images.push({
        url: mediaURL,
        uuid: media.uuid,
        type: customProperties.type,
      });
      this.embedUrls[this.images.length - 1] = mediaURL;
    },
    setProcessId(processId) {
      this.processId = processId;
    },
    getImages() {
      return this.images;
    },
    /**
     * Check if embed media are valid
     */
    checkImages() {
      let isValid = true;
      this.images.forEach((image) => {
        if (image.url === "") {
          ProcessMaker.alert(this.$t("Invalid embed media"), "danger");
          isValid = false;
        }
      });
      return isValid;
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
          if (this.isValidFileExtension(file.name) && this.isValidSize(file)) {
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
      if (this.images[index] && !this.images[index].url) {
        this.notURL = true;
        return;
      }
      this.$set(this.embedUrls, index, this.images[index].url);
      this.unfocusIcon(index);
      this.notURL = false;
    },
    saveEmbed(index) {
      if (!this.embedUrls[index] || !this.isValidURL(this.embedUrls[index])) {
        this.notURL = true;
        return;
      }
      this.images[index].url = this.embedUrls[index];
      this.unfocusIcon(index);
      this.notURL = false;
    },
    isValidURL(urlString) {
      try {
        const url = new URL(urlString);
        return url.protocol === "http:" || url.protocol === "https:";
      } catch (error) {
        return false;
      }
    },
    /**
     * Validate image extensions
     */
    isValidFileExtension(fileName) {
      const allowedExtensions = [".jpg", ".jpeg", ".png"];
      let response = allowedExtensions.includes(
        fileName.slice(fileName.lastIndexOf(".")).toLowerCase(),
      );
      if(!response) {
        ProcessMaker.alert(
          this.$t("Only PNG and JPG extensions are allowed."),
          "danger",
        );
      }
      return response;
    },
    isValidSize(file) {
      let fileSize = file.size;
      // Convert the bytes to Kilobytes (1 KB = 1024 Bytes)
      let sizekiloBytes = parseInt(fileSize / 1024);
      // Convert the KB to MegaBytes (1 MB = 1024 KBytes)
      let sizeMegaBytes = sizekiloBytes / 1024;
      let response = sizeMegaBytes <= this.validSizeImageMB;
      if (!response) {
        ProcessMaker.alert(
          this.$t("Only images smaller than 2MB are allowed."),
          "danger",
        );
      }
      return response;
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
      this.$set(this.embedUrls, index, this.images[index].url);
      this.deleteEmbed = false;
      this.notURL = false;
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
    /**
     * Method to delete image from carousel container
     */
     deleteEmbedMedia(index) {
      const { uuid } = this.images[index];
      this.images.splice(index, 1);
      this.$set(this.showDeleteIcons, index, false);
      this.$set(this.focusIcons, index, false);

      this.embedUrls[index] = '';
      this.notURL = false;

      // Call API to delete
      ProcessMaker.apiClient
        .delete(`process_launchpad/${this.processId}/embed`, {
          data: { uuid },
        })
        .then((response) => {
          ProcessMaker.alert(this.$t("The embed media was deleted"), "success");
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
.drag-and-drop-container {
  font-family: 'Open Sans', sans-serif;
  font-size: 14px;
  font-weight: 400;
  line-height: 19.07px;
  letter-spacing: -0.02em;
  text-align: center;
  color: #6a7888;
  margin-bottom: 9px
}
.drag-and-drop-container i {
  font-size: 32px;
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
.dropdown-item {
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
</style>