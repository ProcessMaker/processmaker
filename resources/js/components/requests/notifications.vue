<template>
  <li class="nav-item d-none d-lg-block" v-cloak>
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
          <i class="fas fa-bell"></i>
          <span class="dot" v-if="hasMessages"></span>
        </div>
        <span class="message-count" v-if="hasMessages">{{totalMessages}}</span>
      </b-button>
      <b-popover target="notification-menu-button" placement="bottomleft" offset="1" triggers="click" @shown="onShown" @hidden="onHidden">
        
        <div class="notification-popover">

          <b-container fluid class="">
            <b-row align-h="between">
              <b-col>
                <b-tabs>
                  <b-tab>
                    <template #title>
                      <b-badge pill variant="warning">5</b-badge>
                      {{ $t('Inbox') }}
                    </template>
                  </b-tab>
                  <b-tab :title="$t('Notifications')"></b-tab>
                  <b-tab :title="$t('Comments')"></b-tab>
                </b-tabs>
              </b-col>
              <b-col align-self="center" lg="auto">
                <i class="fas fa-external-link-alt fa-lg pr-3 external-link"></i>
              </b-col>
            </b-row>
          </b-container>
          
          <notification-item v-for="(item, index) in messages" :key="index" :notification="item"></notification-item>

          <div class="p-2" v-if="messages.length == 0">
            {{ $t('No Notifications Found') }}
          </div>

          <ul v-else class="notification-list list-unstyled m-2">
            <li class="py-2 border-bottom" v-for="(task, index) in messages" :key="`message-${index}`">
              <div v-if="index <= 5">
                <div>
                  <a class="notification-link text-primary" href="#" @click.stop="remove(task, task.url)">{{task.name}}</a>
                  <div class="text-muted" v-if="task.processName && task.userName">
                    {{task.processName}}
                    <br>
                    {{task.userName}}
                  </div>
                </div>
                
                <div class="d-flex align-items-center justify-content-end mt-2">
                  <small class="muted" v-b-tooltip.hover :title="moment(task.created_at).format()">{{ moment(task.created_at).fromNow() }}</small>
                  <b-button
                    variant="link"
                    class="float-right ml-2 button-dismiss"
                    @click="remove(task)"
                    v-b-tooltip.hover
                    :title="$t('Dismiss Alert')"
                  ><i class="fa fa-trash"></i></b-button>
                </div>
              </div>
            </li>
          </ul>
          <footer class="p-2 border-top footer d-flex justify-content-end">
            <b-button
              variant="outline-secondary"
              size="sm"
              v-if="messages.length != 0"
              @click="removeAll"
            >{{$t('Dismiss All')}}</b-button>
            <b-button
              class="ml-auto"
              variant="secondary"
              size="sm"
              href="/notifications"
            >{{$t('View All')}}</b-button>
          </footer>
        </div>
      </b-popover>
    </div>
  </li>
</template>

<script>
import moment from "moment";
import { PopoverPlugin } from "bootstrap-vue"
import NotificationItem from "./notification-item.vue";

Vue.use(PopoverPlugin);
export default {
  components: { NotificationItem },
  props: {
    messages: Array
  },
  watch: {
    messages(value, mutation) {
      console.log("GOT MESSAGES UPDATED", value)
      //update the number of messages just whe the number has been initialized (in mounted)
      if (this.incrementTotalMessages) {
        this.updateTotalMessages();
        $(this.$el)
          .find(".dropdown")
          .dropdown("toggle");
      }
    }
  },
  data() {
    return {
      isOpen: false,
      totalMessages: 0,
      incrementTotalMessages: false,
      arrowStyle: {
        top: "0px",
        left: "0px"
      }
    };
  },
  computed: {
    ariaLabel() {
      let count = this.totalMessages;
      if (count === 0) {
        return this.$t('Notifications, No New Messages', {count});
      } else if (count === 1) {
        return this.$t('Notifications, {{count}} New Messages', {count});
      } else {
        return this.$t('Notifications, {{count}} New Messages', {count});
      }
    },
    buttonVariant() {
      if (this.isOpen) {
        return "primary"
      }
      if (this.hasMessages) {
        return "warning";
      }
      return "link";
    },
    hasMessages() {
      return this.totalMessages > 0;
    }
  },
  methods: {
    onShown() {
      this.isOpen = true;
    },
    onHidden() {
      this.isOpen = false;
    },
    icon(task) {
      return ProcessMaker.$notifications.icons[task.type];
    },
    url(task) {
      if (task.url) {
        return task.url;
      } else {
        return '/notifications';
      }
    },
    updateTotalMessages() {
      this.incrementTotalMessages = false;
      ProcessMaker.apiClient
        .get("/notifications?per_page=5&filter=unread&include=user")
        .then(response => {
          ProcessMaker.notifications.splice(0);
          response.data.data.forEach(function(element) {
            ProcessMaker.pushNotification(element);
          });
          this.totalMessages = response.data.meta.total;
          this.$nextTick(() => {
            this.incrementTotalMessages = true;
          });
        });
    },
    remove(message, redirectTo = null) {
      ProcessMaker.removeNotifications([message.id]).then(() => {
        if (this.totalMessages > 0) {
          this.totalMessages--;
        }
        if (redirectTo) {
          window.location.href = redirectTo
        }
      });
    },
    formatDateTime(iso8601) {
      return moment(iso8601).format("MM/DD/YY HH:mm");
    },
    removeAll() {
      let that = this;
      //Remove all notification of current user
      ProcessMaker.apiClient
        .put("/read_all_notifications", {
          id: ProcessMaker.user.id,
          type: "ProcessMaker\\Models\\User"
        })
        .then(() => {
          ProcessMaker.notifications.splice(
            0,
            ProcessMaker.notifications.length
          );
          that.totalMessages = 0;
        });
    }
  },
  mounted() {
    if ($("#navbar-request-button").length > 0) {
      this.arrowStyle.top = $("#navbar-request-button").offset().top + 45 + "px";
      this.arrowStyle.left =
        $("#navbar-request-button").offset().left + 53 + "px";

      window.addEventListener("resize", () => {
        this.arrowStyle.top =
          $("#navbar-request-button").offset().top + 42 + "px";
        this.arrowStyle.left =
          $("#navbar-request-button").offset().left + 32 + "px";
      });
    }
    this.updateTotalMessages();
  }
};
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";


.button-dismiss {
  font-size: 12px;
  padding: 0;
}

.notification-popover {
  font-size: 12px;
  width: 450px;

  footer {
    text-transform: uppercase;
  }
}
.notification-list {
  li:first-child {
    padding-top: 0 !important;
  }
  
  li:last-child {
    border-bottom: 0 !important;
    padding-bottom: 0 !important;
  }
}

.notification-popover::v-deep .tabs {
  .nav-tabs {
    border: 0;
    font-size: 1.1em;
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
  height: 600px;
  top: -8px;
}

.notification-menu-button {
}
.notification-menu-button i {
  color: $secondary; 
}

.notification-menu-button.has-messages i {
  color: $light;
}

.notification-menu-button.is-open i {
  color: $light;
}

.bell-icon {
  display: inline-block;
  vertical-align: middle;

  i {
    font-size: 19px;
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
</style>
