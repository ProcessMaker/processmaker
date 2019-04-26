<template>
  <div>
    <div class="form-group">
      <label>{{ $t('Start Permission') }}</label>
      <select
        class="form-control"
        :value="assignmentGetter"
        @input="assignmentSetter"
        ref="startSelect"
      >
        <option value=""></option>
        <option value="user">{{ $t('User') }}</option>
        <option value="group">{{ $t('Group') }}</option>
      </select>
    </div>

    <div class="form-group" v-if="assignmentGetter === 'user'">
      <label>{{ $t('User') }}</label>
      <div v-if="loadingUsers">{{ $t('Loading...') }}</div>
      <select v-else class="form-control" :value="assignedUserGetter" @input="assignedUserSetter">
        <option></option>
        <option v-for="(row, index) in users" :key="index" :value="row.id">{{ row.fullname }}</option>
      </select>
    </div>

    <div class="form-group" v-if="assignmentGetter === 'group'">
      <label>{{ $t('Group') }}</label>
      <div v-if="loadingGroups">{{ $t('Loading...') }}</div>
      <select v-else class="form-control" :value="assignedGroupGetter" @input="assignedGroupSetter">
        <option></option>
        <option v-for="(row, index) in groups" :key="index" :value="row.id">{{ row.name }}</option>
      </select>
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
      loadingUsers: true,
      loadingGroups: true,
      selectedUser: null,
      selectedGroup: null,
    };
  },
  computed: {
    assignedUserGetter() {
      const node = this.$parent.$parent.highlightedNode.definition;
      const value = _.get(node, "assignedUsers");
      return value;
    },
    assignedGroupGetter() {
      const node = this.$parent.$parent.highlightedNode.definition;
      const value = _.get(node, "assignedGroups");
      return value;
    },
    assignmentGetter() {
      const node = this.$parent.$parent.highlightedNode.definition;
      const value = _.get(node, "assignment");
      return value;
    },
    processId() {
      return window.ProcessMaker.modeler.process.id;
    },
    node() {
      return this.$parent.$parent.highlightedNode.definition;
    },
  },
  methods: {
    assignedUserSetter(event) {
      this.$set(this.node, "assignedUsers", event.target.value);
      this.$emit("input", this.value);
    },
    assignedGroupSetter(event) {
      this.$set(this.node, "assignedGroups", event.target.value);
      this.$emit("input", this.value);
    },
    assignmentSetter(event) {
      this.$set(this.node, "assignment", event.target.value);
      if(event.target.value === 'user') {
        this.$set(this.node, 'assignedGroups', '');
      } else {
        this.$set(this.node, 'assignedUsers', '');
      }
      this.$emit("input", this.value);
    },
    loadUsersAndGroups() {
      this.loadingUsers = true;
      this.users = [];
      let params = Object.assign({ per_page: 10000 }, this.params);
      ProcessMaker.apiClient
        .get("/users", {
          params: params
        })
        .then(response => {
          this.users.push(...response.data.data);
          this.loadingUsers = false;
        });

      this.loadingGroups = true;
      this.groups = [];
      ProcessMaker.apiClient
        .get("/groups", {
          params: params
        })
        .then(response => {
          this.groups.push(...response.data.data);
          this.loadingGroups = false;
        });
    },
  },
  mounted() {
    this.loadUsersAndGroups();
  },
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
