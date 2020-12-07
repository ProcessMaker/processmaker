<template>
    <div class="form-group">
        <label v-if="label">{{ label }}</label>
        <multi-select
            v-model="content"
            track-by="id"
            label="fullname"
            group-values="items"
            group-label="type"
            :class="{'border border-danger':error}"
            :loading="loading"
            :placeholder="$t('type here to search')"
            :options="options"
            :multiple="multiple"
            :show-labels="false"
            :searchable="true"
            :internal-search="false"
            @open="load"
            @search-change="load">

            <template slot="noResult">
                <slot name="noResult">{{ $t("No elements found. Consider changing the search query.") }}</slot>
            </template>
            <template slot="noOptions">
                <slot name="noOptions">{{ $t("No Data Available") }}</slot>
            </template>
        </multi-select>

        <small v-if="error" class="text-danger">{{ error }}</small>
        <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>

    </div>

</template>

<script>
  import MultiSelect from "vue-multiselect";

  const addUsernameToFullName = (user) => {
    if (!user.fullname || ! user.username)
    {
      return user;
    }
    return {...user, fullname: `${user.fullname} (${user.username})`};
  };

  export default {
    components: {
      MultiSelect
    },
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
            return addUsernameToFullName(this.results.find(item => item.id === uid));
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
          if (value.length) {
            value.forEach(item => {
              this.results.push(item);
              if (typeof item.id === "number") {
                this.selected.users.push(item.id);
              } else {
                this.selected.groups.push(parseInt(item.id.substr(6)));
              }
            });
          } else {
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
      content: {
        handler () {
          this.lastEmitted = JSON.stringify(this.selected);
          this.$emit("input", this.selected);
        }
      },
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
                results.push(item.data);
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
            const users = response.data.data.map(user => addUsernameToFullName(user));
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
