const isProd = document.head.querySelector("meta[name=\"is-prod\"]")?.content === "true";

window.ProcessMaker = Object.assign(window.ProcessMaker, {
  /**
     * ProcessMaker Notifications
     */
  notifications: [],
  /**
     * Push a notification.
     *
     * @param {object} notification
     *
     * @returns {void}
     */
  pushNotification(notification) {
    if (this.notifications.filter((x) => x.id === notification).length === 0) {
      this.notifications.push(notification);
    }
  },
  /**
     * Removes notifications by message ids or urls
     *
     * @returns {void}
     * @param messageIds
     *
     * @param urls
     */
  removeNotifications(messageIds = [], urls = []) {
    return window.ProcessMaker.apiClient.put("/read_notifications", { message_ids: messageIds, routes: urls }).then(() => {
      messageIds.forEach((messageId) => {
        ProcessMaker.notifications.splice(ProcessMaker.notifications.findIndex((x) => x.id === messageId), 1);
      });

      urls.forEach((url) => {
        const messageIndex = ProcessMaker.notifications.findIndex((x) => x.url === url);
        if (messageIndex >= 0) {
          ProcessMaker.removeNotification(ProcessMaker.notifications[messageIndex].id);
        }
      });
    });
  },
  /**
     * Mark as unread a list of notifications
     *
     * @returns {void}
     * @param messageIds
     *
     * @param urls
     */
  unreadNotifications(messageIds = [], urls = []) {
    return window.ProcessMaker.apiClient.put("/unread_notifications", { message_ids: messageIds, routes: urls });
  },

  missingTranslations: new Set(),
  missingTranslation(value) {
    if (this.missingTranslations.has(value)) { return; }
    this.missingTranslations.add(value);
    if (!isProd) {
      console.warn("Missing Translation:", value);
    }
  },
  $notifications: {
    icons: {},
  },
});

