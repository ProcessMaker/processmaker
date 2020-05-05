<template>
  <div id="userMenu">
    <div id="profileMenu">    
      <avatar-image
        id="avatarMenu"
        class-container="d-flex"
        size="40"
        class-image="m-0"
        :input-data="information"
        hide-name="true"
      ></avatar-image>
    </div>

    <b-popover target="profileMenu" placement="bottomleft" offset="3" triggers="click blur">
      <template>
        <ul class="list-group list-group-flush px-1">
          <li class="list-group-item px-2">
            <a :href="user_id">
              <i class="fas fa-user fa-fw fa-lg mr-1"></i>
              {{$t('View')}} {{username}} {{$t('Profile')}}
            </a>
          </li>
          <li class="list-group-item px-2">
            <a href="/profile/edit">
              <i class="fas fa-user-cog fa-fw fa-lg mr-1"></i>
              {{$t('Edit')}} {{username}} {{$t('Profile')}}
            </a>
          </li>
          <li v-if="displayMyFilesLink" class="list-group-item px-2">
            <a href="/file-manager">
              <i class="fas fa-folder fa-fw fa-lg mr-1"></i>
              {{$t('My Files')}}
            </a>
          </li>
          <li class="list-group-item px-2">
            <a href="https://processmaker.gitbook.io/processmaker/" target="_blank">
              <i data-v-2eb90a9e class="fas fa-question-circle fa-fw fa-lg mr-1"></i>
              {{$t('Documentation')}}
            </a>
          </li>
          <li class="list-group-item px-2">
            <a href="/about">
              <i class="fas fa-info-circle fa-fw fa-lg mr-1"></i>
              {{$t('About')}}
            </a>
          </li>
          <li class="list-group-item px-2">
            <a href="/logout">
              <i class="fas fa-sign-out-alt fa-fw fa-lg mr-1"></i>
              {{$t('Log Out')}}
            </a>
          </li>
        </ul>
      </template>
    </b-popover>
  </div>
</template>

<script>
import Vue from "vue";
import AvatarImage from "../components/AvatarImage";
import VueCroppie from "vue-croppie";

Vue.component("avatar-image", AvatarImage);
Vue.use(VueCroppie);

export default {
  data() {
    return {
      sourceImage: false,
      fullName: null,
      username: null,
      user_id: null,
      popoverShow: true,
      information: []
    };
  },
  props: ["info", "items"],
  computed: {
    displayMyFilesLink() {
      return window.ProcessMaker.packages.includes('package-files');
    }
  },
  methods: {
    __(variable) {
      return __(variable);
    },
    onClose() {
      this.popoverShow = false;
    },
    onHidden() {
      this.popoverShow = false;
    },
    formatData(user) {
      if (user.avatar) {
        this.sourceImage = true;
      }
      this.fullName = user.fullname;
      this.user_id = "/profile/" + user.id;
      this.username = user.username;
      this.information = [
        {
          src: user.avatar
            ? user.avatar + "?" + new Date().getTime()
            : user.avatar,
          title: "",
          initials:
            user.firstname && user.lastname
              ? user.firstname.match(/./u)[0] + user.lastname.match(/./u)[0]
              : ""
        }
      ];
    },
    updateAvatar() {
      ProcessMaker.apiClient
        .get("users/" + window.ProcessMaker.user.id)
        .then(response => {
          this.formatData(response.data);
        });
    }
  },
  mounted() {
    this.formatData(this.info);
    window.ProcessMaker.events.$on("update-profile-avatar", this.updateAvatar);
  }
};
</script>

<style lang="scss" scoped>
.wrap-name {
  font-size: 16px;
  font-weight: 600;
  width: 140px;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}

.wrap-name:hover {
  white-space: initial;
  overflow: visible;
  cursor: pointer;
}

.item {
  font-size: 12px;
  padding: 5px;
  width: 160px;
}
</style>
