<template>
  <b-card
    rounded
    elevation="4"
    class="card"
  >
    <b-container>
      <b-row>
        <notification-user :notification="notification" />
        <notification-message
          :notification="notification"
          :show-time="showTime"
        />
      </b-row>
    </b-container>
  </b-card>
</template>

<script>
import notificationsMixin from "../notifications-mixin";
import NotificationUser from "./notification-user";
import NotificationMessage from "./notification-message";

export default {
  components: {
    NotificationUser,
    NotificationMessage,
  },
  mixins: [notificationsMixin],
  props: {
    notification: {
      type: Object,
      required: true,
    },
    showTime: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
    };
  },
  computed: {
    url() {
      return this.notification.data?.url;
    },
    isComment() {
      return this.data.type === "COMMENT";
    },
  },
  methods: {
    touchStart() {
      if (this.url) {
        window.location.href = this.url;
      }
    },
  },
};
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";

.card {
  margin: 16px;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
  border-radius: 10px;
}

.item.clickable {
  cursor: pointer;
  touch-action: manipulation;
}

.item.clickable:hover {
  background-color: lighten($warning, 40%);
}
</style>
