<template>
  <div
    class="item border-top py-3"
    :title="url"
    :class="{ 'clickable': url }"
    @click="click"
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
  </div>
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
    click() {
      if (this.url) {
        window.location.href = this.url;
      }
    },
  },
};
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";

.item.clickable {
  cursor: pointer;
}
.item.item.clickable:hover {
  background-color: lighten($warning, 40%);
}

</style>
