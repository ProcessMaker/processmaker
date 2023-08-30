<template>
  <b-col>
    <strong v-if="displayUser !== null">{{ displayUser }}</strong> {{ displayAction }} <strong>{{ displaySubject }}</strong>
    <template v-if="displayAdditional">
      {{ displayAdditional[0] }} <strong>{{ displayAdditional[1] }}</strong>
    </template>
    <notification-time v-if="showTime" :notification="notification"></notification-time>
    <div v-if="displayBubble" class="bubble">
      {{ displayBubble }}
    </div>
  </b-col>
</template>

<script>
import NotificationTime from './notification-time';
import notificationsMixin from '../notifications-mixin';
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
    displayAction() {
      switch(this.data.type) {
        case "TASK_CREATED":
          return this.$t('has been assigned to the task');
        case "PROCESS_CREATED":
          return this.$t('started the process');
        case "COMMENT":
          return this.$t('commented on');
        default:
          return this.data.name || ''
      }
      return null;
    },
    displaySubject() {
      switch(this.data.type) {
        case "TASK_CREATED":
          return this.data.name;
        case "PROCESS_CREATED":
          return this.data.uid;
        case "COMMENT":
          return this.data.processName;
        default:
          return this.data.processName || ''
      }
      return null;
    },
    displayAdditional() {
      switch(this.data.type) {
        case "TASK_CREATED":
          return [this.$t('in the process'), this.data.processName];
      }
      return null;
    },
    displayBubble()
    {
      switch(this.data.type) {
        case "COMMENT":
          return this.data.message.substring(0, 200);
      }
    },
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