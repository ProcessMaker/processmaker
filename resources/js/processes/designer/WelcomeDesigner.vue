<template>
  <div class="welcome-header mb-4">
    <avatar-image
      id="avatarMenu"
      ref="userMenuButton"
      class-container="d-flex"
      size="60"
      class-image="m-0"
      :input-data="information"
      hide-name="true"
      popover
    />
    <span class="welcome">
      {{ $t("Welcome Back") }} {{ user.fullname }}
    </span>
  </div>
</template>

<script>
import AvatarImage from "../../components/AvatarImage.vue";

export default {
  components: {
    AvatarImage,
  },
  data() {
    return {
      information: [],
      user: window.Processmaker.user,
    };
  },
  mounted() {
    this.fillAvatar(this.user);
  },
  methods: {
    fillAvatar(user) {
      this.information = [
        {
          id: "#",
          tooltip: user.fullname,
          src: user.avatar
            ? `${user.avatar}?${new Date().getTime()}`
            : user.avatar,
          title: "",
          initials:
            user.firstname && user.lastname
              ? user.firstname.match(/./u)[0] + user.lastname.match(/./u)[0]
              : "",
        },
      ];
    },
  },
};
</script>
<style scoped>
.welcome {
  color: #596785;
  font-size: 30.497px;
  font-style: normal;
  font-weight: 600;
  line-height: 60.922px;
  letter-spacing: -0.61px;
}
.welcome-header {
  display: flex;
  height: 64px;
  align-items: flex-start;
  gap: 17px;
}
</style>
