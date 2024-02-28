<template>
  <b-col>
    <div
      class="message-title"
      :class="{ 'message-sm': isMobile }"
      v-html="message"
    />
    <notification-inbox-rule
      v-if="isInboxRule"
      :data="data"
    />
    <notification-time
      v-if="showTime && !isInboxRule"
      :notification="notification"
    />
    <div
      v-if="displayBubble"
      class="bubble"
      :class="{ 'bubble-sm': isMobile }"
    >
      {{ displayBubble }}
    </div>
  </b-col>
</template>

<script>
import moment from "moment";
import NotificationTime from "./notification-time";
import notificationsMixin from "../notifications-mixin";
import NotificationInboxRule from "./notification-inbox-rule";

const messages = {
  TASK_CREATED: "{{- user }} has been assigned to the task {{- subject }} in the process {{- processName }}",
  TASK_COMPLETED: "Task {{- subject }} completed by {{- user }}",
  TASK_REASSIGNED: "Task {{- subject }} reassigned to {{- user }}",
  TASK_OVERDUE: "Task {{- subject }} is overdue. Originally due on {{- due }}",
  PROCESS_CREATED: "{{- user}} started the process {{- subject }}",
  PROCESS_COMPLETED: "{{- subject }} completed",
  ERROR_EXECUTION: "{{- subject }} caused an error",
  COMMENT: "{{- user}} commented on {{- subject}}",
  "ProcessMaker\\Notifications\\ImportReady": "Imported {{- subject }}",
};
export default {
  components: {
    NotificationTime,
    NotificationInboxRule,
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
      isMobile: false,
    };
  },
  computed: {
    message() {
      if (this.isInboxRule) {
        return this.$t('Inbox rules <strong>handled {{ruleCount}} tasks</strong> since your last login', { ruleCount: this.ruleCount });
      }
      const message = messages[this.data.type] || messages[this.notification.type] || this.data.message;
      return this.$t(message, this.bindings);
    },
    bindings() {
      return {
        user: `<strong>${this.displayUser}</strong>`,
        subject: `<strong>${this.displaySubject}</strong>`,
        processName: `<strong>${this.data.processName || this.$t("Unknown Process")}</strong>`,
        due: `<strong>${this.data.due_at ? moment(this.data.due_at).format() : null}</strong>`,
      };
    },
    displaySubject() {
      return this.data.name || this.data.processName || "";
    },
    displayBubble() {
      switch (this.data.type) {
        case "COMMENT":
          return this.data.message.substring(0, 200);
      }
    },
    ruleCount() {
      return this.data.message?.total
    },
  },
  mounted() {
    this.isMobile = window.innerWidth < 768;
  },
  methods: {
  },
};
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";

.message-title {
  font-size: 1.2em;
}
.message-sm {
  font-size: 0.8em;
  margin-bottom: 1em;
}
.bubble {
  background-color: lighten($primary, 55%);
  border-radius: 1em;
  padding: 1em;
  margin-top: 1em;
}
.bubble-sm {
  font-size: 0.8em;
  background-color: lighten($primary, 55%);
  border-radius: 1em;
  padding: 1em;
  margin-top: 1em;
  margin-bottom: 1em;
}
</style>
