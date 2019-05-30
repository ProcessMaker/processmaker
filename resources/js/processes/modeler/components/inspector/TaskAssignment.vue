<template>
    <div>
        <div class="form-group">
            <label>{{ $t('Due In') }}</label>
            <input class="form-control"
                   type="number"
                   placeholder="72 hours"
                   :value="dueInGetter"
                   @input="dueInSetter"
                   min="0"
                   v-on:keydown="dueInValidate">
            <small class="form-text text-muted">{{ $t('Time when the task will due (hours)') }}</small>
        </div>

        <div class="form-group">
            <label>{{ $t('Task Assignment') }}</label>
            <select ref="assignmentsDropDownList"
                    class="form-control"
                    :value="assignmentGetter"
                    @input="assignmentSetter">
                <option value="requester">{{ $t('Requester') }}</option>
                <option value="user">{{ $t('User') }}</option>
                <option value="group">{{ $t('Group') }}</option>
                <option value="previous_task_assignee">{{ $t('Previous Task Assignee') }}</option>
            </select>
        </div>

        <div class="form-group" v-if="showAssignOneUser || showAssignGroup">
            <label class="text-capitalize" v-if="showAssignOneUser">{{ $t('Assigned User')}}</label>
            <label class="text-capitalize" v-if="showAssignGroup">{{ $t('Assigned Group')}}</label>
            <div v-if="loading">{{ $t('Loading...') }}</div>
            <div v-else>
                <multiselect v-model="content"
                             track-by="id"
                             label="name"
                             :select-label="''"
                             :deselect-label="''"
                             :placeholder="$t('type here to search')"
                             :options="options"
                             :multiple="false"
                             :show-labels="false"
                             :searchable="true"
                             :internal-search="false"
                             :helper="helper"
                             @search-change="load($event, 'assignment')">
                </multiselect>
            </div>
        </div>

        <form-checkbox :label="$t('Allow Reassignment')"
                       :checked="allowReassignmentGetter"
                       @change="allowReassignmentSetter">
        </form-checkbox>

        <div class="form-group">

            <div class="form-group special-assignment-header">
                <label>{{ $t('Assign by Expression') }}</label>
                <button type="button"
                        @click="addingSpecialAssignment = true"
                        class="float-right btn-special-assignment-action btn btn-secondary btn-sm"
                        :class="{inactive: addingSpecialAssignment}">
                    <i class="fa fa-plus"></i> {{ $t('Rule') }}
                </button>
            </div>

            <div class="special-assignment-wrapper" ref="specialAssignmentWrapper" @transitionend="transitionEnded">
                <div class="special-assignment-form">

                    <div class="form-group">
                        <label>{{ $t('Expression') }}</label>
                        <input class="form-control" ref="specialAssignmentsInput" type="text"
                               v-model="assignmentExpression">
                    </div>

                    <div class="form-group">
                        <label>{{ $t('Task Assignment') }}</label>
                        <select ref="specialAssignmentsDropDownList"
                                class="form-control"
                                v-model="typeAssignmentExpression">
                            <option value=""></option>
                            <option value="requester">{{ $t('Requester') }}</option>
                            <option value="user">{{ $t('User') }}</option>
                            <option value="group">{{ $t('Group') }}</option>
                        </select>
                    </div>

                    <div class="form-group" v-if="showSpecialAssignOneUser || showSpecialAssignGroup">
                        <label class="text-capitalize" v-if="showSpecialAssignOneUser">{{ $t('Assigned User')}}</label>
                        <label class="text-capitalize" v-if="showSpecialAssignGroup">{{ $t('Assigned Group')}}</label>
                        <div v-if="loading">{{ $t('Loading...') }}</div>
                        <div v-else>
                            <multiselect v-model="contentExpression"
                                         track-by="id"
                                         label="name"
                                         :select-label="''"
                                         :deselect-label="''"
                                         :placeholder="$t('type here to search')"
                                         :options="optionsExpression"
                                         :multiple="false"
                                         :show-labels="false"
                                         :searchable="true"
                                         :internal-search="false"
                                         :helper="helper"
                                         @search-change="load($event, 'expression')">
                            </multiselect>
                        </div>
                    </div>

                    <div class="form-group form-group-actions">
                        <button type="button"
                                @click="addingSpecialAssignment = false"
                                class="btn-special-assignment-action btn-special-assignment-close btn btn-outline-secondary btn-sm">
                            {{ $t('Cancel') }}
                        </button>
                        <button type="button"
                                @click="saveSpecialAssignment"
                                class="btn-special-assignment-action btn btn-secondary btn-sm">
                            {{ $t('Save') }}
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
                            <strong>{{$t(row.expression)}}</strong></div>
                        <div class="btn-special-assignment-delete" @click="removeSpecialAssignment(row)"><i
                                class="fa fa-trash"></i>
                        </div>
                    </div>
                    <div class="special-assignment-section">
                        <div class="special-assignment-value">{{ $t('Assigned to') }}
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
    data() {
      return {
        users: [],
        groups: [],
        specialAssignments: [],
        loadingUsers: true,
        loadingGroups: true,
        addingSpecialAssignment: false,
        assignmentExpression: "",
        userNameAssignmentExpression: "",
        typeAssignmentExpression: "",

        content: null,
        options: [],
        optionsExpression: [],
        loading: false,
        loadingAssign: false,
        contentExpression: null,
        specialAssignmentsData: [],
      };
    },
    computed: {
      /**
       * Get the value of the edited property
       */
      allowReassignmentGetter() {
        const node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
        const value = _.get(node, "allowReassignment");
        return value;
      },
      /**
       * Get owner process.
       *
       * @returns {object}
       */
      process() {
        return this.$parent.$parent.$parent.process;
      },
      dueInGetter() {
        const node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
        const value = _.get(node, "dueIn");
        return value;
      },
      assignedUserGetter() {
        const node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
        const value = _.get(node, "assignedUsers");
        return value;
      },
      assignedGroupGetter() {
        const node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
        const value = _.get(node, "assignedGroups");
        return value;
      },
      assignmentGetter() {
        const node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
        const value = _.get(node, "assignment");
        return value;
      },
      node() {
        return this.$parent.$parent.$parent.$parent.highlightedNode.definition;
      },
      showAssignOneUser() {
        return this.assignmentGetter === "user";
      },
      showAssignGroup() {
        return this.assignmentGetter === "group";
      },

      showSpecialAssignOneUser() {
        if (this.typeAssignmentExpression === "user") {
          this.load('', 'expression');
        }
        return this.typeAssignmentExpression === "user";
      },
      showSpecialAssignGroup() {
        if (this.typeAssignmentExpression === "group") {
          this.load('', 'expression');
        }
        return this.typeAssignmentExpression === "group";
      },
      specialAssignmentsListGetter() {
        const node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
        const value = _.get(node, "assignmentRules");
        return value;
      }
    },
    methods: {
      dueInValidate(event) {
        if (event.key === "-") {
          event.preventDefault();
          return;
        }
      },
      /**
       * Update due in property
       */
      dueInSetter(event) {
        const validValue = Math.abs(event.target.value * 1) || "";
        if (!validValue) {
          this.$delete(this.node, "dueIn");
        } else {
          this.$set(this.node, "dueIn", validValue);
        }
        this.$emit("input", this.value);
        String(validValue) !== event.target.value
          ? (event.target.value = validValue)
          : null;
      },
      /**
       * Update allowReassignment property
       */
      allowReassignmentSetter(value) {
        this.$set(this.node, "allowReassignment", value);
        this.$emit("input", this.value);
      },
      load(filter, type) {
        if (type === 'assignment') {
          this.options = [];
          if (this.assignmentGetter === 'user') {
            this.loadUsers(filter, 'options');
          } else if (this.assignmentGetter === 'group') {
            this.loadGroups(filter, 'options');
          }
        }

        if (type === 'expression') {
          this.optionsExpression = [];
          if (this.typeAssignmentExpression === 'user') {
            this.loadUsers(filter, 'optionsExpression');
          } else if (this.typeAssignmentExpression === 'group') {
            this.loadGroups(filter, 'optionsExpression');
          }
        }
      },
      loadUsers(filter, container) {
        ProcessMaker.apiClient
          .get("users?order_direction=asc&status=active" + (typeof filter === 'string' ? '&filter=' + filter : ''))
          .then(response => {
            this.loading = false;
            let data = response.data.data.map(item => {
              return {
                id: item.id,
                name: item.fullname
              }
            });
            if (container === 'options') {
              this.options = data;
            } else {
              this.optionsExpression = data;
            }
          })
          .catch(err => {
            this.loading = false;
          });
      },
      loadGroups(filter, container) {
        ProcessMaker.apiClient
          .get("groups?order_direction=asc&status=active" + (typeof filter === 'string' ? '&filter=' + filter : ''))
          .then(response => {
            this.loading = false;
            let data = response.data.data.map(item => {
              return {
                id: item.id,
                name: item.name
              }
            });
            if (container === 'options') {
              this.options = data;
            } else {
              this.optionsExpression = data;
            }
          })
          .catch(err => {
            this.loading = false;
          });
      },

      loadAssigned() {
        const node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
        if (this.assignmentGetter === 'user') {
          const value = _.get(node, "assignedUsers");
          ProcessMaker.apiClient
            .get("users/" + value)
            .then(response => {
              this.loading = false;
              this.content = {
                id: response.data.id,
                name: response.data.fullname
              };
            })
            .catch(err => {
              this.loading = false;
            });
        } else if (this.assignmentGetter === 'group') {
          const value = _.get(node, "assignedGroups");
          ProcessMaker.apiClient
            .get("groups/" + value)
            .then(response => {
              this.loading = false;
              this.content = {
                id: response.data.id,
                name: response.data.name
              };
            })
            .catch(err => {
              this.loading = false;
            });
        }
      },
      /**
       * Update the event of the editer property
       */
      assignedUserSetter(id) {
        /*this.$set(this.node, "assignedUsers", event.target.value);
        this.$emit("input", this.value);*/

        let node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
        //let value = _.get(node, "assignedUsers");
        this.$set(node, "assignedUsers", id);
        //value = _.get(node, "assignedGroups");

        this.$set(node, "assignedGroups", '');
      },
      assignedGroupSetter(id) {
        /*this.$set(this.node, "assignedGroups", event.target.value);
        this.$emit("input", this.value);*/

        let node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
        //let value = _.get(node, "assignedUsers");
        this.$set(node, "assignedUsers", '');
        //value = _.get(node, "assignedGroups");
        this.$set(node, "assignedGroups", id);
      },
      /**
       * Update the event of the editer property
       */
      assignmentSetter(event) {
        this.$set(this.node, "assignment", event.target.value);
        this.$emit("input", this.value);
        this.content = [];
        this.load('', 'assignment');
      },

      removeSpecialAssignment(assignment) {
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

        this.$set(
          this.node,
          "assignmentRules",
          JSON.stringify(this.specialAssignments)
        );
      },

      transitionEnded(event) {
        if (this.addingSpecialAssignment) {
          if (event.propertyName == 'height') {
            this.$refs.specialAssignmentsInput.focus();
            this.$refs.specialAssignmentWrapper.style.height = 'auto';
          }
        } else {
          this.assignmentExpression = "";
          this.typeAssignmentExpression = "";
          this.contentExpression = null;
        }
      },

      saveSpecialAssignment() {

        let byExpression = {
          type: this.typeAssignmentExpression,
          assignee: this.contentExpression ? this.contentExpression.id : '',
          expression: this.assignmentExpression
        };

        if (byExpression.type && byExpression.expression) {
          this.specialAssignments.push(byExpression);
          this.$set(
            this.node,
            "assignmentRules",
            JSON.stringify(this.specialAssignments)
          );

          this.specialAssignmentsData.push({
            type: this.typeAssignmentExpression,
            assignee: this.contentExpression ? this.contentExpression.id : '',
            expression: this.assignmentExpression,
            assignmentName: this.contentExpression ? this.contentExpression.name : '',
          });

          this.assignmentExpression = "";
          this.typeAssignmentExpression = "";
          this.contentExpression = null;
        }

        this.addingSpecialAssignment = false;
      },

      loadSpecialAssignments() {
        this.specialAssignments = this.specialAssignmentsListGetter
          ? JSON.parse(this.specialAssignmentsListGetter)
          : [];

        this.specialAssignmentsData.splice(0);
        this.specialAssignments.forEach(item => {
          if (item.type === 'requester') {
            this.specialAssignmentsData.push({
              type: item.type,
              assignee: item.assignee,
              expression: item.expression
            });
            return;
          }
          if (item.type === "user") {
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
              .catch(err => {
                item.assignmentName = '';
                this.specialAssignmentsData.push(item);
                return "";
              });
          }
          if (item.type === "group") {
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
              .catch(err => {
                item.assignmentName = '';
                this.specialAssignmentsData.push(item);
                return "";
              });
          }
        });
      },
    },
    mounted() {
      this.loadAssigned();
      this.loadSpecialAssignments();
    },
    watch: {
      content: {
        handler() {
          if (this.assignmentGetter === 'user' && this.content) {
            this.assignedUserSetter(this.content.id)
          } else if (this.assignmentGetter === 'group' && this.content) {
            this.assignedGroupSetter(this.content.id)
          }
        }
      },
      value() {
        this.loadSpecialAssignments();
      },
      addingSpecialAssignment(value) {
        let wrapper = this.$refs.specialAssignmentWrapper;
        let height = wrapper.scrollHeight;

        if (value === true) {
          wrapper.style.height = height + 'px';
          wrapper.style.opacity = 1;
        }

        if (value === false) {
          wrapper.style.height = height + 'px';
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
    @import "~vue-multiselect/dist/vue-multiselect.min.css";

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
</style>
