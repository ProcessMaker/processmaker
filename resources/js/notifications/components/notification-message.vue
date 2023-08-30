<template>
  <b-col>
    <div v-html="message"></div>
    <notification-time v-if="showTime" :notification="notification"></notification-time>
    <div v-if="displayBubble" class="bubble">
      {{ displayBubble }}
    </div>
  </b-col>
</template>

<script>
import NotificationTime from './notification-time';
import notificationsMixin from '../notifications-mixin';
import moment from "moment";

const messages = {
  'TASK_CREATED': '{{user}} has been assigned to the task {{subject}} in the process {{processName}}',
  'TASK_COMPLETED': 'Task {{subject}} completed by {{user}}',
  'TASK_REASSIGNED': 'Task {{subject}} reassigned to {{user}}',
  'TASK_OVERDUE': 'Task {{subject}} is overdue. Originally due on {{due}}',
  'PROCESS_CREATED': '{{user}} started the process {{subject}}',
  'PROCESS_COMPLETED': '{{subject}} completed',
  'ERROR_EXECUTION': '{{subject}} caused an error',
  'COMMENT': '{{user}} commented on {{subject}}',
  'ProcessMaker\\Notifications\\ImportReady': 'Imported {{subject}}'
}
export default {
  mixins: [ notificationsMixin ],
  components: {
    NotificationTime
  },
  props: {
    notification: {
      type: Object,
      required: true,
    },
    showTime: {
      type: Boolean,
      default: false,
    }
  },
  computed: {
    message() {
      let message = messages[this.data.type] || messages[this.notification.type] || this.data.message;
      message = this.$t(message, this.bindings);
      return this.format(message);
    },
    bindings() {
      return {
        user: this.displayUser,
        subject: this.displaySubject,
        processName: this.data.processName || this.$t('Unknown Process'),
        due: this.data.due_in ? moment(this.data.due_in).format() : null
      }
    },
    displaySubject() {
      return this.data.name || this.data.processName || ''
    },
    displayBubble()
    {
      switch(this.data.type) {
        case "COMMENT":
          return this.data.message.substring(0, 200);
      }
    },
  },
  methods: {
    format(message) {
      Object.values(this.bindings).forEach((value) => {
        message = message.replace(value, `<strong>${value}</strong>`);
      });
      return message;
    }
  }
}
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";
.bubble {
  background-color: lighten($primary, 55%);
  border-radius: 1em;
  padding: 1em;
  margin-top: 1em;
}
</style>