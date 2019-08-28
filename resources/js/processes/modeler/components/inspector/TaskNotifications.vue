<template>
    <div>
        <div class="form-group">
            <div class="notification-settings-group">
                <div class="notification-settings-header">{{ $t('Requester') }}</div>
                <div class="custom-control custom-switch">
                    <input v-model="requesterAssigned" type="checkbox" class="custom-control-input" id="notify-requester-assigned">
                    <label class="custom-control-label" for="notify-requester-assigned">{{ $t('Assigned') }}</label>
                </div>
                <div class="custom-control custom-switch">
                    <input v-model="requesterCompleted"  type="checkbox" class="custom-control-input" id="notify-requester-completed">
                    <label class="custom-control-label" for="notify-requester-completed">{{ $t('Completed') }}</label>
                </div>
                <div class="custom-control custom-switch">
                    <input v-model="requesterDue"  type="checkbox" class="custom-control-input" id="notify-requester-due">
                    <label class="custom-control-label" for="notify-requester-due">{{ $t('Due') }}</label>
                </div>
            </div>
            <div class="notification-settings-group">
                <div class="notification-settings-header">{{ $t('Assignee') }}</div>
                <div class="custom-control custom-switch">
                    <input v-model="assigneeAssigned"  type="checkbox" class="custom-control-input" id="notify-assignee-assigned">
                    <label class="custom-control-label" for="notify-assignee-assigned">{{ $t('Assigned') }}</label>
                </div>
                <div class="custom-control custom-switch">
                    <input v-model="assigneeCompleted"  type="checkbox" class="custom-control-input" id="notify-assignee-completed">
                    <label class="custom-control-label" for="notify-assignee-completed">{{ $t('Completed') }}</label>
                </div>
                <div class="custom-control custom-switch">
                    <input v-model="assigneeDue"  type="checkbox" class="custom-control-input" id="notify-assignee-due">
                    <label class="custom-control-label" for="notify-assignee-due">{{ $t('Due') }}</label>
                </div>
            </div>
            <div class="notification-settings-group">
                <div class="notification-settings-header">{{ $t('Participants') }}</div>
                <div class="custom-control custom-switch">
                    <input v-model="participantsAssigned"  type="checkbox" class="custom-control-input" id="notify-participants-assigned">
                    <label class="custom-control-label" for="notify-participants-assigned">{{ $t('Assigned') }}</label>
                </div>
                <div class="custom-control custom-switch">
                    <input v-model="participantsCompleted"  type="checkbox" class="custom-control-input" id="notify-participants-completed">
                    <label class="custom-control-label" for="notify-participants-completed">{{ $t('Completed') }}</label>
                </div>
                <div class="custom-control custom-switch">
                    <input v-model="participantsDue"  type="checkbox" class="custom-control-input" id="notify-participants-due">
                    <label class="custom-control-label" for="notify-participants-due">{{ $t('Due') }}</label>
                </div>
            </div>
            
            <!-- <table id="table-notifications" class="table">
                <thead>
                    <tr>
                        <th class="notify"></th>
                        <th class="action">Assigned</th>
                        <th class="action">Completed</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="notify">Requester</td>
                        <td class="action">
                            <div class="custom-control custom-switch">
                                <input v-model="notifications.requester.assigned" type="checkbox" class="custom-control-input" id="notify-requester-assigned">
                                <label class="custom-control-label" for="notify-requester-assigned"></label>
                            </div>
                        </td>
                        <td class="action">
                            <div class="custom-control custom-switch">
                                <input v-model="notifications.requester.completed"  type="checkbox" class="custom-control-input" id="notify-requester-completed">
                                <label class="custom-control-label" for="notify-requester-completed"></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="notify">Assignee</td>
                        <td class="action">
                            <div class="custom-control custom-switch">
                                <input v-model="notifications.assignee.assigned"  type="checkbox" class="custom-control-input" id="notify-assignee-assigned">
                                <label class="custom-control-label" for="notify-assignee-assigned"></label>
                            </div>
                        </td>
                        <td class="action">
                            <div class="custom-control custom-switch">
                                <input v-model="notifications.assignee.completed"  type="checkbox" class="custom-control-input" id="notify-assignee-completed">
                                <label class="custom-control-label" for="notify-assignee-completed"></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="notify">Participants</td>
                        <td class="action">
                            <div class="custom-control custom-switch">
                                <input v-model="notifications.participants.assigned"  type="checkbox" class="custom-control-input" id="notify-participants-assigned">
                                <label class="custom-control-label" for="notify-participants-assigned"></label>
                            </div>
                        </td>
                        <td class="action">
                            <div class="custom-control custom-switch">
                                <input v-model="notifications.participants.completed"  type="checkbox" class="custom-control-input" id="notify-participants-completed">
                                <label class="custom-control-label" for="notify-participants-completed"></label>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table> -->
        </div>
    </div>
</template>

<script>

    function notificationTemplate() {
      this.requester = {
        assigned: false,
        completed: false,
      };
      this.assignee = {
        assigned: false,
        completed: false,
      };
      this.participants = {
        assigned: false,
        completed: false,
      };
    };

    export default {
        props: ["value", "label", "helper", "property"],
        data() {
            return {
              content: "",
              users: [],
              requesterAssigned: false,
              requesterCompleted: false,
              requesterDue: false,
              assigneeAssigned: false,
              assigneeCompleted: false,
              assigneeDue: false,
              participantsAssigned: false,
              participantsCompleted: false,              
              participantsDue: false,              
            };
        },
        watch: {
          requesterAssigned: function(value) {
            this.notifications.requester.assigned = value;
          },
          requesterCompleted: function(value) {
            this.notifications.requester.completed = value;
          },
          requesterDue: function(value) {
            this.notifications.requester.due = value;
          },
          assigneeAssigned: function(value) {
            this.notifications.assignee.assigned = value;
          },
          assigneeCompleted: function(value) {
            this.notifications.assignee.completed = value;
          },
          assigneeDue: function(value) {
            this.notifications.assignee.due = value;
          },
          participantsAssigned: function(value) {
            this.notifications.participants.assigned = value;
          },
          participantsCompleted: function(value) {
            this.notifications.participants.completed = value;
          },
          participantsDue: function(value) {
            this.notifications.participants.due = value;
          },
          modelerId: function(newValue, oldValue) {
            this.loadNotifications();
          }
        },
        methods: {
          loadNotifications() {
            this.requesterAssigned = this.notifications.requester.assigned;
            this.requesterCompleted = this.notifications.requester.completed;
            this.requesterDue = this.notifications.requester.due;
            this.assigneeAssigned = this.notifications.assignee.assigned;
            this.assigneeCompleted = this.notifications.assignee.completed;
            this.assigneeDue = this.notifications.assignee.due;
            this.participantsAssigned = this.notifications.participants.assigned;
            this.participantsCompleted = this.notifications.participants.completed;
            this.participantsDue = this.notifications.participants.due;
          }
        },
        computed: {
          process() {
            return this.$parent.$parent.$parent.$parent.$parent.$parent.process;
          },
          modelerId() {
            return this.$parent.$parent.$parent.$parent.highlightedNode._modelerId;
          },
          node() {
            return this.$parent.$parent.$parent.$parent.highlightedNode;
          },
          nodeId() {
            return this.node.definition.id;
          },
          notifications() {
            if (this.node.notifications === undefined) {
              if (this.process.task_notifications[this.nodeId] === undefined) {
                this.node.notifications = new notificationTemplate();
              } else {
                this.node.notifications = this.process.task_notifications[this.nodeId];
              }
            }
            return this.node.notifications;
          }
        },
        mounted() {
          this.loadNotifications();
        }
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
