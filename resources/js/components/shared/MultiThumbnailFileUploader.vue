<template>
    <div>
    <div class="no-padding">
        <div class="d-flex align-items-center w-100 mt-2">
            <label>{{ label }}</label>
            <input
                ref="fileInput"
                type="file"
                style="display: none"
                accept="image/*"
                @change="handleImageUpload"
            >
            <i
                class="fas fa-plus-square ml-auto"
                style="cursor: pointer"
                @click="openFileInput"
            />
        </div>
    </div>
    <b-row
        ref="thumbnailsContainer"
        class="image-thumbnails-container"
        @drop="handleDrop"
        @dragover.prevent
        @dragstart.prevent="handleDragStart"
    >
        <b-col v-for="(image, index) in images" :key="index" md="6">
            <div
                class="d-flex justify-content-end align-items-end thumbnail"
                @mouseover="showDeleteIcon(index)"
                @mouseleave="hideDeleteIcon(index)"
            >
                <div v-if="showDeleteIcons[index] || focusIcons[index]" class="m-1 delete-icon">
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
                <img v-if="image && image.url" :src="image.url ? image.url : ''" :alt="image?.name" class="img-fluid">
                <img v-else-if="image && typeof image === 'string'" :src="image" :alt="$t('Image')" class="img-fluid">
            </div>
        </b-col>
        <b-col v-if="images.length === 0" md="12" class="text-center">
            <div class="drag-and-drop-container" @dragover.prevent>
                <i class="fas fa-cloud-upload-alt" />
                <div>
                    <strong>{{ $t("Drop your images here") }}</strong>
                </div>
                <div>
                    {{ $t("Supported formats are PNG and JPG. ") }}
                </div>
                <b-button class="btn-custom-button" @click="openFileInput">
                    {{ $t("Upload Images") }}
                </b-button>
            </div>
        </b-col>
    </b-row>
</div>
</template>

<script>

export default {
    components: {},
    mixins: [],
    props: ["label", "value", "modelType", "modelId"],
    data() {
        return {
            images: [],
            imagesMedia: [],
            showDeleteIcons: Array(4).fill(false),
            focusIcons: Array(4).fill(false),
            list: {},
            maxImages: 4,
            mediaImageId: [],
            dataProcess: {},
        }
    },
    watch: {
        images: {
            deep: true,
            handler() {
                this.$emit('input', this.images);
            }
        }
    },
    methods: {
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
               window.ProcessMaker.alert(
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
        },
        /**
         * Generic Method to manage drag and drop and selected images
        */
        handleImages(files) {
            this.validateImageExtension(files);
        },
        /**
         *  Validates images with png and jpg extensions.
         */
        validateImageExtension(files) {
            console.log("validateImageExtension", files);
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
                        window.ProcessMaker.alert(
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
    },
    mounted() {
        this.images = this.value ? this.value : [];
    }
}
</script>

<style lang="css" scoped>
.image-style {
  width: 80px;
  height: 80px;
  border-radius: 4px;
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
  height: 204px;
  border-radius: 4px;
  padding: 12px;
  overflow-y: auto;
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
.modal-dialog, .modal-content {
  max-width: 727px;
  width: 727px;
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
.modal-content-custom {
  padding: 11px 8px 0px 8px;
}
b-row, b-col {
  margin: 0px;
  padding: 0px;
}
.delete-icon {
  cursor: pointer;
  position: absolute;
  display: flex;
  justify-content: center;
  align-items: center;
  /* width: 80px;
  height: 80px; */
  /* border-radius: 4px; */
  /* background-color: #00000080; */
}
.delete-icon i {
  font-size: 18px;
  color: #000;
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
  color: #000;
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