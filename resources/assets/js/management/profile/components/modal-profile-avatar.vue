<template>
    <b-modal ref="profileModal" title="Profile Avatar">
        <div>
            <div v-if="!image" class="no-avatar">Click the browse button below to get started</div>
            <vue-croppie :style="{display: (image) ? 'block' : 'none' }" ref="croppie"
                         :viewport="{ width: 380, height: 380, type: 'circle' }" :boundary="{ width: 400, height: 400 }"
                         :enableOrientation="false" :enableResize="false">
            </vue-croppie>
        </div>
        <input type="file" class="custom-file-input" ref="customFile" @change="onFileChange">

        <div slot="modal-footer">
            <b-button @click="browse" class="btn btn-success btn-sm text-uppercase"><i class="fas fa-upload"></i>
                BROWSE
            </b-button>
            <b-button @click="hideModal" class="btn btn-outline-success btn-md">
                CANCEL
            </b-button>
            <b-button @click="saveAndEmit" class="btn btn-success btn-sm text-uppercase">
                CONTINUE
            </b-button>
        </div>

    </b-modal>
</template>

<script>
    import VueCroppie from "vue-croppie";
    import avatar from '../../../../js/components/common/avatar.vue'

    // No likey
    Vue.use(VueCroppie);

    export default {
        components: {
            avatar
        },
        data() {
            return {
                image: "",
                uid: window.ProcessMaker.user.uid
            };
        },
        methods: {
            // Called when the croppie instance is completed
            cropResult() {
            },
            saveAndEmit() {
                // We will close our modal, but we will ALSO emit a message stating the image has been updated
                // The parent component will listen for that message and update it's data to reflect the new image
                this.$refs.croppie.result({}, (output) => {
                    this.$emit('image-update', output);

                    // And finally close the modal
                    this.hideModal();

                })

            },
            browse() {
                this.$refs.customFile.click();
            },
            openModal() {
                this.$refs.profileModal.show();
            },
            hideModal() {
                this.$refs.profileModal.hide();
            },
            onFileChange(e) {
                let files = e.target.files || e.dataTransfer.files;
                if (!files.length) return;
                this.createImage(files[0]);
            },
            createImage(file) {
                let reader = new FileReader();

                // Assigning the load listener to store the contents of the file to our image property
                reader.onload = e => {
                    // Show we now have an image in our modal to use
                    this.image = true;
                    this.$refs.croppie.bind({
                        url: e.target.result
                    });
                };
                // Now actually read it, calling the onload after it's read
                reader.readAsDataURL(file);
            }
        }
    };
</script>
<style lang="scss" scoped>
    .no-avatar {
        width: 320px;
        height: 454px;
        line-height: 454px;
        margin: auto;
    }
</style>
