<template>
  <li
    v-cloak
    class="nav-item"
  >
    <div id="notificationMenu">
      <b-button
        id="notification-menu-button"
        :variant="buttonVariant"
        :class="{'is-open': isOpen, 'has-messages': hasMessages}"
        class="notification-menu-button"
        data-toggle="dropdown"
        role="button"
        aria-haspopup="menu"
        :aria-expanded="isOpen"
        :title="$t('Notifications')"
        :aria-label="ariaLabel"
        size="sm"
      >
        <div class="bell-icon">
          <i class="fas fa-bell" />
          <span
            v-if="hasMessages"
            class="dot"
          />
        </div>
        <span
          v-if="hasMessages"
          class="message-count"
        >{{ displayTotalCount }}</span>
      </b-button>
      <b-popover
        v-if="shouldShowMobilePopover"
        target="notification-menu-button"
        offset="1"
        triggers="click blur"
        custom-class="notification-popover-wrapper custom-popover"
        data-cy="notification-popover"
        @shown="onShown"
        @hidden="onHidden"
      >
        <div class="notification-popover">
          <b-container
            fluid
            class=""
          >
            <b-row align-h="between">
              <b-col>
                <b-tabs>
                  <b-tab @click="_ => filterComments = null">
                    <template
                      #title
                      data-cy="notification-popover-inbox"
                    >
                      <b-badge
                        v-if="allCount"
                        pill
                        variant="warning lighten"
                      >
                        {{ allCount }}
                      </b-badge>
                      {{ $t('Inbox') }}
                    </template>
                  </b-tab>
                  <b-tab @click="_ => filterComments = false">
                    <template
                      #title
                      data-cy="notification-popover-notifications"
                    >
                      <b-badge
                        v-if="notificationsCount"
                        pill
                        variant="warning lighten"
                      >
                        {{ notificationsCount }}
                      </b-badge>
                      {{ $t('Notifications') }}
                    </template>
                  </b-tab>
                  <b-tab @click="_ => filterComments = true">
                    <template
                      #title
                      data-cy="notification-popover-comments"
                    >
                      <b-badge
                        v-if="commentsCount"
                        pill
                        variant="warning lighten"
                      >
                        {{ commentsCount }}
                      </b-badge>
                      {{ $t('Comments') }}
                    </template>
                  </b-tab>
                </b-tabs>
              </b-col>
              <b-col
                align-self="center"
                cols="auto"
              >
                <a href="/notifications"><i class="fas fa-external-link-alt fa-lg pr-3 external-link" /></a>
              </b-col>
            </b-row>
          </b-container>
          <div
            v-if="messages.length == 0"
            class="no-notifications-mobile"
          >
            <img :alt="$t('All Clear')" src="/img/all-cleared.svg">
            <h2>{{ $t('All Clear') }}</h2>
            <h5>{{ $t('No new notifications at the moment.') }}</h5>
            <b-button variant="primary" href="/notifications">{{ $t('View All Notifications') }}</b-button>
          </div>
          <template v-else>
            <notification-card
              v-for="(item, index) in filteredMessages"
              :key="index"
              :notification="item"
              :show-time="true"
            />
          </template>
        </div>
      </b-popover>
      <b-popover
        v-if="shouldShowPopover"
        id="notification-popover-wrapper"
        target="notification-menu-button"
        placement="bottomleft"
        offset="1"
        triggers="click blur"
        custom-class="notification-popover-wrapper"
        @shown="onShown"
        @hidden="onHidden"
      >
        <div class="notification-popover">
          <b-container
            fluid
            class=""
          >
            <b-row align-h="between">
              <b-col>
                <b-tabs>
                  <b-tab @click="_ => filterComments = null">
                    <template #title>
                      <b-badge
                        v-if="allCount"
                        pill
                        variant="warning lighten"
                      >
                        {{ allCount }}
                      </b-badge>
                      {{ $t('Inbox') }}
                    </template>
                  </b-tab>
                  <b-tab @click="_ => filterComments = false">
                    <template #title>
                      <b-badge
                        v-if="notificationsCount"
                        pill
                        variant="warning lighten"
                      >
                        {{ notificationsCount }}
                      </b-badge>
                      {{ $t('Notifications') }}
                    </template>
                  </b-tab>
                  <b-tab @click="_ => filterComments = true">
                    <template #title>
                      <b-badge
                        v-if="commentsCount"
                        pill
                        variant="warning lighten"
                      >
                        {{ commentsCount }}
                      </b-badge>
                      {{ $t('Comments') }}
                    </template>
                  </b-tab>
                </b-tabs>
              </b-col>
              <b-col
                align-self="center"
                cols="auto"
                class="notification-popover-col"
              >
                <a
                  href="/notifications"
                  data-cy="notification-popover-link"
                >
                  <i class="fas fa-external-link-alt fa-lg pr-3 external-link" />
                </a>
              </b-col>
            </b-row>
          </b-container>
          <div
            v-if="messages.length == 0"
            class="no-notifications"
            data-cy="notification-popover-no-notifications"
          >
            <img :alt="$t('All Clear')" src="/img/all-cleared.svg">
            <h2>{{ $t('All Clear') }}</h2>
            <h5>{{ $t('No new notifications at the moment.') }}</h5>
            <b-button variant="primary" href="/notifications">{{ $t('View All Notifications') }}</b-button>
          </div>
          <div class="items" v-else>
            <notification-item
              v-for="(item, index) in filteredMessages"
              :key="index"
              :notification="item"
              :show-time="true"
            />
          </div>
        </div>
      </b-popover>
    </div>
  </li>
</template>

<script>
import { PopoverPlugin } from "bootstrap-vue";
import NotificationCard from "./notification-card.vue";
import NotificationItem from "./notification-item.vue";
import notificationsMixin from "../notifications-mixin";

Vue.use(PopoverPlugin);
export default {
  components: { NotificationItem, NotificationCard },
  mixins: [notificationsMixin],
  props: {
    messages: Array,
  },
  data() {
    return {
      isOpen: false,
      totalMessages: 0,
      incrementTotalMessages: false,
      arrowStyle: {
        top: "0px",
        left: "0px",
      },
      filterComments: null,
      reloadOnClose: false,
      shouldShowPopover: false,
      shouldShowMobilePopover: false,
    };
  },
  computed: {
    ariaLabel() {
      const count = this.totalMessages;
      if (count === 0) {
        return this.$t("Notifications, No New Messages", { count });
      } else if (count === 1) {
        return this.$t("Notifications, {{count}} New Messages", { count });
      }
      return this.$t("Notifications, {{count}} New Messages", { count });
    },
    buttonVariant() {
      if (this.isOpen) {
        return "primary";
      }
      if (this.hasMessages) {
        return "notification-btn-warning";
      }
      return "link";
    },
    hasMessages() {
      return this.totalMessages > 0;
    },
    displayTotalCount() {
      return this.totalMessages > 10 ? "10+" : this.totalMessages;
    },
  },
  watch: {
    messages(value, mutation) {
      // update the number of messages just whe the number has been initialized (in mounted)
      if (this.incrementTotalMessages) {
        this.updateTotalMessages();
        $(this.$el)
          .find(".dropdown")
          .dropdown("toggle");
      }
    },
  },
  mounted() {
    this.checkScreenWidth();
    window.addEventListener("resize", this.checkScreenWidth);

    if ($("#navbar-request-button").length > 0) {
      this.arrowStyle.top = `${$("#navbar-request-button").offset().top + 45}px`;
      this.arrowStyle.left = `${$("#navbar-request-button").offset().left + 53}px`;

      window.addEventListener("resize", () => {
        this.arrowStyle.top = `${$("#navbar-request-button").offset().top + 42}px`;
        this.arrowStyle.left = `${$("#navbar-request-button").offset().left + 32}px`;
      });
    }
    this.updateTotalMessages();
  },
  methods: {
    checkScreenWidth() {
      const mobileScreenWidth = 768;
      this.shouldShowPopover = window.innerWidth > mobileScreenWidth;
      this.shouldShowMobilePopover = window.innerWidth < mobileScreenWidth;
    },
    onShown() {
      this.isOpen = true;
      this.markAsRead();
    },
    onHidden() {
      this.isOpen = false;
      this.filterComments = null;
      if (this.reloadOnClose) {
        this.updateTotalMessages();
      }
    },
    icon(task) {
      return ProcessMaker.$notifications.icons[task.type];
    },
    url(task) {
      if (task.url) {
        return task.url;
      }
      return "/notifications";
    },
    updateTotalMessages() {
      this.incrementTotalMessages = false;
      ProcessMaker.apiClient
        .get("/notifications?per_page=10&filter=unread&include=user")
        .then((response) => {
          ProcessMaker.notifications.splice(0);
          response.data.data.forEach((element) => {
            ProcessMaker.pushNotification(element);
          });
          this.totalMessages = response.data.meta.total;
          this.$nextTick(() => {
            this.incrementTotalMessages = true;
          });
        });
    },
    markAsRead() {
      if (!this.hasMessages) {
        return;
      }
      const messageIds = this.messages.map((m) => m.id);
      window.ProcessMaker.apiClient.put("/read_notifications", { message_ids: messageIds, routes: [] });
      this.reloadOnClose = true;
    },
  },
};
</script>
<style>
.btn-notification-btn-warning{
    background-color: #FFEABF;
    border-color: #FFEABF;
}
</style>
<style lang="scss" scoped>
@import "../../../sass/variables";

.no-notifications {
  text-align: center;
  height: 600px;

  img {
    width: 190px;
    margin-top: 100px;
    margin-bottom: 20px;
  }
}

.no-notifications-mobile {
  text-align: center;

  img {
    width: 120px;
    margin-top: 48px;
    margin-bottom: 24px;
  }

  h5 {
    margin-bottom: 24px;
  }
}

.custom-popover {
  background-color: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
}

.notification-list {
  max-height: 300px;
  overflow-y: auto;
}

.lighten {
  background-color: lighten($warning, 40%);;
}

.has-messages {
  // background-color: lighten($warning, 20%);
  // border-color: lighten($warning, 20%);
}

.button-dismiss {
  font-size: 12px;
  padding: 0;
}

.notification-popover {
  font-size: 12px;
  width: 450px;
}

.notification-popover-col {
    height: auto !important;
}

.notification-popover-wrapper .popover-body {
  max-width: 450px;
}

.notification-popover::v-deep .tabs {
  .nav-tabs {
    border: 0;
    font-size: 1.2em;
    flex-direction: row;
  }

  .nav-link {
    border: 0;
    color: $secondary;
    border-bottom: 3px solid transparent;
  }

  .nav-link.active {
    color: $primary;
    font-weight: bold;
    border: 0;
    border-bottom: 3px solid $primary;
  }

  .nav-link.hover {
    border: 0;
  }

}

.external-link {
  color: $secondary;
}

.notification-link {
  font-size: 14px;
}

.count-info {
  color: #788793;
}

.count-info .badge {
  font-size: 10px;
  padding: 2px 5px;
  position: absolute;
  right: 88px;
  top: 17px;
}
.count-info #info-large {
  position: absolute;
  right: 83px;
  top: 17px;
}

.popover {
  max-width: 450px;
  top: -8px;
}

.items {
  height: 600px;
  overflow-y: scroll;
  overflow-x: hidden;
}

.notification-menu-button {
  color: $dark;
}
.notification-menu-button i {
  color: $light;
}

.notification-menu-button.is-open,
.notification-menu-button.is-open i {
  color: $light;
}

.bell-icon {
  display: inline-block;
  vertical-align: middle;

  i {
    font-size: 19px;
    padding-right: 1px;
    color: $secondary;
  }
  .dot {
    height: 10px;
    width: 10px;
    background-color: $danger;
    border-radius: 50%;
    position: relative;
    display: inline-block;
    top: -6px;
    margin-left: -10px;
  }

  .message-count {
  }
}

@media only screen and (max-width: 600px) {
  .notification-popover-wrapper.popover {
    transform: translate3d(0px, 0px, 0px) !important;
    right: 0;
    height: auto;
    max-width: 100%;
  }

  .notification-popover {
    width: auto;
  }
}
</style>
