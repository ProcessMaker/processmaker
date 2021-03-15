<template>
    <div>
        <div class="form-group"> 
            <label>{{ $t( label ) }}</label>
            <select
                ref="assignmentsDropDownList"
                class="form-control"
                v-model="assignment"
                :disabled="disabled">
                <option v-for="type in assignmentTypes" :key="type.value" :value="type.value">{{ $t(type.label) }}
                </option>
            </select>
            <small class="form-text text-muted">{{$t("Select the Task assignee")}}</small>
        </div>

        <div v-if="!disabled">
          <select-user-group
            v-if="showAssignments"
            :label="$t('Assigned Users/Groups')"
            v-model="assignments"
            :hide-users="hideUsers" 
            :multiple="true" />
          
          <user-by-id
              v-if="showAssignUserById"
              :label="$t('Variable Name')"
              v-model="assigned"
              :helper="$t('Variable containing the numeric User ID')"
          ></user-by-id>

          <self-service-select v-if="showAssignSelfService" 
            v-model="assignments"
          ></self-service-select>

          <assign-expression 
            v-if="showAssignRuleExpression"
            v-model="specialAssignments" 
          />
            
          <form-checkbox
              v-if="configurables.includes('LOCK_TASK_ASSIGNMENT')"
              :label="$t('Lock Task Assignment to User')"
              :checked="assignmentLockGetter"
              toggle="true"
              @change="assignmentLockSetter">
          </form-checkbox>

          <form-checkbox
              v-if="configurables.includes('ALLOW_REASSIGNMENT')"
              :label="$t('Allow Reassignment')"
              :checked="allowReassignmentGetter"
              toggle="true"
              @change="allowReassignmentSetter">
          </form-checkbox>

          <form-checkbox
              v-if="configurables.includes('ESCALATE_TO_MANAGER')"
              :label="$t('Escalte to Manager')"
              :checked="escalateToManagerGetter"
              toggle="true"
              @change="escalateToManagerSetter">
          </form-checkbox>
        </div>
    </div>
</template>

<script>
  import SelfServiceSelect from './SelfServiceSelect.vue';
  import AssignExpression from './AssignExpression.vue';
  import SelectUserGroup from '../../../../components/SelectUserGroup.vue';

  export default {
  components: { SelfServiceSelect, AssignExpression},
    props: {
      value: null,
      label: null,
      helper: null,
      property: null,
      configurables: {
        type: Array,
        default() {
          return ['LOCK_TASK_ASSIGNMENT', 'ALLOW_REASSIGNMENT', 'ASSIGN_BY_EXPRESSION', 'ESCALATE_TO_MANAGER'];
        },
      },
      assignmentTypes: {
        type: Array,
        default() {
          return [
            {
              value: "user_group",
              label: "Users / Groups"
            },
            {
              value: "previous_task_assignee",
              label: "Previous Task Assignee"
            },
            {
              value: "requester",
              label: "Request Starter"
            },
            {
              value: "user_by_id",
              label: "By User ID"
            },
            {
              value: "self_service",
              label: "Self Service"
            },
            {
              value: "rule_expression",
              label: "Rule Expression"
            },
          ];
        },
      },
    },
    data () {
      return {
        assignmentExpression: "",
        typeAssignmentExpression: "",
        specialAssignmentsData: [],
        assignedExpression: null,
        error: "",
        hideUsers: false,
        hideUsersAssignmentExpression: false,
        disabled: false,
      };
    },
    mounted () {
      this.$root.$on('disable-assignment-settings', (val) => {
        this.disabled = val;
      });
    },
    computed: {
      node () {
        return this.$root.$children[0].$refs.modeler.highlightedNode.definition;
      },
      /**
       * Get owner process.
       *
       * @returns {object}
       */
      process () {
        return this.$root.$children[0].process;
      },
      /**
       * Get the value of the edited property
       */
      escalateToManagerGetter () {
        const config = this.node.config && JSON.parse(this.node.config) || {};
        return config.escalateToManager || false;
      },
      assignmentLockGetter () {
        return _.get(this.node, "assignmentLock");
      },
      allowReassignmentGetter () {
        return _.get(this.node, "allowReassignment");
      },
      assignedUserGetter () {
        let value = _.get(this.node, "assignedUsers");
        value = this.unformatIfById(value);
        return value;
      },
      assignedGroupGetter () {
        return _.get(this.node, "assignedGroups");
      },
      assignments: {
        get () {
          let value = [],
          users = this.assignedUserGetter ? this.assignedUserGetter.split(",") : [],
          groups = this.assignedGroupGetter ? this.assignedGroupGetter.split(",") : [];
          value.users = users;
          value.groups = groups;
          return value;
        },
        set (value) {
          const assignedUsers = value.users.map(user => { return user.id ? user.id : user});
          const assignedGroups = value.groups.map(group => {
            if (!group.id) {
              return group;
            } else {
              return group.id.replace("group-", "");
            }
          });
          this.assignedUserSetter(assignedUsers.join(","));
          this.assignedGroupSetter(assignedGroups.join(","));
        }
      },
      specialAssignments: {
        get () {
          const value = this.specialAssignmentsListGetter;
          return value;
        }, 
        set(value) {
          this.assignmentRulesSetter(value);
        }
      },

      assigned: {
        get () {
          let value = "";
          if (this.assignment === "user_by_id") {
            value = this.assignedUserGetter;
          }
          return value;
        },
        set (value) {
          if (this.assignment === "user_by_id" && value) {
            this.assignedUserSetter(value);
            this.assignedGroupSetter("");
          }
        }
      },
      assignment: {
        get () {
          const value = _.get(this.node, "assignment");
          return value;
        },
        set (value) {
          this.$set(this.node, 'assignedUsers', "");
          this.$set(this.node, 'assignedGroups', "");
          this.$set(this.node, "assignment", value);
        }
      },
      showAssignUserById () {
        return this.assignment === "user_by_id";
      },
      showAssignments() {
        this.hideUsers = this.assignment === "self_service";
        const assign = ["user", "group", "user_group"];
        return assign.indexOf(this.assignment) !== -1;
      },
      showAssignSelfService () {
        return this.assignment === "self_service";
      },
      showAssignRuleExpression () {
        return this.assignment === 'rule_expression';
      },
      specialAssignmentsListGetter () {
        const value = this.node.get("assignmentRules") || "[]";
        return JSON.parse(value);
      },
    },
    methods: {
      /**
       * Update escalateToManager property
       */
      escalateToManagerSetter (value) {
        const config = this.node.config && JSON.parse(this.node.config) || {};
        config.escalateToManager = value;
        this.$set(this.node, "config", JSON.stringify(config));
      },
      /**
       * Update assignmentLock property
       */
      assignmentLockSetter (value) {
        this.$set(this.node, "assignmentLock", value);
      },
      /**
       * Update allowReassignment property
       */
      allowReassignmentSetter (value) {
        this.$set(this.node, "allowReassignment", value);
      },
      /**
       * Update the event of the editer property
       */
      assignedUserSetter (id) {
        let value = this.formatIfById(id);
        this.$set(this.node, "assignedUsers", value);
      },
      assignedGroupSetter (id) {
        let node = this.node;
        this.$set(node, "assignedGroups", id);
      },
      formatIfById (val) {
        if (this.assignment === "user_by_id") {
          return `{{ ${val} }}`;
        }
        return val;
      },
      unformatIfById (val) {
        if (this.assignment === "user_by_id") {
          try {
            return val.match(/^{{ (.*) }}$/)[1];
          } catch (e) {
            return "";
          }
        }
        return val;
      },
      assignmentRulesSetter (value) {
        this.$set(this.node, "assignmentRules", JSON.stringify(value));
      },
    },
    watch: {
      assigned: {
        handler (value) {
          if (this.assignment === "user" && value) {
            this.assignedUserSetter(value);
          } else if ((this.assignment === "group" || this.assignment === "self_service") && value) {
            this.assignedGroupSetter(value);
          }
        }
      },
      assignment: {
        handler (assigned) {
          this.assignments.groups = [];
          this.assignments.users = [];
          let value = "";
          if (assigned === "user") {
            value = this.assignedUserGetter;
          } else if (assigned === "group" || assigned === "self_service") {
            value = this.assignedGroupGetter;
          }
          this.assigned = value;
        }

      },
    }
  };
</script>


<style lang="scss" scoped>
    $transition: .25s;

    .list-users-groups {
        width: 100%;
        height: 24em;
        overflow-y: auto;
        font-size: 0.75rem;
        background: white;
    }

    .list-users-groups.small {
        height: 8em;
    }

    .special-assignment-header {
        label {
            padding-top: 4px;
        }
    }

    .special-assignment-wrapper {
        width: 100%;
        height: 0;
        opacity: 0;
        overflow: hidden;
        transition: height $transition, opacity $transition;
    }

    .special-assignment-form {
        background-color: #fff;
        border: 1px solid rgba(0, 0, 0, 0.125);
        padding: 4px 9px 2px 9px;
        margin-bottom: 9px;
    }

    .form-group-actions {
        text-align: right;
    }

    .btn-special-assignment-action {
        border-radius: 2px;
        font-size: 12px;
        font-weight: bold;
        padding: 2px 4px;
        transition: opacity $transition;
        &.inactive {
            opacity: 0;
        }
    }

    .list-group-item {
        padding: 0;
        margin-bottom: 9px;

        .btn-special-assignment-delete {
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,1) 75%);
            border: 0;
            color: gray;
            cursor: pointer;
            margin: 0;
            outline: 0;
            padding: 4px 4px 4px 60px;
            position: absolute;
            right: 0;
            top: 0;

            &:hover {
                color: #ed4757;
            }
        }

        &:hover {
            .btn-special-assignment-delete {
                background: linear-gradient(90deg, rgba(247,248,249,0) 0%, rgba(247,248,249,1) 75%);
            }
        }
    }

    .special-assignment-section {
        padding: 4px 1px 4px 4px;

        &:first-child {
            border-bottom: 1px solid #eee;
        }
    }

    .special-assignment-value {
        overflow: hidden;
        white-space: nowrap;
    }

    .btn-special-assignment-close {
        margin-right: 9px;
    }

    .fa-trash {
        cursor: pointer;
    }

    .form-group {
        padding: 0px;
    }
</style>

