<template>
  <div class="form-wrap container bg-light mt-3 p-5">
    <h3 class="pl-5">Profile</h3>
    <div>
      <div>
        <avatar :uid="uid" class="avatar-wrapper"></avatar>
        <img class="profile-overlay" align="center" src="/img/avatar-profile-overlay.png" @click="openModal()">
      </div>
    </div>
    <modalProfileAvatar ref="profileModal" @image-update="updateImage">
    </modalProfileAvatar>
    <form class="pl-5 pr-5">
      <div class="row form-group">
        <div class="col">
          <label for="inputAddress">First Name</label>
          <input type="text" class="form-control">
        </div>
        <div class="col">
          <label for="inputAddress">Last Name</label>
          <input type="text" class="form-control">
        </div>
      </div>
      <div class="row form-group">
        <div class="col">
          <label for="inputAddress">User Name</label>
          <input type="text" class="form-control">
        </div>
        <div class="col">
          <label for="inputAddress">Email</label>
          <input type="text" class="form-control">
        </div>
      </div>
      <div class="row form-group">
        <div class="col">
          <label for="inputAddress">New Password</label>
          <input type="text" class="form-control">
        </div>
        <div class="col">
          <label for="inputAddress">Change Password</label>
          <input type="text" class="form-control">
        </div>
      </div>
      <br>
      <div class="row form-group">
        <div class="col">
         <label for="inputAddress">Address</label>
         <input type="text" class="form-control" id="inputAddress" >
       </div>
     </div>
     <div class="row form-group">
       <div class="col">
         <label for="inputAddress">City</label>
         <input type="text" class="form-control">
       </div>
        <div class="col">
          <label for="inputState">State or Region</label>
          <select id="inputState" class="form-control">
            <option selected>Choose...</option>
            <option>...</option>
          </select>
        </div>
      </div>
     <div class="row form-group">
       <div class="col">
         <label for="inputAddress">Zip Code</label>
         <input type="text" class="form-control">
       </div>
        <div class="col">
          <label for="inputState">Country</label>
          <select id="inputState" class="form-control">
            <option selected>Choose...</option>
            <option>...</option>
          </select>
        </div>
      </div>
     <div class="row form-group">
       <div class="col">
         <label for="inputAddress">Phone</label>
         <input type="text" class="form-control">
       </div>
       <div class="col">
         <label for="inputState">Default Time Zone</label>
         <select id="inputState" class="form-control">
           <option selected>Choose...</option>
           <option>...</option>
         </select>
       </div>
      </div>
     <div class="row form-group">
       <div class="col-6">
         <label for="inputState">Language</label>
         <select id="inputState" class="form-control">
           <option selected>Choose...</option>
           <option>...</option>
         </select>
       </div>
      </div>
      <div class="row form-group float-right mt-3">
        <div class="col">
          <button type="button" class="btn btn-outline-secondary">Cancel</button>
          <button type="button" class="btn btn-secondary text-light">Save</button>
        </div>
      </div>
    </form>
  </div>
</template>

<script>

import VueCroppie from 'vue-croppie';
import modalProfileAvatar from './modal-profile-avatar.vue'
import avatar from '../../../../js/components/common/avatar.vue'

export default{
  components:{
    VueCroppie,
    modalProfileAvatar,
    avatar
  },
  data(){
    return{
      // Points to a url of the image
       image: '',
       uid: window.ProcessMaker.user.uid,
    }
  },
  methods: {
    updateImage (newImage) {
      this.image = newImage;
    },
    openModal () {
      this.$refs.profileModal.openModal()
    },
    hideModal () {
     this.$refs.modalProfileAvatar.hide()
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

<style lang="scss" scoped>
  #browse{
    padding: 0;
    margin-bottom: 0;
  }
  form{
    margin-top: 44px;
  }
  .form-wrap{
    max-width: 620px;
  }
  h3{
    font-size: 24px;
  }
  .profile-overlay{
    position: absolute;
    margin-left: -82px;
  }
  .avatar-wrapper{
    width: 82px;
    height: 82px;
    margin-left: 220px;
  }
</style>
