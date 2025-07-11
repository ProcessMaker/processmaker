export default {};

// Highlight the node when it is added
// TODO: This is a workaround to highlight the node when it is added
// because the highlightNode method is not working when the node is added
export const configureTaskNotifications = ({ modeler }) => {
  modeler.$on("before-node-added", (node) => {
    if (node.type.includes("task") && !node.notifications) {
      node.notifications = {
        assignee: {
          assigned: true,
          completed: false,
          due: true,
          default: false,
        },
        requester : {
          assigned: false,
          completed: false,
          due: false,
        },
        participants : {
          assigned: false,
          completed: false,
          due: false,
        },
        manager : {
          assigned: false,
          completed: false,
          due: false,
        },
      };
    }

    if (node.type.includes("task") && !node.config) {
      node.config = {
        email_notifications: {},
      };
    }
  });
};

export const onModelerInit = (options) => {
  configureTaskNotifications(options);
};
