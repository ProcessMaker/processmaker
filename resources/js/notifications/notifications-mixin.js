export default {
  computed: {
    displayUser() {
      if ('user_id' in this.data) {
        return this.data.user?.fullname || this.$t('Unknown User');
      }
      return null;
    },
    user() {
      return this.notification.data.user || {"name": this.$t('Unknown User')};
    },
    data() {
      return this.notification.data; 
    },
  },
}