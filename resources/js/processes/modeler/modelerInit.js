import { nextTick } from "vue";

export default {};

// Highlight the node when it is added
// TODO: This is a workaround to highlight the node when it is added
// because the highlightNode method is not working when the node is added
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
    modeler.clearSelection();
    nextTick(() => {
      modeler.highlightNode(node);
    });
  });
};

export const onModelerInit = (options) => {
  configureTaskNotifications(options);
};
