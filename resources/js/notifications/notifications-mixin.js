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
    filteredMessages() {
      return this.messages.filter(this.commentFilterFn(this.filterComments));
    },
    notifications() {
      return this.messages.filter(this.commentFilterFn(false));
    },
    comments() {
      return this.messages.filter(this.commentFilterFn(true));
    }
  },
  methods: {
    commentFilterFn(onlyComments) {
      return (message) => {
        if (onlyComments !== null) {
          if (onlyComments) {
            return message.data?.type === "COMMENT";
          } else {
            return message.data?.type !== "COMMENT";
          }
        }
        return true;
      }
    },
  }
}