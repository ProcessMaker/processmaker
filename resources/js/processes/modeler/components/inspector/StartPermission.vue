<template>
    <div>
        <div class="form-group">
            <label>{{ $t('Start Permission') }}</label>
            <select class="form-control"
                    :value="assignmentGetter"
                    @input="assignmentSetter">
                <option value="">{{ $t('Select...') }}</option>
                <option value="user">{{ $t('User') }}</option>
                <option value="group">{{ $t('Group') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label class="text-capitalize">{{ $t(assignmentGetter)}}</label>
            <div v-if="loading">{{ $t('Loading...') }}</div>
            <div v-else>
                <multiselect v-model="content"
                             track-by="id"
                             label="name"
                             :placeholder="$t('type here to search')"
                             :options="options"
                             :multiple="false"
                             :show-labels="false"
                             :searchable="true"
                             :internal-search="false"
                             :helper="helper"
                             @search-change="load">
                </multiselect>
            </div>
        </div>
    </div>
</template>

<script>
  export default {
    props: ["value", "label", "helper", "property"],
    data() {
      return {
        content: null,
        options: [],
        loading: false,
        type: '',
      };
    },
    watch: {
      content: {
        handler() {
          if (this.type === 'user' && this.content) {
            this.assignedUserSetter(this.content.id)
          } else if (this.type === 'group' && this.content) {
            this.assignedGroupSetter(this.content.id)
          }
        }
      }
    },
    computed: {
      assignmentGetter() {
        const node = this.$parent.$parent.highlightedNode.definition;
        const value = _.get(node, "assignment");
        this.type = value;
        return value;
      }
    },
    methods: {
      load(filter) {
        this.options = [];
        if (this.type === 'user') {
          this.loadUsers(filter);
        } else if (this.type === 'group') {
          this.loadGroups(filter);
        }
      },
      loadUsers(filter) {
        ProcessMaker.apiClient
          .get("users?order_direction=asc&status=active" + (typeof filter === 'string' ? '&filter=' + filter : ''))
          .then(response => {
            this.loading = false;
            this.options = response.data.data.map(item => {
              return {
                id: item.id,
                name: item.fullname
              }
            });
          })
          .catch(err => {
            this.loading = false;
          });
      },
      loadGroups(filter) {
        ProcessMaker.apiClient
          .get("groups?order_direction=asc&status=active" + (typeof filter === 'string' ? '&filter=' + filter : ''))
          .then(response => {
            this.loading = false;
            this.options = response.data.data.map(item => {
              return {
                id: item.id,
                name: item.name
              }
            });
          })
          .catch(err => {
            this.loading = false;
          });
      },
      loadAssigned() {
        const node = this.$parent.$parent.highlightedNode.definition;
        if (this.type === 'user') {
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
        } else if (this.type === 'group') {
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

      assignedUserSetter(id) {
        let node = this.$parent.$parent.highlightedNode.definition;
        let value = _.get(node, "assignedUsers");
        this.$set(node, "assignedUsers", id);
        value = _.get(node, "assignedGroups");
        this.$set(node, "assignedGroups", '');
      },
      assignedGroupSetter(id) {
        let node = this.$parent.$parent.highlightedNode.definition;
        let value = _.get(node, "assignedUsers");
        this.$set(node, "assignedUsers", '');
        value = _.get(node, "assignedGroups");
        this.$set(node, "assignedGroups", id);
      },
      assignmentSetter(event) {
        this.type = event.target.value;
        this.content = null;
        let node = this.$parent.$parent.highlightedNode.definition;
        this.$set(node, "assignment", this.type);
        this.load();
      },
    },
    mounted() {
      this.loadAssigned();
    },
  };
</script>

<style lang="scss" scoped>
    @import "~vue-multiselect/dist/vue-multiselect.min.css";
</style>
