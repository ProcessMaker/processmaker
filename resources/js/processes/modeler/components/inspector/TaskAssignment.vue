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

        <div class="form-group" v-if="showAssignOneUser">
            <label>Assigned User</label>
            <div v-if="loadingUsers">Loading...</div>
            <select v-else class="form-control" :value="assignedUserGetter"
                    @input="assignedUserSetter">
                <option></option>
                <option v-for="(row, index) in activeUsers" v-bind:value="row.id" :selected="row.id == assignedUserGetter">
                    {{row.fullname}}
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
                usersAndGroups: [],
                loadingUsers: true,
            };
        },
        computed: {
            process() {
                return this.$parent.$parent.$parent.process;
            },
            /**
             * Get the value of the edited property
             */
            assignedUserGetter() {
                const node = this.$parent.$parent.inspectorNode;
                const value = _.get(node, 'assignedUsers');
                return value;
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
             * Load the list of assigned users
             */
            loadUsersAndGroups() {
                this.loadingUsers = true;
                this.usersAndGroups.splice(0);
                ProcessMaker.apiClient
                    .get("/users")
                    .then(response => {
                        this.usersAndGroups.push(...response.data.data);
                        this.loadingUsers = false;
                    });
            },
            /**
             * Update the event of the editer property
             */
            assignedUserSetter(event) {
                this.$set(this.node, 'assignedUsers', event.target.value);
                this.$emit('input', this.value);
            },
            /**
             * Update the event of the editer property
             */
            assignmentSetter(event) {
                this.$set(this.node, 'assignment', event.target.value);
                this.$emit('input', this.value);
            },
        },
        watch: {
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
