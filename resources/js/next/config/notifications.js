import { setGlobalPMVariables, getGlobalPMVariable } from "../globalVariables";

export default () => {
  const apiClient = getGlobalPMVariable("apiClient");

  const notifications = [];

  const pushNotification = (notification) => {
    if (notifications.filter((x) => x.id === notification).length === 0) {
      notifications.push(notification);
    }
  };

  const removeNotifications = (messageIds = [], urls = []) => apiClient.put("/read_notifications", { message_ids: messageIds, routes: urls }).then(() => {
    messageIds.forEach((messageId) => {
      notifications.splice(notifications.findIndex((x) => x.id === messageId), 1);
    });

    urls.forEach((url) => {
      const messageIndex = notifications.findIndex((x) => x.url === url);
      if (messageIndex >= 0) {
        removeNotifications(notifications[messageIndex].id);
      }
    });
  });

  const unreadNotifications = (messageIds = [], urls = []) => apiClient.put("/unread_notifications", { message_ids: messageIds, routes: urls });

  const $notifications = {
    icons: {},
  };

  setGlobalPMVariables({
    notifications,
    pushNotification,
    removeNotifications,
    unreadNotifications,
    $notifications,
  });
};
