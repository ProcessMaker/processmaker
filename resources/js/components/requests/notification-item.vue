<template>
  <div class="item border-top py-3">
    <b-container>
      <b-row>
        <b-col v-if="user" lg="auto" class="pr-0">
          <avatar-image size="40" hide-name="true" :input-data="user"></avatar-image>
        </b-col>
        <b-col>
          <strong>{{ displayUser }}</strong> {{ displayAction }} <strong>{{ displaySubject }}</strong>
          <template v-if="displayAdditional">
            {{ displayAdditional[0] }} <strong>{{ displayAdditional[1] }}</strong>
          </template>
          <div>
            <span class="text-muted" v-b-tooltip.hover :title="moment(time).format()">{{ moment(time).fromNow() }}</span>
          </div>
          <div v-if="displayBubble" class="bubble">
            {{ displayBubble }}
          </div>
        </b-col>
      </b-row>
    </b-container>
  </div>
</template>

<script>
import AvatarImage from '../AvatarImage.vue';
export default {
  components: { AvatarImage },
  props: {
    notification: {
      type: Object,
      required: true,
    }
  },
  data() {
    return {
    }
  },
  computed: {
    user() {
      return this.notification.data.user;
    },
    data() {
      return this.notification.data; 
    },
    displayUser() {
      return this.data.user?.fullname || this.$t('Unknown User');
    },
    displayAction() {
      switch(this.data.type) {
        case "TASK_CREATED":
          return this.$t('has been assigned to the task');
        case "PROCESS_CREATED":
          return this.$t('started the process');
        case "COMMENT":
          return this.$t('commented on');
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
    time() {
      return this.notification.created_at;
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