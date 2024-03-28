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

            // Call API to delete
            window.ProcessMaker.apiClient
            .delete(`${this.modelType}/${this.modelId}/media`, {
                data: { uuid },
            })
            .then((response) => {
                window.ProcessMaker.alert(this.$t("The image was deleted"), "success");
            })
            .catch((error) => {
                console.error("Error", error);
            });
            const params = {
            indexImage: index,
            type: "delete",
            };
            window.ProcessMaker.EventBus.$emit("getLaunchpadImagesEvent", params);
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