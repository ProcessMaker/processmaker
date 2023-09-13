<template>
  <div>
    <span
      v-b-tooltip.hover
      class="text-muted"
      :title="time"
    >{{ formatDate }}</span>
  </div>
</template>

<script>

export default {
  props: {
    notification: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
    };
  },
  computed: {
    time() {
      return this.notification.created_at;
    },
    formatDate() {
      const months = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December",
      ];

      const dateObj = new Date(this.time);
      const currentDate = new Date();

      if (
        dateObj.getDate() === currentDate.getDate()
                && dateObj.getMonth() === currentDate.getMonth()
                && dateObj.getFullYear() === currentDate.getFullYear()
      ) {
        const hours = dateObj.getHours();
        const minutes = dateObj.getMinutes();
        return `${hours}:${minutes}`;
      }
      const month = dateObj.getMonth();
      const day = dateObj.getDate();
      const formattedMonth = months[month];
      return `${formattedMonth} ${day}`;
    },
  },
};
</script>
