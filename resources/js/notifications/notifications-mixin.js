export default {
  computed: {
    displayUser() {
      if ("user_id" in this.data && !this.isInboxRule) {
        return this.data.user?.fullname || this.$t("Unknown User");
      }
      return null;
    },
    user() {
      return this.notification.data.user || { name: this.$t("Unknown User") };
    },
    data() {
      return this.notification.data;
    },
    filteredMessages() {
      return this.messages.filter(this.commentFilterFn(this.filterComments));
    },
    allCount() {
      return this.messages.filter(this.commentFilterFn(null)).length;
    },
    notificationsCount() {
      return this.messages.filter(this.commentFilterFn(false)).length;
    },
    commentsCount() {
      return this.messages.filter(this.commentFilterFn(true)).length;
    },
    isInboxRule() {
      return this.data.type === "INBOX_RULE";
    },
  },
  methods: {
    commentFilterFn(onlyComments) {
      return (message) => {
        if (onlyComments !== null) {
          if (onlyComments) {
            return message.data?.type === "COMMENT";
          }
          return message.data?.type !== "COMMENT";
        }
        return true;
      };
    },
  },
};
