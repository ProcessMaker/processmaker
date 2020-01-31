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
          return this.selected.users.map(uid => {
            return this.results.find(item => item.id === uid);
          })
            .concat(this.selected.groups.map(gid => {
              return this.results.find(item => item.id === "group-" + gid);
            }));
        },
        set (value) {
          this.selected.users = [];
          this.selected.groups = [];
          value.forEach(item => {
            this.results.push(item);
            if (typeof item.id === "number") {
              this.selected.users.push(item.id);
            } else {
              this.selected.groups.push(parseInt(item.id.substr(6)));
            }
          });
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
              return ProcessMaker.apiClient.get("users/" + item);
            })
          )
            .then(items => {
              items.forEach(item => {
                results.push(item.data);
              });
            });

          let groupsPromise = Promise.all(
            value.groups.map(item => {
              return ProcessMaker.apiClient.get("groups/" + item);
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
            this.users = response.data.data;
            if (response.data.data) {
              this.options.push({
                "type": this.labelUsers,
                "items": response.data.data
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
        item.id = "group-" + item.id;
        item.fullname = item.name;
        return item;
      },
    },
  };
</script>
