<template>
    <div v-if="loaded">
        <div class="modal-wrapper">
            <avatar ref="avatar" :uid="uid" class="avatar-wrapper">
                <template slot="optional">
                    <img class="profile-overlay" align="center" src="/img/avatar-profile-overlay.png"
                         @click="openModal()">
                </template>
            </avatar>
        </div>
        <modalProfileAvatar ref="profileModal" @image-update="updateImage">
        </modalProfileAvatar>
    </div>
</template>

<script>
import VueCroppie from "vue-croppie";
import modalProfileAvatar from "./modal-profile-avatar.vue";
import avatar from "../../../components/common/avatar.vue";
import states from "../../../data/states_hash.json";
import timezones from "timezones.json";

let countries = require("country-json/src/country-by-abbreviation.json");

export default {
  components: {
    VueCroppie,
    modalProfileAvatar,
    avatar
  },
  data() {
    return {
      loaded: false,
      // Points to a url of the image
      image: "",
      uid: window.ProcessMaker.user.uid,
      data: {},
      states: states,
      timezones: timezones,
      countries: countries
    };
  },
  mounted() {
    this.load();
  },
  methods: {
    // Loads data from our profile api to fetch data and populate fields
    load() {
      ProcessMaker.apiClient.get("/users").then(response => {
        // Copy everything into our data
        this.data = response.data;
        this.loaded = true;
        console.log(response.data);
      });
    },
    save() {
      delete this.data.avatar;
      if (this.image) {
        this.data.avatar = this.image;
      }
      ProcessMaker.apiClient.put("/users", this.data).then(response => {
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
  top: 0px;
  left: 0px;
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
