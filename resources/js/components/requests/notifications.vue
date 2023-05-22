<template>
  <li class="nav-item d-none d-lg-block" v-cloak>
    <div id="notificationMenu">
      <b-button
        ref="button"
        variant="link"
        class="nav-link count-info"
        data-toggle="dropdown"
        role="button"
        aria-haspopup="menu"
        :aria-expanded="ariaExpanded"
        :title="$t('Notifications')"
        :aria-label="ariaLabel"
      >
        <i class="fas fa-bell fa-lg font-size-23"></i>
        <b-badge pill variant="danger" v-if="totalMessages>0 && totalMessages<=9">{{totalMessages}}</b-badge>
        <b-badge pill variant="danger" v-if="totalMessages>9" id="info-large">9+</b-badge>
      </b-button>
      <b-popover container="#notificationMenu" :target="getTarget" :placement="'bottomleft'" offset="3" triggers="click blur" @shown="onShown" @hidden="onHidden">
        <div class="notification-popover">
          <header class="p-2 border-bottom">
            {{$t('Notifications')}}
          </header>
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

Vue.use(PopoverPlugin);
export default {
  props: {
    messages: Array
  },
  watch: {
    messages(value, mutation) {
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
      ariaExpanded: false,
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
  },
  methods: {
    onShown() {
      this.ariaExpanded = true;
    },
    onHidden() {
      this.ariaExpanded = false;
    },
    getTarget() {
      return this.$refs.button;
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
        .get("/notifications?per_page=5&filter=unread")
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
.button-dismiss {
  font-size: 12px;
  padding: 0;
}

.popover-header {
  font-size: 18px;
  font-weight: 600;
  margin: -12px;
  margin-top: -8px;
  margin-bottom: 18px;
  display: block;
}

.notification-popover {
  font-size: 12px;
  width: 250px;
  
  header {
    font-size: 18px;
  }
  
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
</style>
