<template>
  <div>
    <div class="form-group">
      <label>Start Permission</label>
      <select
        class="form-control"
        v-model="selectedType"
      >
        <option value="null"></option>
        <option value="user">User</option>
        <option value="group">Group</option>
      </select>
    </div>

    <div class="form-group" v-if="selectedType == 'user'">
      <label>User</label>
      <div v-if="loadingUsers">Loading...</div>
      <select v-else class="form-control" v-model="selectedUser">
        <option></option>
        <option v-for="(row, index) in users" :key="index">{{row.fullname}}</option>
      </select>
    </div>

    <div class="form-group" v-if="selectedType == 'group'">
      <label>Group</label>
      <div v-if="loadingGroups">Loading...</div>
      <select v-else class="form-control" v-model="selectedGroup">
        <option></option>
        <option v-for="(row, index) in groups" :key="index">{{row.name}}</option>
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
      selectedType: null,
    };
  },
  computed: {
    // allowReassignmentGetter() {
    //   const node = this.$parent.$parent.highlightedNode.definition;
    //   const value = _.get(node, "allowReassignment");
    //   return value;
    // },
  },
  methods: {
    node() {
      return this.$parent.$parent.highlightedNode.definition.id;
    },
    processId() {
      return window.ProcessMaker.modeler.process.id;
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
    update() {
      ProcessMaker.apiClient
        .put("/processes/" + , {
          params: params
        })
        .then(response => {
          this.users.push(...response.data.data);
          this.loadingUsers = false;
        });
    }
  },
  mounted() {
    this.loadUsersAndGroups();
  },
  watch: {
    selectedUser() {
      this.update();
    },
    selectedGroup() {
      this.update();
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
