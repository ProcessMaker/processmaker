<template>
  <div id="userMenu">
    <avatar-image
      id="avatarMenu"
      class-container="d-flex m-1"
      size="40"
      class-image="m-1"
      :input-data="information"
    ></avatar-image>

    <b-popover
      target="avatarMenu"
      triggers="click blur"
      placement="bottomleft"
      container="userMenu"
      ref="popover"
      @hidden="onHidden"
    >
      <template slot="title">
        <div class="wrap-name">{{fullName}}</div>
      </template>
      <template>
        <a
          data-v-2eb90a9e
          href="https://processmaker.gitbook.io/processmaker-4-community/-LPblkrcFWowWJ6HZdhC/"
          class="dropdown-item item"
          target="_blank"
        >
          <i data-v-2eb90a9e class="fas fa-question-circle fa-fw fa-lg"></i> Help
        </a>
        <template v-for="item in items">
          <a class="dropdown-item item" :href="item.url">
            <i :class="item.class"></i>
            {{item.title}}
          </a>
        </template>
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
      popoverShow: false,
      information: []
    };
  },
  props: ["info", "items"],
  methods: {
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
      this.information = [
        {
          src: user.avatar,
          title: "",
          initials: user.firstname[0] + user.lastname[0]
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
/deep/ .popover-header {
  background-color: #fff;
  font-size: 16px;
  font-weight: 600;
  color: #333333;
}

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
