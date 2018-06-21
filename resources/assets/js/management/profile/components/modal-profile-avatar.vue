<template>
  <b-modal ref="profileModal" hide-footer title="Profile Avatar">
    <div class="d-block text-center">
      <div v-if="!image" class="profile-avatar-none text-light">JB</div>
        <vue-croppie :style="{display: (image) ? 'block' : 'none' }" ref="croppie" :viewport="{ width: 200, height: 200, type: 'circle' }" :enableOrientation="false">
        </vue-croppie>
      </div>
      <input type="file" class="custom-file-input" ref="customFile" @change="onFileChange">
    </div>
    <div class="mb-5 mt-3 float-right">
      <button type="button" @click="browse" class="btn btn-secondary text-light">
        <i class="fas fa-upload"></i> Browse</button>
      <button type="button" @click="hideModal" class="btn btn-outline-secondary">Cancel</button>
      <button type="button" @click="saveAndEmit" class="btn btn-secondary text-light">Continue</button>
    </div>
  </b-modal>
</template>

<script>
import VueCroppie from "vue-croppie";

// No likey
Vue.use(VueCroppie);

export default {
  data() {
    return {
      image: ""
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
        this.$emit('image-update', output)

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
.profile-avatar-none {
  width: 82px;
  height: 82px;
  margin-left: 220px;
  background-color: rgb(251, 181, 4);
  text-align: center;
}
.modal-profile-avatar {
  max-width: 100%;
}

.profile-avatar {
  width: 82px;
  height: 82px;
  margin-left: 220px;
}
</style>
