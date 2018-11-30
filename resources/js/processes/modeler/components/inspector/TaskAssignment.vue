<template>
    <div>
        <div class="form-group">
            <label>Task Assignment</label>
            <select ref="assignmentsDropDownList"
                    class="form-control"
                    :value="assignmentGetter"
                    @input="assignmentSetter">
                <option value=""></option>
                <option value="requestor">To requestor</option>
                <option value="cyclical" v-if="false">Cyclical</option>
                <option value="user">To user</option>
            </select>
        </div>
        <div class="form-group" v-if="showMultiassignment">
            <label>Assigned Users/Groups</label>
            <button @click="showUserOrGroup=true;" class="btn-sm float-right">+</button>
            <button @click="removeUserOrGroup" :disabled="!selectedAssignee" class="btn-sm float-right">-</button>
            <div class="list-users-groups small">
                <small v-if="loadingAssigned">loading...</small>
                <span v-else v-for="(row, index) in assignedUsersGroups"
                      class="list-group-item list-group-item-action pt-0 pb-0"
                      :class="{'bg-primary': selectedAssigneeIndex == index}"
                      @click="selectAssignee(row, index)">
                    <avatar-image v-if="row.assigned.fullname"
                                  class-container=""
                                  size="12" class-image=""
                                  :input-data="row.assigned"></avatar-image>
                    <template v-else>
                        <i class="fa fa-users" aria-hidden="true"></i>
                        <span class="text-center text-capitalize text-nowrap m-1">{{row.assigned.name}}</span>
                    </template>
                </span>
            </div>
        </div>

        <div class="form-group" v-if="showAssignOneUser">
            <label>Assigned User</label>
            <select class="form-control" @click="changedUser" v-model="selectedUser">
                <option v-for="(row, index) in activeUsers" v-bind:value="row.id">
                    {{row.fullname}}
                </option>
            </select>
        </div>

        <b-modal v-model="showUserOrGroup" size="md" centered title="Assign User or Group" v-cloak>
            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="search user or group" v-model="filter">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" @click="loadUsersAndGroups">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="list-users-groups">
                <span v-for="(row, index) in usersAndGroups"
                      class="list-group-item list-group-item-action pt-1 pb-1"
                      :class="{'bg-primary': selectedUserGroupIndex == index}"
                      @click="selectUserGroup(row, index)"
                      @dblclick="selectUserGroup(row, index);addUserOrGroup();"
                >
                      <avatar-image v-if="row.fullname"
                                    class-container=""
                                    size="12" class-image=""
                                    :input-data="row"></avatar-image>
                    <template v-else>
                        <i class="fa fa-users" aria-hidden="true"></i>
                        <span class="text-center text-capitalize text-nowrap m-1">{{row.name}}</span>
                    </template>
                </span>
            </div>
            <div slot="modal-footer">
                <b-button @click="cancelAddUserOrGroup" class="btn btn-outline-secondary btn-sm text-uppercase">
                    CLOSE
                </b-button>
                <b-button :disabled="selectedUserGroupIndex < 0" @click="addUserOrGroup"
                          class="btn btn-secondary btn-sm text-uppercase">
                    ASSIGN
                </b-button>
            </div>

        </b-modal>
    </div>
</template>


<script>
    const USER_TYPE = "ProcessMaker\\Models\\User";
    const GROUP_TYPE = "ProcessMaker\\Models\\Group";
    export default {
        props: ["value", "label", "helper", "property"],
        data() {
            return {
                selectedAssigneeIndex: -1,
                selectedAssignee: null,
                assignedUsersGroups: [],
                usersAndGroups: [],
                showUserOrGroup: false,
                filter: '',
                selectedUserGroupIndex: -1,
                selectedUserGroup: null,
                loadingAssigned: true,
                selectedUser: ''
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
            activeUsers: function () {
               return this.usersAndGroups.filter(function (u) {
                  return u.fullname !== undefined;
               })
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
             * Select an assigned user or group
             *
             * @param {object} assignee
             * @param {number} index
             */
            selectAssignee(assignee, index) {
                this.selectedAssigneeIndex = index;
                this.selectedAssignee = assignee;
            },
            /**
             * Updates in the backend the user assigned when it is changed
             *
             */
            changedUser() {
                let assignToDelete = this.assignedUsersGroups.pop();
                if (assignToDelete !== null && assignToDelete !== undefined) {
                    let idToDelete = assignToDelete.data === undefined ? assignToDelete.id : assignToDelete.data.id;
                    ProcessMaker.apiClient
                        .delete(`task_assignments/${idToDelete}`)
                        .then(() => {
                            this.selectedUserGroupIndex = -1;
                            this.selectedUserGroup = this.usersAndGroups
                                .filter(usr => usr.id === this.selectedUser && usr.fullname)[0];
                            this.addUserOrGroup();
                        });
                }
                else {
                    this.selectedUserGroupIndex = -1;
                    this.selectedUserGroup = this.usersAndGroups
                        .filter(usr => usr.id === this.selectedUser && usr.fullname)[0];
                    this.addUserOrGroup();
                }
            },
            /**
             * Remove a user or group from assigned list
             */
            removeUserOrGroup() {
                if (this.selectedAssigneeIndex > -1) {
                    ProcessMaker.apiClient
                        .delete(`task_assignments/${this.selectedAssignee.id}`)
                        .then(() => {
                            this.assignedUsersGroups.splice(this.selectedAssigneeIndex, 1);
                            this.selectedAssigneeIndex = -1;
                        });
                }
            },
            /**
             * Select an user or group
             *
             * @param {object} userGroup
             * @param {number} index
             */
            selectUserGroup(userGroup, index) {
                this.selectedUserGroupIndex = index;
                this.selectedUserGroup = userGroup;
            },
            /**
             * Add an user or group.
             */
            addUserOrGroup() {
                ProcessMaker.apiClient
                    .post("/task_assignments", {
                        'process_id': this.process.id,
                        'process_task_id': this.value,
                        'assignment_id': this.selectedUserGroup.id,
                        'assignment_type': this.selectedUserGroup.fullname ? USER_TYPE : GROUP_TYPE
                    })
                    .then(assignment => {
                        assignment.assigned = this.selectedUserGroup;
                        this.assignedUsersGroups.push(assignment);
                        this.selectUserGroup(null, -1);
                        this.showUserOrGroup = false;
                    });
            },
            /**
             * Cancel the add user or group action.
             */
            cancelAddUserOrGroup() {
                this.showUserOrGroup = false;
            },
            /**
             * Load the list of assigned users
             */
            loadUsersAndGroups() {
                this.usersAndGroups.splice(0);
                ProcessMaker.apiClient
                    .get("/users", {
                        params: {
                            filter: this.filter,
                            per_page: 5,
                        }
                    })
                    .then(response => {
                        this.usersAndGroups.push(...response.data.data);
                    });
                ProcessMaker.apiClient
                    .get("/groups", {
                        params: {
                            filter: this.filter,
                            per_page: 5,
                        }
                    })
                    .then(response => {
                        this.usersAndGroups.push(...response.data.data);
                    });
            },
            /**
             * Load the list of assigned users
             */
            loadAssignedUsers() {
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
                        this.assignedUsersGroups.splice(0);
                        this.assignedUsersGroups.push(...response.data.data);
                        this.loadingAssigned = false;


                        if (this.assignedUsersGroups.length > 0) {
                            this.selectedUser = this.assignedUsersGroups[0].assigned.id;
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
            value() {
                this.loadAssignedUsers();
            }
        },
        mounted() {
            this.loadUsersAndGroups();
            this.loadAssignedUsers();
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
