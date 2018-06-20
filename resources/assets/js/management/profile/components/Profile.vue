<template>
  <div class="form-wrap container bg-light mt-4 p-5">
    <h3 class="pl-5">Profile</h3>
    <div>
      <div class="custom-file">
        <div v-if="!image" class="profile-avatar-none text-light">JB</div>
        <div v-else ><img :src="image" class="profile-avatar"></div>
        <img class="profile-overlay" align="center" src="/img/avatar-profile-overlay.png" @click="openModal()">
        <input type="file" class="custom-file-input" id="customFile" @change="onFileChange">
      </div>
    </div>
    <b-modal ref="myModalRef" hide-footer title="Using Component Methods">
      <div class="d-block text-center">
        <h3>Hello From My Modal!</h3>
      </div>
      <b-btn class="mt-3" variant="outline-danger" block @click="hideModal">Close Me</b-btn>
    </b-modal>
    <form class="pl-5 pr-5">
      <div class="row form-group">
        <div class="col">
          <label for="inputAddress">First Name</label>
          <input type="text" class="form-control" placeholder="First name">
        </div>
        <div class="col">
          <label for="inputAddress">Last Name</label>
          <input type="text" class="form-control" placeholder="Last name">
        </div>
      </div>
      <div class="row form-group">
        <div class="col">
          <label for="inputAddress">User Name</label>
          <input type="text" class="form-control" placeholder="First name">
        </div>
        <div class="col">
          <label for="inputAddress">Email</label>
          <input type="text" class="form-control" placeholder="Last name">
        </div>
      </div>
      <div class="row form-group">
        <div class="col">
          <label for="inputAddress">New Password</label>
          <input type="text" class="form-control" placeholder="First name">
        </div>
        <div class="col">
          <label for="inputAddress">Change Password</label>
          <input type="text" class="form-control" placeholder="Last name">
        </div>
      </div>
      <br>
      <div class="row form-group">
        <div class="col">
         <label for="inputAddress">Address</label>
         <input type="text" class="form-control" id="inputAddress" placeholder="1234  St">
       </div>
     </div>
     <div class="row form-group">
       <div class="col">
         <label for="inputAddress">City</label>
         <input type="text" class="form-control" placeholder="First name">
       </div>
        <div class="col">
          <label for="inputState">State</label>
          <select id="inputState" class="form-control">
            <option selected>Choose...</option>
            <option>...</option>
          </select>
        </div>
      </div>
     <div class="row form-group">
       <div class="col">
         <label for="inputAddress">Address</label>
         <input type="text" class="form-control" placeholder="First name">
       </div>
        <div class="col">
          <label for="inputState">State</label>
          <select id="inputState" class="form-control">
            <option selected>Choose...</option>
            <option>...</option>
          </select>
        </div>
      </div>
     <div class="row form-group">
       <div class="col">
         <label for="inputAddress">Address</label>
         <input type="text" class="form-control" placeholder="First name">
       </div>
       <div class="col">
         <label for="inputAddress">Address</label>
         <input type="text" class="form-control" placeholder="First name">
       </div>
      </div>
     <div class="row form-group">
       <div class="col">
         <label for="inputAddress">Address</label>
         <input type="text" class="form-control" placeholder="First name">
       </div>
       <div class="col">
         <label for="inputAddress">Address</label>
         <input type="text" class="form-control" placeholder="First name">
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

export default{
  components:{
    VueCroppie,
  },
  data(){
    return{
       image: '',
    }
  },
  methods: {
    openModal () {
      this.$refs.myModalRef.show()
    },
    hideModal () {
     this.$refs.myModalRef.hide()
    },
    onFileChange(e) {
      var files = e.target.files || e.dataTransfer.files;
      if (!files.length)
        return;
      this.createImage(files[0]);
    },
    createImage(file) {
      var image = new Image();
      var reader = new FileReader();
      var vm = this;

      reader.onload = (e) => {
        vm.image = e.target.result;
      };
      reader.readAsDataURL(file);
    },
  }
}
</script>

<style lang="scss" scoped>
  form{
    margin-top: -34px;
  }
  .form-wrap{
    max-width: 620px;
  }
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
  h3{
    font-size: 24px;
  }
  .profile-overlay{
    position: absolute;
    margin-left: 220px;
    margin-top: -82px;
  }
</style>
