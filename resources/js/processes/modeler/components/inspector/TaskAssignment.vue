<template>
    <div>
        <div class="form-group"> 
            <label for="assignmentsDropDownList">{{ $t( label ) }}</label>
            <select id="assignmentsDropDownList"
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

          <div v-if="showAssignByVariable">
            <label class="mt-1">{{ $t('Variable Name (Users)') }}</label>
            <b-form-input v-model="assignedUsersVar" />
            <small class="form-text text-muted">{{$t("Enter the variable containing one or more numeric user IDs")}}</small>

            <label class="mt-2">{{ $t('Variable Name (Groups)') }}</label>
            <b-form-input v-model="assignedGroupsVar" />
            <small v-if="helper" class="form-text text-muted" >{{ $t(helper) }}</small>

            <small class="form-text text-muted">{{$t("Enter the variable containing one or more numeric group IDs")}}</small>
          </div>

          <assign-expression
            v-if="showAssignRuleExpression"
            v-model="specialAssignments" 
          />
          <div v-for="configurable in optionsConfigurables">
            <h6 class="font-weight-bold mt-3" v-if="configurable.startsWith('SECTION_TITLE:')" v-text="configurableLabel(configurable)"></h6>
            <form-checkbox v-else-if="configurable !== 'SELF_SERVICE' || (configurable === 'SELF_SERVICE' && showAssignSelfService)"
               :key="configurable"
               :label="configurableLabel(configurable)"
               :checked="getConfigurableValue(configurable)"
               toggle="true"
               @change="setConfigurableValue($event, configurable)">
            </form-checkbox>
          </div>
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
          return ProcessMaker.modeler.configurables;
        },
      },
      assignmentTypes: {
        type: Array,
        default() {
          return ProcessMaker.modeler.assignmentTypes;
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
    created () {
      // If it is self service in the old format we transform to the new one
      if (this.assignment === 'self_service') {
        const cachedAssignments = {...this.assignments};
        this.assignment = 'user_group';
        this.setConfigurableValue (true, 'SELF_SERVICE');
        this.assignedUserSetter(cachedAssignments.users.join(","));
        this.assignedGroupSetter(cachedAssignments.groups.join(","));
      }

      // If it is user_by_id we update the assignment to an assignment by process_variable
      if (this.assignment === 'user_by_id') {
        const cachedAssignments = {...this.assignments};
        this.assignment = 'process_variable';
        this.setConfigurableValue (true, 'PROCESS_VARIABLE');
        this.assignedUserSetter(cachedAssignments.users.join(","));
        this.assignedGroupSetter(cachedAssignments.groups.join(","));
      }
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
      assignedUsersVar: {
        get () {
          return _.get(this.node, "assignedUsers");
        },
        set (value) {
          this.$set(this.node, "assignedUsers", value);
        }
      },
      assignedGroupsVar: {
        get () {
          return _.get(this.node, "assignedGroups");
        },
        set (value) {
          this.$set(this.node, "assignedGroups", value);
        }
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
          const value = _.get(this.node, "assignment", "requester");
          return value;
        },
        set (value) {
          this.$set(this.node, 'assignedUsers', "");
          this.$set(this.node, 'assignedGroups', "");
          this.$set(this.node, "assignment", value);
        }
      },
      showAssignByVariable () {
        return this.assignment === "process_variable";
      },
      showAssignments() {
        this.hideUsers = this.assignment === "self_service";
        const assign = ["user", "group", "user_group"];
        return assign.indexOf(this.assignment) !== -1;
      },
      showAssignSelfService () {
        return this.assignmentSupportsSelfService(this.assignment);
      },
      showAssignRuleExpression () {
        return this.assignment === 'rule_expression';
      },
      specialAssignmentsListGetter () {
        const value = this.node.get("assignmentRules") || "[]";
        return JSON.parse(value);
      },
      optionsConfigurables () {
        let options = ['self_service', 'rule_expression'];

        if (this.assignment === 'user_group' && ((this.assignments['groups'].length + this.assignments['users'].length) > 1)) {
          options.push('user_group');
        }

        let data = [];
        this.configurables.forEach(element => {
          if (!(options.includes(this.assignment) && element === 'ESCALATE_TO_MANAGER')) {
              data.push(element);
          }
        });

        return data;
      },
    },
    methods: {
      getConfigurableValue(configurable) {
        switch (configurable) {
          case 'LOCK_TASK_ASSIGNMENT':
            return this.assignmentLockGetter;
          case 'ALLOW_REASSIGNMENT':
            return this.allowReassignmentGetter;
          default:
            const config = this.node.config && JSON.parse(this.node.config) || {};
            return config[window._.camelCase(configurable)] || false;
        }
      },
      setConfigurableValue(value, configurable) {
        switch (configurable) {
          case 'LOCK_TASK_ASSIGNMENT':
            return this.assignmentLockSetter(value);
          case 'ALLOW_REASSIGNMENT':
            return this.allowReassignmentSetter(value);
          default:
            const config = this.node.config && JSON.parse(this.node.config) || {};
            config[window._.camelCase(configurable)] = value;
            this.$set(this.node, "config", JSON.stringify(config));
        }
      },
      configurableLabel(configurable) {
        if (configurable.substr(0, 'SECTION_TITLE:'.length) === 'SECTION_TITLE:') {
          configurable = configurable.substr('SECTION_TITLE:'.length);
        }
        switch (configurable) {
          case 'ASSIGNMENT_OPTIONS':
            return this.$t('Assignment Options');
          case 'ASSIGNEE_PERMISSIONS':
            return this.$t('Assignee Permissions');
          case 'SELF_SERVICE':
            return this.$t('Self Service');
          case 'LOCK_TASK_ASSIGNMENT':
            return this.$t('Lock User Assignment');
          case 'ALLOW_REASSIGNMENT':
            return this.$t('Allow Reassignment');
          default:
            return window._.startCase(configurable.toLowerCase());
        }
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
      assignmentSupportsSelfService (assignmentType) {
        let options = ['process_variable', 'user_group', 'rule_expression'];
        if (assignmentType === "process_variable" && _.get(this.node, "loopCharacteristics") !== undefined) {
          options = ['user_group', 'rule_expression'];
        }
        return options.includes(assignmentType);
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

          //self service toggle must be inactivated with a non supported assignment type
          if (!this.assignmentSupportsSelfService(assigned)) {
            this.setConfigurableValue (false, 'SELF_SERVICE');
          }

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

