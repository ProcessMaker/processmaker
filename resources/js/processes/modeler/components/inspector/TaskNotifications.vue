<template>
  <div>
    <div class="form-group">
      <div class="notification-settings-group">
        <div class="notification-settings-header">
          {{ $t('Requester') }}
        </div>
        <div class="custom-control custom-switch">
          <input
            id="notify-requester-assigned"
            v-model="requesterAssigned"
            type="checkbox"
            class="custom-control-input"
          >
          <label
            class="custom-control-label"
            for="notify-requester-assigned"
          >{{ $t('Assigned') }}</label>
        </div>
        <div class="custom-control custom-switch">
          <input
            id="notify-requester-completed"
            v-model="requesterCompleted"
            type="checkbox"
            class="custom-control-input"
          >
          <label
            class="custom-control-label"
            for="notify-requester-completed"
          >{{ $t('Completed') }}</label>
        </div>
        <div class="custom-control custom-switch">
          <input
            id="notify-requester-due"
            v-model="requesterDue"
            type="checkbox"
            class="custom-control-input"
          >
          <label
            class="custom-control-label"
            for="notify-requester-due"
          >{{ $t('Due') }}</label>
        </div>
      </div>
      <div class="notification-settings-group">
        <div class="notification-settings-header">
          {{ $t('Assignee') }}
        </div>
        <div class="custom-control custom-switch">
          <input
            id="notify-assignee-assigned"
            v-model="assigneeAssigned"
            type="checkbox"
            class="custom-control-input"
          >
          <label
            class="custom-control-label"
            for="notify-assignee-assigned"
          >{{ $t('Assigned') }}</label>
        </div>
        <div class="custom-control custom-switch">
          <input
            id="notify-assignee-completed"
            v-model="assigneeCompleted"
            type="checkbox"
            class="custom-control-input"
          >
          <label
            class="custom-control-label"
            for="notify-assignee-completed"
          >{{ $t('Completed') }}</label>
        </div>
        <div class="custom-control custom-switch">
          <input
            id="notify-assignee-due"
            v-model="assigneeDue"
            type="checkbox"
            class="custom-control-input"
          >
          <label
            class="custom-control-label"
            for="notify-assignee-due"
          >{{ $t('Due') }}</label>
        </div>
      </div>
      <div class="notification-settings-group">
        <div class="notification-settings-header">
          {{ $t('Participants') }}
        </div>
        <div class="custom-control custom-switch">
          <input
            id="notify-participants-assigned"
            v-model="participantsAssigned"
            type="checkbox"
            class="custom-control-input"
          >
          <label
            class="custom-control-label"
            for="notify-participants-assigned"
          >{{ $t('Assigned') }}</label>
        </div>
        <div class="custom-control custom-switch">
          <input
            id="notify-participants-completed"
            v-model="participantsCompleted"
            type="checkbox"
            class="custom-control-input"
          >
          <label
            class="custom-control-label"
            for="notify-participants-completed"
          >{{ $t('Completed') }}</label>
        </div>
        <div class="custom-control custom-switch">
          <input
            id="notify-participants-due"
            v-model="participantsDue"
            type="checkbox"
            class="custom-control-input"
          >
          <label
            class="custom-control-label"
            for="notify-participants-due"
          >{{ $t('Due') }}</label>
        </div>
      </div>
      <div class="notification-settings-group">
        <div class="notification-settings-header">
          {{ $t('Process Manager') }}
        </div>
        <div class="custom-control custom-switch">
          <input
            id="notify-manager-assigned"
            v-model="managerAssigned"
            type="checkbox"
            class="custom-control-input"
          >
          <label
            class="custom-control-label"
            for="notify-manager-assigned"
          >{{ $t('Assigned') }}</label>
        </div>
        <div class="custom-control custom-switch">
          <input
            id="notify-manager-completed"
            v-model="managerCompleted"
            type="checkbox"
            class="custom-control-input"
          >
          <label
            class="custom-control-label"
            for="notify-manager-completed"
          >{{ $t('Completed') }}</label>
        </div>
        <div class="custom-control custom-switch">
          <input
            id="notify-manager-due"
            v-model="managerDue"
            type="checkbox"
            class="custom-control-input"
          >
          <label
            class="custom-control-label"
            for="notify-manager-due"
          >{{ $t('Due') }}</label>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

class NotificationTemplate {
  constructor(type) {
    this.requester = {
      assigned: false,
      completed: false,
      due: false,
    };
    this.assignee = {
      assigned: type === "task",
      completed: false,
      due: type === "task",
      default: false,
    };
    this.participants = {
      assigned: false,
      completed: false,
      due: false,
    };
    this.manager = {
      assigned: false,
      completed: false,
      due: false,
    };
  }
}

export default {
  props: ["value", "label", "helper", "property"],
  data() {
    return {
      notifications: [],
      content: "",
      users: [],
      requesterAssigned: false,
      requesterCompleted: false,
      requesterDue: false,
      assigneeAssigned: true,
      assigneeCompleted: false,
      assigneeDue: true,
      participantsAssigned: false,
      participantsCompleted: false,
      participantsDue: false,
      managerAssigned: false,
      managerCompleted: false,
      managerDue: false,
      defaultNotification: false,
    };
  },
  computed: {
    type() {
      return this.node.type;
    },
    process() {
      return this.$root.$children[0].process;
    },
    modelerId() {
      return this.$root.$children[0].$refs.modeler.highlightedNode._modelerId;
    },
    node() {
      return this.$root.$children[0].$refs.modeler.highlightedNode;
    },
    nodeId() {
      return this.node.definition.id;
    },

  },
  watch: {
    requesterAssigned(value) {
      if (this.notifications) {
        this.notifications.requester.assigned = value;
        this.verifyNotifications();
      }
    },
    requesterCompleted(value) {
      if (this.notifications) {
        this.notifications.requester.completed = value;
        this.verifyNotifications();
      }
    },
    requesterDue(value) {
      if (this.notifications) {
        this.notifications.requester.due = value;
        this.verifyNotifications();
      }
    },
    assigneeAssigned(value) {
      if (this.notifications) {
        this.notifications.assignee.assigned = value;
        this.verifyNotifications();
      }
    },
    assigneeCompleted(value) {
      if (this.notifications) {
        this.notifications.assignee.completed = value;
        this.verifyNotifications();
      }
    },
    assigneeDue(value) {
      if (this.notifications) {
        this.notifications.assignee.due = value;
        this.verifyNotifications();
      }
    },
    participantsAssigned(value) {
      if (this.notifications) {
        this.notifications.participants.assigned = value;
        this.verifyNotifications();
      }
    },
    participantsCompleted(value) {
      if (this.notifications) {
        this.notifications.participants.completed = value;
        this.verifyNotifications();
      }
    },
    participantsDue(value) {
      if (this.notifications) {
        this.notifications.participants.due = value;
        this.verifyNotifications();
      }
    },
    managerAssigned(value) {
      if (this.notifications) {
        this.notifications.manager.assigned = value;
        this.verifyNotifications();
      }
    },
    managerCompleted(value) {
      if (this.notifications) {
        this.notifications.manager.completed = value;
        this.verifyNotifications();
      }
    },
    managerDue(value) {
      if (this.notifications) {
        this.notifications.manager.due = value;
        this.verifyNotifications();
      }
    },
    modelerId() {
      this.loadNotifications();
    },
  },
  mounted() {
    this.updateNotifications();
    this.loadNotifications();
  },
  methods: {
    loadNotifications() {
      this.requesterAssigned = this.notifications?.requester.assigned;
      this.requesterCompleted = this.notifications?.requester.completed;
      this.requesterDue = this.notifications?.requester.due;
      this.assigneeAssigned = this.notifications?.assignee.assigned;
      this.assigneeCompleted = this.notifications?.assignee.completed;
      this.assigneeDue = this.notifications?.assignee.due;
      this.participantsAssigned = this.notifications?.participants.assigned;
      this.participantsCompleted = this.notifications?.participants.completed;
      this.participantsDue = this.notifications?.participants.due;
      this.managerAssigned = this.notifications?.manager.assigned;
      this.managerCompleted = this.notifications?.manager.completed;
      this.managerDue = this.notifications?.manager.due;
      this.defaultNotification = this.notifications?.assignee.default;
    },
    updateNotifications() {
      if (this.process.task_notifications[this.nodeId]) {
        this.node.notifications = this.process.task_notifications[this.nodeId];
      } else if (this.node.notifications === undefined) {
        const newNotifications = this.createNewNotification();
        this.node.notifications = newNotifications;
      }
      this.notifications = this.node.notifications;
    },
    createNewNotification() {
      let type = "task";
      const cloneOf = this.getNode(this.node.cloneOf);
      if (this.node.cloneOf && cloneOf) {
        return structuredClone(cloneOf.notifications);
      }
      if (this.node.type === "processmaker-modeler-manual-task") {
        type = "manual_task";
      }
      return new NotificationTemplate(type);
    },
    getNode(nodeId) {
      return this.$root.$children[0].$refs.modeler.nodes.find((node) => node.definition.id === nodeId);
    },
    /**
     * Verify if there are any notifications enabled to set a default value in true
     */
    verifyNotifications() {
      let flag = false;

      for (const prop in this.notifications) {
        if (this.notifications[prop].assigned || this.notifications[prop].completed || this.notifications[prop].due) {
          flag = true;
          break;
        }
      }

      this.notifications.assignee.default = !flag;
    },
  },
};
</script>

<style lang="scss" scoped>
  .notification-settings-group {
    margin-bottom: 10px;
  }
  .notification-settings-header {
    font-weight: bold;
  }
  .custom-control-label {
    margin-bottom: 0;
    padding-top: 3px;
  }
  .form-group {
    padding: 0px;
  }
</style>
