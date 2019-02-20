<template>
    <div>
        <div class="form-group">
            <label>Due In</label>
            <input class="form-control" type="number" placeholder="72 hours" :value="dueInGetter" @input="dueInSetter" min="0" v-on:keydown="dueInValidate">
            <small class="form-text text-muted">Time when the task will due (hours)</small>
        </div>

        <div class="form-group">
            <label>Task Assignment</label>
            <select ref="assignmentsDropDownList"
                    class="form-control"
                    :value="assignmentGetter"
                    @input="assignmentSetter">
                <option value="requester">To requester</option>
                <option value="user">To user</option>
                <option value="group">To group</option>
                <option value="previous_task_assignee">Previous task assignee</option>
            </select>
        </div>

        <div class="form-group" v-if="showAssignOneUser">
            <label>Assigned User</label>
            <div v-if="loadingUsers">Loading...</div>
            <select v-else class="form-control" :value="assignedUserGetter"
                    @input="assignedUserSetter">
                <option></option>
                <option v-for="(row, index) in users" v-bind:value="row.id" :selected="row.id == assignedUserGetter">
                    {{row.fullname}}
                </option>
            </select>
        </div>
        
        <div class="form-group" v-if="showAssignGroup">
            <label>Assigned Group</label>
            <div v-if="loadingGroups">Loading...</div>
            <select v-else class="form-control" :value="assignedGroupGetter"
                    @input="assignedGroupSetter">
                <option></option>
                <option v-for="(row, index) in groups" v-bind:value="row.id" :selected="row.id == assignedGroupGetter">
                    {{row.name}}
                </option>
            </select>
        </div>

        <form-checkbox label="Allow Reassignment" :checked="allowReassignmentGetter" @change="allowReassignmentSetter"></form-checkbox>

        <div class="form-group">
            <label>Special Assignments</label>
            <div v-if="loadingGroups">Loading...</div>

            <button type="button" @click="addSpecialAssignment" class="float-right btn btn-primary btn-sm">+</button>
            <label>Expression</label>

            <input class="form-control" type="text" v-model="assignmentExpression">

            <div class="form-group">
                <label>Task Assignment</label>
                <select ref="specialAssignmentsDropDownList"
                        class="form-control"
                        v-model="typeAssignmentExpression"
                >
                    <option value=""></option>
                    <option value="requester">To requester</option>
                    <option value="user">To user</option>
                    <option value="group">To group</option>
                </select>
            </div>

            <div class="form-group" v-if="showSpecialAssignOneUser">
                <label>Assigned User</label>
                <div v-if="loadingUsers">Loading...</div>
                <select v-else class="form-control" v-model="userAssignmentExpression">
                    <option></option>
                    <option v-for="(row, index) in users" v-bind:value="row.id"
                            :selected="row.id == this.userAssignmentExpression">
                        {{row.fullname}}
                    </option>
                </select>
            </div>

            <div class="form-group" v-if="showSpecialAssignGroup">
                <label>Assigned Group</label>
                <div v-if="loadingGroups">Loading...</div>
                <select v-else class="form-control" v-model="groupAssignmentExpression">
                    <option></option>
                    <option v-for="(row, index) in groups" v-bind:value="row.id"
                            :selected="row.id == groupAssignmentExpression">
                        {{row.name}}
                    </option>
                </select>
            </div>

            <span v-for="(row, index) in specialAssignments"
                  class="list-group-item list-group-item-action pt-0 pb-0"
                  :class="{'bg-primary': false}">
                    <template>
                        <span class="text-center text-nowrap m-1">{{row.expression}}</span>
                        &nbsp; to &nbsp;
                        <span class="text-center text-capitalize text-nowrap m-1">{{row.type}}</span>
                        <span class="text-center text-nowrap m-1">
                            {{getAssigneeName(row)}}
                        </span>
                        <i class="fa fa-trash" aria-hidden="true"
                           @click="removeSpecialAssignment(row)"></i>
                    </template>
            </span>

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
                assignmentExpression: '',
                userAssignmentExpression: '' ,
                userNameAssignmentExpression: '' ,
                groupAssignmentExpression: '',
                typeAssignmentExpression: '',
            };
        },
        computed: {
            /**
             * Get the value of the edited property
             */
            allowReassignmentGetter() {
                const node = this.$parent.$parent.highlightedNode.definition;
                const value = _.get(node, 'allowReassignment');
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
                const node = this.$parent.$parent.highlightedNode.definition;
                const value = _.get(node, 'dueIn');
                return value;
            },
            assignedUserGetter() {
                const node = this.$parent.$parent.highlightedNode.definition;
                const value = _.get(node, 'assignedUsers');
                return value;
            },
            assignedGroupGetter() {
                const node = this.$parent.$parent.highlightedNode.definition;
                const value = _.get(node, 'assignedGroups');
                return value;
            },
            assignmentGetter() {
                const node = this.$parent.$parent.highlightedNode.definition;
                const value = _.get(node, 'assignment');
                return value;
            },
            node() {
                return this.$parent.$parent.highlightedNode.definition;
            },
            showAssignOneUser() {
                return this.assignmentGetter === 'user';
            },
            showAssignGroup() {
                return this.assignmentGetter === 'group';
            },

            showSpecialAssignOneUser() {
                return this.typeAssignmentExpression === 'user';
            },
            showSpecialAssignGroup() {
                return this.typeAssignmentExpression === 'group';
            },
            specialAssignmentsListGetter() {
                const node = this.$parent.$parent.highlightedNode.definition;
                const value = _.get(node, 'assignmentRules');
                return value;
            },
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
                    this.$delete(this.node, 'dueIn');
                } else {
                    this.$set(this.node, 'dueIn', validValue);
                }
                this.$emit('input', this.value);
                String(validValue) !== event.target.value ? event.target.value = validValue : null;
            },
            /**
             * Update allowReassignment property
             */
            allowReassignmentSetter(value) {
                this.$set(this.node, 'allowReassignment', value);
                this.$emit('input', this.value);
            },
            /**
             * Load the list of assigned users
             */
            loadUsersAndGroups() {
                this.loadingUsers = true;
                this.users = []
                ProcessMaker.apiClient
                    .get("/users")
                    .then(response => {
                        this.users.push(...response.data.data);
                        this.loadingUsers = false;
                    });
                
                this.loadingGroups = true;
                this.groups = [];
                ProcessMaker.apiClient
                    .get("/groups")
                    .then(response => {
                        this.groups.push(...response.data.data);
                        this.loadingGroups = false;
                    });
            },
            /**
             * Update the event of the editer property
             */
            assignedUserSetter(event) {
                this.$set(this.node, 'assignedUsers', event.target.value);
                this.$emit('input', this.value);
            },
            assignedGroupSetter(event) {
                this.$set(this.node, 'assignedGroups', event.target.value);
                this.$emit('input', this.value);
            },
            /**
             * Update the event of the editer property
             */
            assignmentSetter(event) {
                this.$set(this.node, 'assignment', event.target.value);
                this.$emit('input', this.value);
            },

            removeSpecialAssignment(assignment) {
                this.specialAssignments = this.specialAssignments.filter(
                    function (obj) {
                       return obj.type !== assignment.type
                           || obj.expression !== assignment.expression
                           || obj.assignee !== assignment.assignee
                    }
                );

                this.$set(this.node, 'assignmentRules',
                    JSON.stringify(this.specialAssignments)
                );
            },

            addSpecialAssignment () {
                let selectedAssignee = this.userAssignmentExpression
                    ? this.userAssignmentExpression
                    : this.groupAssignmentExpression;

                let byExpression = {
                    type: this.typeAssignmentExpression,
                    assignee: selectedAssignee,
                    expression: this.assignmentExpression
                };

                if (byExpression.type && byExpression.expression) {
                    this.specialAssignments.push(byExpression);
                    this.$set(this.node, 'assignmentRules',
                        JSON.stringify(this.specialAssignments));
                    this.assignmentExpression = '';
                    this.typeAssignmentExpression = '';
                    this.userAssignmentExpression = '';
                    this.groupAssignmentExpression = '';
                }
            },

            loadSpecialAssignments() {
                this.specialAssignments = this.specialAssignmentsListGetter
                                            ? JSON.parse (this.specialAssignmentsListGetter)
                                            : [];
            },

            getAssigneeName(assignment) {
                if (assignment.type === 'requester') {
                    return '';
                }

                if (assignment.type === 'group') {
                    let group = this.groups.find(obj => {return obj.id === assignment.assignee});
                    return group ? group.name : '';
                }

                if (assignment.type === 'user') {
                    let user = this.users.find(obj => {return obj.id === assignment.assignee});
                    return user ? user.fullname : '';
                }

                return '';
            }
        },
        mounted() {
            this.loadUsersAndGroups();
            this.loadSpecialAssignments();
        },
        watch: {
            value() {
                this.loadSpecialAssignments();
            }
        }
    };
</script>

<style lang="scss" scoped>
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
</style>
