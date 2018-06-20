<template>
  <b-modal ref="profileModal" hide-footer title="Profile Avatar">
    <div class="d-block text-center">
      <div v-if="!image" class="profile-avatar-none text-light">JB</div>
      <div v-else><img :src="image" class="modal-profile-avatar"></div>
      <input type="file" class="custom-file-input" id="customFile" @change="onFileChange">
    </div>
    <div class="mb-5 mt-3 float-right">
      <button type="button" class="btn btn-secondary text-light"><label id="browse" for="customFile"><i class="fas fa-upload"></i> Browse</label></button>
      <button type="button" @click="hideModal" class="btn btn-outline-secondary">Cancel</button>
      <button type="button" @click="hideModal" class="btn btn-secondary text-light">Save</button>
    </div>
  </b-modal>
</template>

<script>
export default{
  data(){
    return{
       image: '',
    }
  },
  methods:{
    openModal () {
      this.$refs.profileModal.show()
    },
    hideModal () {
     this.$refs.profileModal.hide()
    },
    onFileChange(e) {
      let files = e.target.files || e.dataTransfer.files;
      if (!files.length)
        return;
      this.createImage(files[0]);
    },
  }
}
</script>
<style>
.profile-avatar-none{
  width: 82px;
  height: 82px;
  margin-left: 220px;
  background-color: rgb(251,181,4);
  text-align:center;
}
.profile-avatar{
  width: 82px;
  height: 82px;
  margin-left: 220px;
}
</style>
