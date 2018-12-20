<template>
    <div>
        <div class="form-group">
            <label>Task Assignment</label>
            <select ref="assignmentsDropDownList"
                    class="form-control"
                    :value="assignmentGetter"
                    @input="assignmentSetter"
                    @change="assignmentTypeChanged">
                <option value=""></option>
                <option value="requestor">To requestor</option>
                <option value="cyclical" v-if="false">Cyclical</option>
                <option value="user">To user</option>
            </select>
        </div>
        <div class="form-group" v-if="showAssignOneUser">
            <label>Assigned User</label>
            <select class="form-control" @change="updateAssignment" v-model="selectedUserId">
                <option v-for="user in users" v-bind:value="user.id">
                    {{user.fullname}}
                </option>
            </select>
        </div>
    </div>
</template>


<script>
    const USER_TYPE = "ProcessMaker\\Models\\User";
    const GROUP_TYPE = "ProcessMaker\\Models\\Group";
    export default {
        props: ["value", "label", "helper", "property"],
        data() {
            return {
                users: [],
                groups: [],
                // showAssignOneUser: false,
                showUserOrGroup: false,
                filter: '',
                loadingAssigned: true,
                selectedUserId: null,
                assignmentId: null,

                // temp
                usersAndGroups: [],

                // remove below
                selectedUserGroupIndex: -1,
                selectedUserGroup: null,
                selectedAssigneeIndex: -1,
                selectedAssignee: null,
                assignedUsersGroups: [],
            };
        },
        computed: {
            process() {
                return this.$parent.$parent.$parent.process;
            },
            /**
             * Get the value of the edited property
             */
            assignmentGetter() {
                const node = this.$parent.$parent.inspectorNode;
                const value = _.get(node, 'assignment');
                return value;
            },
            node() {
                return this.$parent.$parent.inspectorNode;
            },
            showAssignOneUser() {
                return this.assignmentGetter === 'user';
            },
            showMultiassignment() {
                return this.assignmentGetter === 'cyclical';
            }
        },
        methods: {
            /**
             * Updates in the backend the user assigned when it is changed
             *
             */
            updateAssignment() {
                if (!this.assignmentId) {
                    return this.createAssignment();
                }
                if (!this.selectedUserId) {
                    return this.deleteAssignment();
                }
                ProcessMaker.apiClient
                    .put(`task_assignments/${this.assignmentId}`, {
                        'process_id': this.process.id,
                        'process_task_id': this.value,
                        'assignment_id': this.selectedUserId,
                        'assignment_type': USER_TYPE,
                    })
                    .then(() => {});
            },
            assignmentTypeChanged()
            {
                if (this.assignmentGetter != 'user' && this.assignmentId) {
                    this.deleteAssignment()
                }
                // set a default user otherwise the nayra engine will break
                if (this.assignmentGetter == 'user' && !this.selectedUserId) {
                    this.selectedUserId = this.users[0].id
                    this.updateAssignment()
                }
            },
            /**
             * Remove an assignment
             */
            deleteAssignment() {
                ProcessMaker.apiClient
                    .delete(`task_assignments/${this.assignmentId}`)
                    .then(() => {
                        this.assignmentId = null
                        this.selectedUserId = null
                        // this.assignedUsersGroups.splice(this.selectedAssigneeIndex, 1);
                        // this.selectedAssigneeIndex = -1;
                    });
            },
            /**
             * Add an user or group.
             */
            createAssignment() {
                ProcessMaker.apiClient
                    .post("/task_assignments", {
                        'process_id': this.process.id,
                        'process_task_id': this.value,
                        'assignment_id': this.selectedUserId,
                        'assignment_type': USER_TYPE,
                    })
                    .then(assignment => {
                        this.assignmentId = assignment.data.id
                        // this.showAssignees  = false;
                    });
            },
            /**
             * Load the list of assigned users
             */
            loadUsersAndGroups() {
                ProcessMaker.apiClient
                    .get("/users", {
                        params: {
                            filter: this.filter,
                        }
                    })
                    .then(response => {
                        this.users = response.data.data;
                    });

                return; // Not using groups yet
                ProcessMaker.apiClient
                    .get("/groups", {
                        params: {
                            filter: this.filter,
                        }
                    })
                    .then(response => {
                        this.groups = response.data.data;
                    });
            },
            /**
             * Load the list of assigned users
             */
            loadAssignments() {
                this.loadingAssigned = true;
                ProcessMaker.apiClient
                    .get("/task_assignments", {
                        params: {
                            process_id: this.process.id,
                            process_task_id: this.value,
                            include: 'assigned',
                        }
                    })
                    .then(response => {
                        if (response.data.data.length > 0) {
                            // get the last element in array (even though there should only be one)
                            const assignment = response.data.data.slice(-1)[0]

                            if (assignment.assignment_type != 'ProcessMaker\\Models\\User') {
                                throw("Only users assignments are supported for now")
                            }
                            this.assignmentId = assignment.id
                            this.selectedUserId = assignment.assignment_id
                        }
                    });
            },
            /**
             * Update the event of the editer property
             */
            assignmentSetter(event) {
                Vue.set(this.node, 'assignment', event.target.value);
                this.$emit('input', this.value);
            },
        },
        watch: {
            value: _.debounce(function() {
                if (this.assignmentId) {
                    this.updateAssignment();
                    this.loadAssignments();
                }
            }, 500),
        },
        mounted() {
            this.loadUsersAndGroups();
            this.loadAssignments();
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
