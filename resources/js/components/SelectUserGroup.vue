<template>
    <div class="form-group">
        <label v-if="label">{{ label }}</label>
        <multiselect
            :id="'category-select-' + _uid"
            v-model="content"
            track-by="id"
            label="fullname"
            group-values="items"
            group-label="type"
            :class="{'border border-danger':error}"
            :loading="loading"
            :placeholder="placeholder ? placeholder : $t('type here to search')"
            :options="options"
            :multiple="multiple"
            :show-labels="false"
            :searchable="true"
            :internal-search="false"
            @open="load(null)"
            @input="updateSeletected"
            @search-change="load">

            <template slot="noResult">
                <slot name="noResult">{{ $t("No elements found. Consider changing the search query.") }}</slot>
            </template>
            <template slot="noOptions">
                <slot name="noOptions">{{ $t("No Data Available") }}</slot>
            </template>
        </multiselect>

        <small v-if="error" class="text-danger">{{ error }}</small>
        <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>

    </div>

</template>

<script>
  export default {
    props: {
      value: null,
      label: {
        type: String,
        default: ""
      },
      multiple: {
        type: Boolean,
        default: true
      },
      hideUsers: {
        type: Boolean,
        default: false
      },
      hideGroups: {
        type: Boolean,
        default: false
      },
      error: String,
      helper: String,
      placeholder: String,
    },
    data () {
      return {
        loading: false,
        selected: {
          users: [],
          groups: []
        },
        options: [],
        results: [],
        lastEmitted: "",
        labelUsers: this.$t("Users"),
        labelGroups: this.$t("Groups"),
      };
    },
    computed: {
      content: {
        get () {
          if (this.loading) {
            return [];
          }
          return this.selected.users.map(user => {
            let uid;
            if (typeof user === 'number') {
              uid = user;
            } else {
              uid = user.id;
            }
            return this.results.find(item => item.id === uid);
          })
            .concat(this.selected.groups.map(group => {
              let gid;
              if (typeof group == 'number') {
                gid = "group-" + group;
              } else {
                gid = group.id;
              }
              return this.results.find(item => item.id ===  gid);
              
            }));
        },
        set (value) {
          this.selected.users = [];
          this.selected.groups = [];
          if (value === null) {
            return;
          }

          // If it is array (this happens when the Select User/Group is selected)
          // add value just if it is not empty
          if (Array.isArray(value) && value.length) {
            value.forEach(item => {
              this.results.push(item);
              if (typeof item.id === "number") {
                this.selected.users.push(item.id);
              } else {
                this.selected.groups.push(parseInt(item.id.substr(6)));
              }
            });
          }

          //If an object arrives as value (this happens with Self Service and assign by expression)
          if (!Array.isArray(value) && value)
          {
            this.results.push(value);
            if (typeof value.id === "number") {
              this.selected.users.push(value);
            } else {
              this.selected.groups.push(value);
            }
          }

        }
      }
    },
    watch: {
      value: {
        immediate: true,
        deep: true,
        handler (value) {
          if (!value) {
            return
          }
          if (value.users.length === 0 && value.groups.length === 0) {
            return;
          }
          if (JSON.stringify(value) == this.lastEmitted) {
            return;
          }
          this.loading = true;
          let results = [];

          let usersPromise = Promise.all(
            value.users.map(item => {
              if (typeof item == 'number' || typeof item == 'string') {
                return ProcessMaker.apiClient.get("users/" + item);
              } else {
                if (item.assignee) {
                  let id = item.assignee;
                  return ProcessMaker.apiClient.get("users/" + id);
                }
              }
            })
          )
            .then(items => {
              items.forEach(item => {
                results.push(this.addUsernameToFullName(item.data));
              });
            });

          let groupsPromise = Promise.all(
            value.groups.map(item => {
              if (typeof item == 'number' || typeof item == 'string' ) {
                return ProcessMaker.apiClient.get("groups/" + item);
              } else {
                if (item.assignee) {
                  let id = this.unformatGroup(item.assignee);
                  return ProcessMaker.apiClient.get("groups/" + id);
                }
              }
            })
          )
            .then(items => {
              items.forEach(item => {
                results.push(this.formatGroup(item.data));
              });
            });

          Promise.all([usersPromise, groupsPromise])
            .then(() => {
              this.content = results;
              this.loading = false;
            });
        }
      }
    },
    methods: {
      updateSeletected() {
        this.lastEmitted = JSON.stringify(this.selected);
        this.$emit("input", this.selected);
      },
      addUsernameToFullName(user) {
        if (!user.fullname || ! user.username)
        {
          return user;
        }
        let status = '';
        if (user.status === 'INACTIVE') {
          status = " - " + this.$t('Inactive');
        }
        return {...user, fullname: `${user.fullname} (${user.username}${status})`};
      },
      load (filter) {
        this.options = [];
        if (!this.hideUsers) {
          this.loadUsers(filter);
        }
        if (!this.hideGroups) {
          this.loadGroups(filter);
        }
      },
      loadUsers (filter) {
        ProcessMaker.apiClient
          .get("users" + (typeof filter === "string" ? "?filter=" + filter : ""))
          .then(response => {
            const users = response.data.data.map(user => this.addUsernameToFullName(user));
            this.users = users;

            if (response.data.data) {
              this.options.push({
                "type": this.labelUsers,
                "items": users,
              });
            }
          });
      },
      loadGroups (filter) {
        ProcessMaker.apiClient
          .get("groups" + (typeof filter === "string" ? "?filter=" + filter : ""))
          .then(response => {
            let groups = response.data.data.map(item => {
              return this.formatGroup(item);
            });

            if (groups) {
              this.options.push({
                "type": this.labelGroups,
                "items": groups
              });
            }
          });
      },
      formatGroup (item) {
        if (item && typeof item.id == 'number') {
          item.id = "group-" + item.id;
        }
        item.fullname = item.name;
        if (item.status === 'INACTIVE') {
          item.fullname += " (" + this.$t('Inactive') + ")";
        }
        return item;
      },
      unformatGroup(groupId) {
        if (typeof groupId == 'number') {
          return groupId;
        } else {
          let id = groupId.replace('group-', "");
          return id;
        }
        
      }
    },
  };
</script>
