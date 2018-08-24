<template>
<div class="container" v-if="loaded">
  <h1>Profile</h1>
  <div class="row">
    <div class="col-8">
      <div class="card card-body">
        <h3>Name</h3>
        <div class="row">
          <div class="col">
            <form-input label="First Name" v-model="data.firstname" :error="this.addError"></form-input>
          </div>
          <div class="col">
            <form-input label="Last Name" v-model="data.lastname" :error="this.addError"></form-input>
          </div>
        </div>
        <br>
        <h3>Contact Info</h3>
        <div class="row">
          <div class="col">
            <form-input label="Email" v-model="data.email" :error="this.addError"></form-input>
          </div>
          <div class="col">
            <form-input label="Phone" v-model="data.phone" :error="this.addError"></form-input>
          </div>
        </div>
        <br>
        <h3>Address</h3>
        <form-input label="Address" v-model="data.address" :error="this.addError"></form-input>
        <div class="row">
          <div class="col">
            <form-input label="City" v-model="data.city" :error="this.addError"></form-input>
          </div>
          <div class="col">
            <form-select label="State or Region" v-model="data.state" :options="states" :error="this.addError"></form-select>
          </div>
        </div>

        <div class="row">
          <div class="col">
            <form-input label="Postal Code" v-model="data.zipcode" :error="this.addError"></form-input>
          </div>
          <div class="col">
            <form-select label="Country" v-model="data.country" :options="countries" :error="this.addError"></form-select>
          </div>
        </div>

        <br>
        <h3>Localization</h3>
        <div class="row">
          <div class="col">
            <form-select label="Timezone" v-model="data.timezone" :options="timezones" :error="this.addError"></form-select>
          </div>
          <div class="col">
            <form-select label="Language" v-model="data.language" :options="languages" :error="this.addError"></form-select>
          </div>
        </div>
        <hr>
        <div align="right">
          <b-button variant="success">Save Profile</b-button>
        </div>
      </div>
    </div>
    <div class="col-4">
      <div class="card card-body">
        <h3>Account Login</h3>
        <div align="center">
          <avatar ref="avatar" :uid="uid" class="avatar-wrapper">
            <div slot="optional">
                    <img class="profile-overlay" align="center" src="/img/avatar-profile-overlay.png"
                         @click="openModal()">
                </div>
          </avatar>
        </div>
        <hr>
        <div>
          <div class="col">

            <form-input label="Username" v-model="data.username" :error="this.addError"></form-input>
            <form-input label="New Password" v-model="data.password" :error="this.addError" type="password"></form-input>
            <form-input label="Confirm New Password" v-model="data.password2" :error="this.addError" type="password"></form-input>

          </div>
        </div>
        <br>
      </div>
    </div>
  </div>
  <br>
  <modalProfileAvatar ref="profileModal" @image-update="updateImage"></modalProfileAvatar>
</div>

</template>

<script>
import VueCroppie from "vue-croppie";
import modalProfileAvatar from "./modal-profile-avatar.vue";
import avatar from "../../../components/common/avatar.vue";
import states from "../../../data/usstates.json";
import timezones from "../../../data/timezones.json";
import countries from "../../../data/countries.json";
import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";
import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";

export default {
  components: {
    VueCroppie,
    modalProfileAvatar,
    avatar,
    FormInput,
    FormSelect
  },
  data() {
    return {
      loaded: false,
      // Points to a url of the image
      image: "",
      uid: window.ProcessMaker.user.uid,
      data: {},
      states: [],
      timezones: [],
      countries: [],
      loadStates: states,
      loadTimezones: timezones,
      loadCountries: countries,
      languages: [{ value: "en", content: "English" }]
    };
  },
  mounted() {
    this.load(), this.set_json_objects();
  },
  methods: {
    set_json_objects() {
      // parse the states json into a usable object
      for (let index in this.loadStates) {
        this.states.push({ value: index, content: this.loadStates[index] });
      }
      // parse timezone and countries. Can we use the correct format for the start?
    },

    // Loads data from our profile api to fetch data and populate fields
    load() {
      ProcessMaker.apiClient.get("admin/profile").then(response => {
        // Copy everything into our data
        this.data = response.data;
        this.loaded = true;
      });
    },
    save() {
      delete this.data.avatar;
      if (this.image) {
        this.data.avatar = this.image;
      }
      ProcessMaker.apiClient.put("admin/profile", this.data).then(response => {
        ProcessMaker.alert("Save profile success", "success");
        location.reload();
      });
    },
    updateImage(newImage) {
      this.image = newImage;
      this.$refs.avatar.setImage(newImage);
    },
    openModal() {
      this.$refs.profileModal.openModal();
    },
    hideModal() {
      this.$refs.modalProfileAvatar.hide();
    },
    onFileChange(e) {
      let files = e.target.files || e.dataTransfer.files;
      if (!files.length) return;
      this.createImage(files[0]);
    }
  }
};
</script>

<style lang="scss" scoped>
#browse {
  padding: 0;
  margin-bottom: 0;
}

form {
  margin-top: 44px;
}

.form-wrap {
  max-width: 620px;
}

h3 {
  font-size: 24px;
}

.profile-overlay {
  position: absolute;
  top: 0;
  left: 0;
}

.avatar-wrapper {
  width: 82px;
  height: 82px;
}

.modal-wrapper {
  width: 82px;
  margin: auto;
  position: relative;
}
</style>
