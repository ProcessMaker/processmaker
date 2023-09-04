<template>
  <div>
    <span class="text-muted" v-b-tooltip.hover :title="time">{{ formattedTime }}</span>
  </div>
</template>

<script>
import moment from "moment";

export default {
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
    time() {
      return this.notification.created_at;
    },
    formattedTime() {
      let d = new Date(this.time);
      let formatted = moment(d).format();

      // Check if its the same day
      if (d.setHours(0,0,0,0) !== (new Date()).setHours(0,0,0,0)) {
        // return the day
        return formatted.split(' ')[0];
        
      } else {
        // return the time
        return formatted.split(' ')[1];
      }
    },
  }
}
</script>
