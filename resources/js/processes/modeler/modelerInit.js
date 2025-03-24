export default {};

export const configureTaskNotifications = ({ modeler }) => {
  modeler.$on("node-added", (node) => {
    if (node.type.includes("task") && node.notifications) {
      node.notifications.assignee = {
        assigned: true,
        completed: false,
        due: true,
        default: false,
      };
    }
  });
};

export const onModelerInit = (options) => {
  configureTaskNotifications(options);
};
