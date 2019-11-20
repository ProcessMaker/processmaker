<template>
    <div>
        <div class="form-group">
            <label>{{ $t("Task Assignment") }}</label>
            <select
                ref="assignmentsDropDownList"
                class="form-control"
                :value="assignmentGetter"
                @input="assignmentSetter">
                <option value="requester">{{ $t("Requester") }}</option>
                <option value="user">{{ $t("User") }}</option>
                <option value="group">{{ $t("Group") }}</option>
                <option value="previous_task_assignee">{{ $t("Previous Task Assignee") }}</option>
            </select>
        </div>

        <user-select
            v-if="showAssignOneUser"
            :label="$t('Assigned User')"
            v-model="assigned"
        >
        </user-select>

        <group-select
            v-if="showAssignGroup"
            :label="$t('Assigned Group')"
            v-model="assigned"
        >
        </group-select>

        <form-checkbox
            :label="$t('Allow Reassignment')"
            :checked="allowReassignmentGetter"
            @change="allowReassignmentSetter">
        </form-checkbox>

        <div class="form-group">

            <div class="form-group special-assignment-header">
                <label>{{ $t("Assign by Expression") }}</label>
                <button type="button"
                        @click="addingSpecialAssignment = true"
                        class="float-right btn-special-assignment-action btn btn-secondary btn-sm"
                        :class="{inactive: addingSpecialAssignment}">
                    <i class="fa fa-plus"></i> {{ $t("Rule") }}
                </button>
            </div>

            <div class="special-assignment-wrapper" ref="specialAssignmentWrapper" @transitionend="transitionEnded">
                <div class="special-assignment-form">

                    <div class="form-group">
                        <label>{{ $t("Expression") }}</label>
                        <input class="form-control" ref="specialAssignmentsInput" type="text"
                               v-model="assignmentExpression">
                    </div>

                    <div class="form-group">
                        <label>{{ $t("Task Assignment") }}</label>
                        <select
                            ref="specialAssignmentsDropDownList"
                            class="form-control"
                            v-model="typeAssignmentExpression">
                            <option value=""></option>
                            <option value="requester">{{ $t("Requester") }}</option>
                            <option value="user">{{ $t("User") }}</option>
                            <option value="group">{{ $t("Group") }}</option>
                        </select>
                    </div>

                    <user-select
                        ref="userAssignedSpecial"
                        v-if="showSpecialAssignOneUser"
                        :label="$t('Assigned User')"
                        v-model="assignedExpression"
                    >
                    </user-select>

                    <group-select
                        ref="groupAssignedSpecial"
                        v-if="showSpecialAssignGroup"
                        :label="$t('Assigned Group')"
                        v-model="assignedExpression"
                    >
                    </group-select>

                    <div class="form-group form-group-actions">
                        <button
                            type="button"
                            @click="addingSpecialAssignment = false"
                            class="btn-special-assignment-action btn-special-assignment-close btn btn-outline-secondary btn-sm">
                            {{ $t("Cancel") }}
                        </button>
                        <button
                            type="button"
                            @click="saveSpecialAssignment"
                            class="btn-special-assignment-action btn btn-secondary btn-sm">
                            {{ $t("Save") }}
                        </button>
                    </div>

                </div>
            </div>

            <div v-for="(row, index) in specialAssignmentsData"
                 class="list-group-item list-group-item-action pt-0 pb-0"
                 :class="{'bg-primary': false}">
                <template>
                    <div class="special-assignment-section">
                        <div class="special-assignment-value" :title="row.expression">
                            <strong>{{ $t(row.expression) }}</strong></div>
                        <div class="btn-special-assignment-delete" @click="removeSpecialAssignment(row)">
                            <i class="fa fa-trash"></i>
                        </div>
                    </div>
                    <div class="special-assignment-section">
                        <div class="special-assignment-value">{{ $t("Assigned to") }}
                            <strong v-if="row.type == 'requester'">{{$t(row.type)}}</strong>
                            <strong v-else>{{$t(row.assignmentName)}}</strong>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
  export default {
    props: ["value", "label", "helper", "property"],
    data () {
      return {
        specialAssignments: [],
        addingSpecialAssignment: false,
        assignment: null,
        assignmentExpression: "",
        typeAssignmentExpression: "",
        specialAssignmentsData: [],

        assigned: "",
        assignedExpression: null,
        error: "",
      };
    },
    computed: {
      node () {
        return this.$parent.$parent.$parent.$parent.highlightedNode.definition;
      },
      /**
       * Get owner process.
       *
       * @returns {object}
       */
      process () {
        return this.$parent.$parent.$parent.process;
      },
      /**
       * Get the value of the edited property
       */
      allowReassignmentGetter () {
        return _.get(this.node, "allowReassignment");
      },
      assignedUserGetter () {
        return _.get(this.node, "assignedUsers");
      },
      assignedGroupGetter () {
        return _.get(this.node, "assignedGroups");
      },
      assignmentGetter () {
        this.assigned = null;
        this.loadSpecialAssignments();
        const value = _.get(this.node, "assignment");
        this.assignment = value;
        return value;
      },
      showAssignOneUser () {
        return this.assignment === "user";
      },
      showAssignGroup () {
        return this.assignment === "group";
      },
      showSpecialAssignOneUser () {
        return this.typeAssignmentExpression === "user";
      },
      showSpecialAssignGroup () {
        return this.typeAssignmentExpression === "group";
      },
      specialAssignmentsListGetter () {
        const value = this.node.get('assignmentRules') || '[]';
        return JSON.parse(value);
      },
    },
    methods: {
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
        let node = this.node;
        this.$set(node, "assignedUsers", id);
        this.$set(node, "assignedGroups", "");
      },
      assignedGroupSetter (id) {
        let node = this.node;
        this.$set(node, "assignedUsers", "");
        this.$set(node, "assignedGroups", id);
      },
      /**
       * Update the event of the editer property
       */
      assignmentSetter (event) {
        this.assignment = event.target.value;
        this.assigned = "";
        this.$set(this.node, "assignment", event.target.value);
      },
      assignmentRulesSetter () {
        this.$set(this.node, "assignmentRules", JSON.stringify(this.specialAssignments));
      },
      removeSpecialAssignment (assignment) {
        this.specialAssignments = this.specialAssignments.filter(function (obj) {
          return (
            obj.type !== assignment.type ||
            obj.expression !== assignment.expression ||
            obj.assignee !== assignment.assignee
          );
        });

        this.specialAssignmentsData = this.specialAssignmentsData.filter(function (obj) {
          return (
            obj.type !== assignment.type ||
            obj.expression !== assignment.expression ||
            obj.assignee !== assignment.assignee
          );
        });

        this.assignmentRulesSetter();
      },

      transitionEnded (event) {
        if (this.addingSpecialAssignment) {
          if (event.propertyName == "height") {
            this.$refs.specialAssignmentsInput.focus();
            this.$refs.specialAssignmentWrapper.style.height = "auto";
          }
        } else {
          this.assignmentExpression = "";
          this.typeAssignmentExpression = "";
          this.assignedExpression = null;
        }
      },

      saveSpecialAssignment () {

        let byExpression = {
          type: this.typeAssignmentExpression,
          assignee: this.assignedExpression || "",
          expression: this.assignmentExpression
        };

        if (byExpression.type && byExpression.expression) {
          this.specialAssignments.push(byExpression);
          this.assignmentRulesSetter();

          this.specialAssignmentsData.push({
            type: this.typeAssignmentExpression,
            assignee: this.assignedExpression || "",
            expression: this.assignmentExpression,
            assignmentName: this.typeAssignmentExpression === "user" ? this.$refs.userAssignedSpecial.content.fullname : this.$refs.groupAssignedSpecial.content.name,
          });

          this.assignmentExpression = "";
          this.typeAssignmentExpression = "";
          this.assignedExpression = null;
        }

        this.addingSpecialAssignment = false;
      },

      loadSpecialAssignments () {
        this.specialAssignmentsData = [];
        const items = this.specialAssignmentsListGetter;
        this.specialAssignments = items;

        items.forEach(item => {
          if (item.type === "requester") {
            this.specialAssignmentsData.push({
              type: item.type,
              assignee: item.assignee,
              expression: item.expression
            });
          } else if (item.type === "user") {
            ProcessMaker.apiClient
              .get("users/" + item.assignee)
              .then(response => {
                this.specialAssignmentsData.push({
                  type: item.type,
                  assignee: item.assignee,
                  expression: item.expression,
                  assignmentName: response.data.fullname
                });
              })
              .catch(() => {
                item.assignmentName = "";
                this.specialAssignmentsData.push(item);
              });
          } else if (item.type === "group") {
            ProcessMaker.apiClient
              .get("groups/" + item.assignee)
              .then(response => {
                this.specialAssignmentsData.push({
                  type: item.type,
                  assignee: item.assignee,
                  expression: item.expression,
                  assignmentName: response.data.name
                });
              })
              .catch(() => {
                item.assignmentName = "";
                this.specialAssignmentsData.push(item);
              });
          }
        });
      },
    },
    watch: {
      assigned: {
        handler (value) {
          if (this.assignment === "user" && value) {
            this.assignedUserSetter(value);
          } else if (this.assignment === "group" && value) {
            this.assignedGroupSetter(value);
          }
        }
      },
      assignment: {
        handler (assigned) {
          let value = "";
          if (assigned === "user") {
            value = this.assignedUserGetter;
          } else if (assigned === "group") {
            value = this.assignedGroupGetter;
          }
          this.assigned = value;
        }

      },
      addingSpecialAssignment (value) {
        let wrapper = this.$refs.specialAssignmentWrapper;
        let height = wrapper.scrollHeight;

        if (value === true) {
          wrapper.style.height = height + "px";
          wrapper.style.opacity = 1;
        }

        if (value === false) {
          wrapper.style.height = height + "px";
          setTimeout(() => {
            wrapper.style.height = 0;
            wrapper.style.opacity = 0;
          }, 0);
        }
      }
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
