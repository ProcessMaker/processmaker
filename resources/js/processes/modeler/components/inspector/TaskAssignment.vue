<template>
    <div>
        <div class="form-group">
            <label>Task Assignment</label>
            <select class="form-control" :value="assignmentGetter" @input="assignmentSetter">
                <option value=""></option>
                <option value="requestor">To requestor</option>
            </select>
        </div>
        <div class="form-group">
            <label>Assigned Users/Groups</label>
            <button @click="showUserOrGroup=true;" class="btn-sm float-right">+</button>
            <button @click="removeUserOrGroup" :disabled="!selectedAssignee" class="btn-sm float-right">-</button>
            <div class="list-users-groups small">
                <span v-for="(row, index) in assignedUsersGroups"
                      class="list-group-item list-group-item-action pt-0 pb-0"
                      :class="{'bg-primary': selectedAssigneeIndex == index}"
                      @click="selectAssignee(row, index)">
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
                <b-button :disabled="selectedUserGroupIndex < 0" @click="addUserOrGroup"
                    class="btn btn-outline-success btn-sm text-uppercase">
                    ADD
            </b-button>
            <b-button @click="cancelAddUserOrGroup" class="btn btn-success btn-sm text-uppercase">
                CANCEL
            </b-button>
        </div>

    </b-modal>
</div>
</template>


<script>
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
            };
        },
        computed: {
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
             * Remove a user or group from assigned list
             */
            removeUserOrGroup() {
                if (this.selectedAssigneeIndex > -1) {
                    this.assignedUsersGroups.splice(this.selectedAssigneeIndex, 1);
                    this.selectedAssigneeIndex = -1;
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
                this.assignedUsersGroups.push(this.selectedUserGroup);
                this.selectUserGroup(null, -1);
                this.showUserOrGroup = false;
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
            },
            /**
             * Update the event of the editer property
             */
            assignmentSetter(event) {
                Vue.set(this.node, 'assignment', event.target.value);
                this.$emit('input', this.value);
            },
        },
        mounted() {
            this.loadUsersAndGroups();
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