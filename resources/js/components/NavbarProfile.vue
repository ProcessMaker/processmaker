<template>
  <div id="userMenu">
    <a data-toggle="dropdown" href="#" aria-expanded="false" id="profileMenu">
      <avatar-image
        id="avatarMenu"
        class-container="d-flex m-1"
        size="40"
        class-image="m-1"
        :input-data="information"
      ></avatar-image>
    </a>

    <b-popover target="profileMenu" placement="bottomleft" triggers="click blur">
      <template>
        <ul class="list-group list-group-flush m-1">
          <li class="list-group-item px-1">
            <a :href="user_id">
              <i class="fas fa-user fa-fw fa-lg"></i>
              {{$t('View Profile')}}
            </a>
          </li>
          <li class="list-group-item px-1">
            <a href="/profile/edit">
              <i class="fas fa-user-cog fa-fw fa-lg"></i>
              {{$t('Profile Settings')}}
            </a>
          </li>
          <li class="list-group-item px-1">
            <a href="https://processmaker.gitbook.io/processmaker/" target="_blank">
              <i data-v-2eb90a9e class="fas fa-question-circle fa-fw fa-lg"></i>
              {{$t('Documentation')}}
            </a>
          </li>
          <li class="list-group-item px-1">
            <a href="/about">
              <i class="fas fa-info-circle fa-fw fa-lg"></i>
              {{$t('About')}}
            </a>
          </li>
          <li class="list-group-item px-1">
            <a href="/logout">
              <i class="fas fa-sign-out-alt fa-fw fa-lg"></i>
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
      user_id: null,
      popoverShow: true,
      information: []
    };
  },
  props: ["info", "items"],
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

.avatar-image {
  width: 40px;
  height: 40px;
  margin-left: -16px;
  margin-top: -7px;
}
</style>
